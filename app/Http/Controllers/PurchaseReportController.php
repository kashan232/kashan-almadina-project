<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\ClaimCreditNoteItem;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Collection;

class PurchaseReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyPurchaseDateFilter($query, ?string $from_date, ?string $to_date, string $column = 'current_date'): void
    {
        if (!empty($from_date)) {
            $query->whereDate($column, '>=', $from_date);
        }
        if (!empty($to_date)) {
            $query->whereDate($column, '<=', $to_date);
        }
    }

    private function lineFormAmounts(float $price, float $discPct, float $retail, float $qty, float $lineTotal): array
    {
        $base = $retail > 0 ? $retail : $price;
        $unitDiscAmt = $base * $discPct / 100;
        $formRate = $price - $unitDiscAmt;
        $formLineTotal = $lineTotal != 0.0 ? $lineTotal : ($formRate * $qty);

        return [$formRate, $formLineTotal];
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = !$user || $user->roles->pluck('name')->contains('Admin') || $user->id == 1;

        $userGroups = UserGroup::orderBy('group_name')
            ->when(!$isAdmin, function ($q) use ($user) {
                $groupIds = $user ? $user->userGroups()->pluck('user_groups.id')->toArray() : [];
                $q->whereIn('id', $groupIds);
            })
            ->get();

        $users = User::with('userGroups')->orderBy('name')->get();
        $warehouses = Warehouse::all();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.purchase.index', compact(
            'userGroups', 'users', 'warehouses', 'categories',
            'brands', 'products', 'customers', 'vendors', 'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $report_type = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $purchaseItems = $this->buildReportLines($request);

        if ($report_type == 'All') {
            return $this->previewAll($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Actual Invoice') {
            return $this->previewActualInvoice($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Party Wise') {
            return $this->previewPartyWise($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Item Wise') {
            return $this->previewItemWise($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Invoice Wise') {
            return $this->previewInvoiceWise($purchaseItems, $from_date, $to_date);
        }

        return back()->with('error', 'Invalid Report Type Selected');
    }

    private function extractFilters(Request $request): array
    {
        return [
            'user_groups' => $request->user_group ?? [],
            'sales_officers' => $request->sales_officer ?? [],
            'warehouses' => $request->warehouse ?? [],
            'categories' => $request->category ?? [],
            'subcategories' => $request->subcategory ?? [],
            'brands' => $request->brand ?? [],
            'items' => $request->item ?? [],
            'party_types' => $request->party_type ?? [],
            'parties' => $request->party ?? $request->vendor ?? [],
            'invoice_no' => $request->invoice_no,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'totalGroups' => UserGroup::count(),
            'totalUsers' => User::count(),
            'totalPartyTypes' => 3,
            'totalParties' => Customer::count() + Vendor::count(),
            'totalWarehouses' => Warehouse::count() + 1,
            'totalCategories' => Category::count(),
            'totalSubcategories' => Subcategory::count(),
            'totalBrands' => Brand::count(),
            'totalProducts' => Product::count(),
        ];
    }

    private function buildReportLines(Request $request): Collection
    {
        $rawTypes = $request->input('transaction_type', ['purchase', 'purchase_return', 'claim_credit_note']);
        $selectedTypes = is_array($rawTypes) ? $rawTypes : [$rawTypes];
        if (in_array('both', $selectedTypes, true)) {
            $selectedTypes = ['purchase', 'purchase_return', 'claim_credit_note'];
        }

        $lines = collect();

        $extractNum = function ($val) {
            return (int) (preg_replace('/[^0-9]/', '', (string) $val) ?: 0);
        };

        if (in_array('purchase', $selectedTypes, true)) {
            $purchaseLines = $this->fetchPurchaseLines($request)
                ->map(fn ($item) => $this->wrapPurchaseLine($item, 1))
                ->sortBy(fn ($item) => $extractNum($item->purchase->invoice_no ?? ''))
                ->values();
            $lines = $lines->merge($purchaseLines);
        }

        if (in_array('purchase_return', $selectedTypes, true)) {
            $isNetMode = count($selectedTypes) > 1;
            $sign = $isNetMode ? -1 : 1;
            $returnLines = $this->fetchReturnLines($request)
                ->map(fn ($item) => $this->wrapReturnLine($item, $sign))
                ->sortBy(fn ($item) => $extractNum($item->purchase->invoice_no ?? ''))
                ->values();
            $lines = $lines->merge($returnLines);
        }

        if (in_array('claim_credit_note', $selectedTypes, true)) {
            $isNetMode = count($selectedTypes) > 1;
            $sign = $isNetMode ? -1 : 1;
            $claimLines = $this->fetchClaimCreditNoteLines($request)
                ->map(fn ($item) => $this->wrapClaimCreditNoteLine($item, $sign))
                ->sortBy(fn ($item) => $extractNum($item->purchase->invoice_no ?? ''))
                ->values();
            $lines = $lines->merge($claimLines);
        }

        // Attach date-aware purchase_retail_price to each line
        $allProductPrices = \App\Models\ProductPrice::orderBy('start_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('product_id');

        $lines->each(function($item) use ($allProductPrices) {
            $pId = $item->product_id;
            $purchase = $item->purchase;
            $createdVal = is_object($purchase) && isset($purchase->created_at) ? $purchase->created_at : null;
            $entryVal = is_object($purchase) && isset($purchase->entry_date) ? $purchase->entry_date : null;
            $currVal = is_object($purchase) && isset($purchase->current_date) ? $purchase->current_date : null;

            $rawDate = !empty($entryVal) ? $entryVal : (!empty($currVal) ? $currVal : ($createdVal ? \Carbon\Carbon::parse($createdVal)->toDateString() : now()->toDateString()));
            $purchaseTime = $createdVal ? \Carbon\Carbon::parse($createdVal) : now();

            $retailPrice = 0;
            $pricesForProd = $allProductPrices->get($pId, collect());

            if ($pricesForProd->isNotEmpty()) {
                $matched = $pricesForProd->filter(function($pr) use ($rawDate) {
                    $start = $pr->start_date;
                    $end = $pr->end_date;
                    if ($start && $rawDate < $start) return false;
                    if ($end && $rawDate > $end) return false;
                    return true;
                });

                if ($matched->count() > 1) {
                    $exact = $matched->filter(fn($pr) => \Carbon\Carbon::parse($pr->created_at) <= $purchaseTime)->last();
                    $chosen = $exact ?: $matched->last();
                } else {
                    $chosen = $matched->first();
                }

                if (!$chosen) {
                    $chosen = $pricesForProd->filter(fn($pr) => !$pr->start_date || $pr->start_date <= $rawDate)->last() ?: $pricesForProd->last();
                }

                $retailPrice = (float) ($chosen->purchase_retail_price ?? $chosen->sale_retail_price ?? 0);
            }

            if ($retailPrice <= 0) {
                $retailPrice = (float) ($item->product?->latestPrice?->purchase_retail_price ?? $item->product?->latestPrice?->sale_retail_price ?? $item->product?->retail_price ?? 0);
            }

            $item->purchase_retail_price = $retailPrice;
        });

        return $lines->values();
    }

    private function applyPurchaseHeaderFilters($query, array $filters): void
    {
        $query->withoutGlobalScopes()->where('status', 'Posted');
        $this->applyPurchaseDateFilter($query, $filters['from_date'], $filters['to_date']);

        if (!empty($filters['invoice_no'])) {
            $invoiceNo = $filters['invoice_no'];
            $query->where(function ($sub) use ($invoiceNo) {
                $sub->where('invoice_no', 'like', "%{$invoiceNo}%")
                    ->orWhere('invoice_no', 'like', '%' . ltrim($invoiceNo, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($filters['parties'], $filters['totalParties'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->whereIn('vendor_id', $filters['parties'])
                    ->orWhereIn('purchasable_id', $filters['parties']);
            });
        }

        if ($this->shouldApplyFilter($filters['sales_officers'], $filters['totalUsers'])) {
            $query->whereIn('created_by', $filters['sales_officers']);
        }

        if ($this->shouldApplyFilter($filters['user_groups'], $filters['totalGroups'])) {
            $query->where(function ($sub) use ($filters) {
                foreach ($filters['user_groups'] as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($filters['warehouses'], $filters['totalWarehouses'])) {
            $query->whereIn('warehouse_id', $filters['warehouses']);
        }

        if ($this->shouldApplyFilter($filters['party_types'], $filters['totalPartyTypes'])) {
            $query->where(function ($pt) use ($filters) {
                if (in_array('Vendor', $filters['party_types'])) {
                    $pt->orWhere('purchasable_type', Vendor::class);
                }
                if (in_array('Main Customer', $filters['party_types'])) {
                    $mainCustomerIds = Customer::where('customer_type', 'Main Customer')->pluck('id');
                    $pt->orWhere(function ($sq) use ($mainCustomerIds) {
                        $sq->where('purchasable_type', Customer::class)
                            ->whereIn('purchasable_id', $mainCustomerIds);
                    });
                }
                if (in_array('Walking Customer', $filters['party_types'])) {
                    $walkinCustomerIds = Customer::where('customer_type', 'Walking Customer')->pluck('id');
                    $pt->orWhere(function ($sq) use ($walkinCustomerIds) {
                        $sq->where('purchasable_type', Customer::class)
                            ->whereIn('purchasable_id', $walkinCustomerIds);
                    });
                }
            });
        }
    }

    private function applyReturnHeaderFilters($query, array $filters): void
    {
        $query->withoutGlobalScopes()->where('status', 'Posted');
        $this->applyPurchaseDateFilter($query, $filters['from_date'], $filters['to_date']);

        if (!empty($filters['invoice_no'])) {
            $invoiceNo = $filters['invoice_no'];
            $query->where(function ($sub) use ($invoiceNo) {
                $sub->where('invoice_no', 'like', "%{$invoiceNo}%")
                    ->orWhere('invoice_no', 'like', '%' . ltrim($invoiceNo, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($filters['parties'], $filters['totalParties'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->whereIn('vendor_id', $filters['parties'])
                    ->orWhereIn('purchasable_id', $filters['parties']);
            });
        }

        if ($this->shouldApplyFilter($filters['sales_officers'], $filters['totalUsers'])) {
            $query->whereIn('created_by', $filters['sales_officers']);
        }

        if ($this->shouldApplyFilter($filters['user_groups'], $filters['totalGroups'])) {
            $query->where(function ($sub) use ($filters) {
                foreach ($filters['user_groups'] as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($filters['warehouses'], $filters['totalWarehouses'])) {
            $query->whereIn('warehouse_id', $filters['warehouses']);
        }

        if ($this->shouldApplyFilter($filters['party_types'], $filters['totalPartyTypes'])) {
            $query->where(function ($pt) use ($filters) {
                if (in_array('Vendor', $filters['party_types'])) {
                    $pt->orWhere('purchasable_type', Vendor::class);
                }
                if (in_array('Main Customer', $filters['party_types'])) {
                    $mainCustomerIds = Customer::where('customer_type', 'Main Customer')->pluck('id');
                    $pt->orWhere(function ($sq) use ($mainCustomerIds) {
                        $sq->where('purchasable_type', Customer::class)
                            ->whereIn('purchasable_id', $mainCustomerIds);
                    });
                }
                if (in_array('Walking Customer', $filters['party_types'])) {
                    $walkinCustomerIds = Customer::where('customer_type', 'Walking Customer')->pluck('id');
                    $pt->orWhere(function ($sq) use ($walkinCustomerIds) {
                        $sq->where('purchasable_type', Customer::class)
                            ->whereIn('purchasable_id', $walkinCustomerIds);
                    });
                }
            });
        }
    }

    private function applyClaimCreditNoteHeaderFilters($query, array $filters): void
    {
        $query->withoutGlobalScopes()->where('status', 'Posted');
        $this->applyPurchaseDateFilter($query, $filters['from_date'], $filters['to_date'], 'date');

        if (!empty($filters['invoice_no'])) {
            $invoiceNo = $filters['invoice_no'];
            $query->where(function ($sub) use ($invoiceNo) {
                $sub->where('voucher_no', 'like', "%{$invoiceNo}%")
                    ->orWhere('voucher_no', 'like', '%' . ltrim($invoiceNo, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($filters['parties'], $filters['totalParties'])) {
            $query->whereIn('party_id', $filters['parties']);
        }

        if ($this->shouldApplyFilter($filters['sales_officers'], $filters['totalUsers'])) {
            $query->whereIn('created_by', $filters['sales_officers']);
        }

        if ($this->shouldApplyFilter($filters['user_groups'], $filters['totalGroups'])) {
            $query->where(function ($sub) use ($filters) {
                foreach ($filters['user_groups'] as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($filters['warehouses'], $filters['totalWarehouses'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->whereIn('from_warehouse_id', $filters['warehouses'])
                    ->orWhereIn('to_warehouse_id', $filters['warehouses']);
            });
        }

        if ($this->shouldApplyFilter($filters['party_types'], $filters['totalPartyTypes'])) {
            $query->where(function ($pt) use ($filters) {
                if (in_array('Vendor', $filters['party_types'])) {
                    $pt->orWhere('party_type', 'vendor');
                }
                if (in_array('Main Customer', $filters['party_types'])) {
                    $pt->orWhere(function ($sq) {
                        $sq->where('party_type', 'customer')
                            ->whereHas('customer', fn ($c) => $c->where('customer_type', 'Main Customer'));
                    });
                }
                if (in_array('Walking Customer', $filters['party_types'])) {
                    $pt->orWhere(function ($sq) {
                        $sq->where('party_type', 'customer')
                            ->whereHas('customer', fn ($c) => $c->where('customer_type', 'Walking Customer'));
                    });
                }
            });
        }
    }

    private function applyProductFilters($query, array $filters): void
    {
        if ($this->shouldApplyFilter($filters['items'], $filters['totalProducts'])) {
            $query->whereIn('product_id', $filters['items']);
        }

        if (
            $this->shouldApplyFilter($filters['brands'], $filters['totalBrands'])
            || $this->shouldApplyFilter($filters['categories'], $filters['totalCategories'])
            || $this->shouldApplyFilter($filters['subcategories'], $filters['totalSubcategories'])
        ) {
            $query->whereHas('product', function ($p) use ($filters) {
                if ($this->shouldApplyFilter($filters['brands'], $filters['totalBrands'])) {
                    $p->whereIn('brand_id', $filters['brands']);
                }
                if ($this->shouldApplyFilter($filters['categories'], $filters['totalCategories'])) {
                    $p->whereIn('category_id', $filters['categories']);
                }
                if ($this->shouldApplyFilter($filters['subcategories'], $filters['totalSubcategories'])) {
                    $p->whereIn('sub_category_id', $filters['subcategories']);
                }
            });
        }
    }

    private function fetchPurchaseLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = PurchaseItem::with([
            'purchase' => function ($q) {
                $q->withoutGlobalScopes()->with([
                    'vendor',
                    'purchasable',
                    'warehouse',
                    'user',
                    'accountAllocations.account',
                    'accountAllocations.head',
                    'whtAccount',
                ]);
            },
            'product.brandRelation',
            'product.latestPrice',
        ])->whereHas('purchase', function ($q) use ($filters) {
            $this->applyPurchaseHeaderFilters($q, $filters);
        });

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function fetchReturnLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = PurchaseReturnItem::with([
            'purchaseReturn.purchasable',
            'product.brandRelation',
            'product.latestPrice',
        ])->whereHas('purchaseReturn', function ($q) use ($filters) {
            $this->applyReturnHeaderFilters($q, $filters);
        });

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function fetchClaimCreditNoteLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = ClaimCreditNoteItem::with([
            'creditNote.vendor',
            'creditNote.customer',
            'product.brandRelation',
            'product.latestPrice',
        ])->whereHas('creditNote', function ($q) use ($filters) {
            $this->applyClaimCreditNoteHeaderFilters($q, $filters);
        });

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function wrapPurchaseLine(PurchaseItem $item, int $sign): object
    {
        $rate = (float) ($item->purchase_rate > 0 ? $item->purchase_rate : $item->form_rate);
        $total = (float) ($item->line_total > 0 ? $item->line_total : $item->form_line_total);

        if ($sign === 1) {
            $item->setAttribute('entry_type', 'purchase');
            $item->setAttribute('entry_type_label', 'PJ');
            $item->setAttribute('purchase_rate', $rate);
            $item->setAttribute('form_rate', $rate);
            $item->setAttribute('line_total', $total);
            $item->setAttribute('form_line_total', $total);
            return $item;
        }

        return (object) [
            'purchase' => $item->purchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * (float) $item->qty,
            'form_rate' => $rate,
            'purchase_rate' => $rate,
            'form_line_total' => $sign * $total,
            'line_total' => $sign * $total,
            'entry_type' => 'purchase',
            'entry_type_label' => 'PJ',
        ];
    }

    private function wrapReturnLine(PurchaseReturnItem $item, int $sign): object
    {
        $return = $item->purchaseReturn;
        $qty = (float) $item->qty;
        [$formRate, $formLineTotal] = $this->lineFormAmounts(
            (float) $item->price,
            (float) ($item->discount_percent ?? 0),
            (float) ($item->retail_price ?? 0),
            $qty,
            (float) ($item->line_total ?? 0)
        );

        $pseudoPurchase = (object) [
            'id' => $return?->id,
            'invoice_no' => $return?->invoice_no,
            'current_date' => $return?->current_date,
            'created_at' => $return?->created_at,
            'purchasable' => $return?->purchasable,
            'vendor' => $return?->purchasable_type === Vendor::class ? $return?->purchasable : null,
            'purchasable_id' => $return?->purchasable_id,
            'vendor_id' => $return?->vendor_id,
            'wht' => 0,
            'discount' => 0,
            'accountAllocations' => collect(),
        ];

        return (object) [
            'purchase' => $pseudoPurchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * $qty,
            'form_rate' => $formRate,
            'form_line_total' => $sign * $formLineTotal,
            'purchase_rate' => $formRate,
            'price' => (float) $item->price,
            'retail_price' => (float) ($item->retail_price ?? 0),
            'line_total' => $sign * $formLineTotal,
            'entry_type' => 'purchase_return',
            'entry_type_label' => 'PRJ',
        ];
    }

    private function wrapClaimCreditNoteLine(ClaimCreditNoteItem $item, int $sign): object
    {
        $note = $item->creditNote;
        $qty = (float) $item->quantity;
        [$formRate, $formLineTotal] = $this->lineFormAmounts(
            (float) $item->price,
            (float) ($item->discount_percent ?? 0),
            (float) ($item->retail_price ?? 0),
            $qty,
            (float) ($item->line_total ?? 0)
        );

        $party = $note?->party_type === 'vendor' ? $note?->vendor : $note?->customer;

        $pseudoPurchase = (object) [
            'id' => $note?->id,
            'invoice_no' => $note?->voucher_no,
            'current_date' => $note?->date,
            'created_at' => $note?->created_at,
            'purchasable' => $party,
            'vendor' => $note?->party_type === 'vendor' ? $party : null,
            'purchasable_id' => $note?->party_id,
            'vendor_id' => $note?->party_type === 'vendor' ? $note?->party_id : null,
            'wht' => (float) ($note?->wht_amount ?? 0),
            'discount' => (float) ($note?->total_discount ?? 0),
            'accountAllocations' => collect(),
        ];

        return (object) [
            'purchase' => $pseudoPurchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * $qty,
            'form_rate' => $formRate,
            'form_line_total' => $sign * $formLineTotal,
            'purchase_rate' => $formRate,
            'price' => (float) $item->price,
            'retail_price' => (float) ($item->retail_price ?? 0),
            'line_total' => $sign * $formLineTotal,
            'entry_type' => 'claim_credit_note',
            'entry_type_label' => 'CLM-CN',
        ];
    }

    private function previewAll($purchaseItems, $from_date, $to_date)
    {
        return view('admin_panel.reports.purchase.preview_all', compact('purchaseItems', 'from_date', 'to_date'));
    }

    private function previewActualInvoice($purchaseItems, $from_date, $to_date)
    {
        $groupedInvoices = $purchaseItems->groupBy(function ($item) {
            $entryType = $item->entry_type ?? 'purchase';
            $invNo = $item->purchase->invoice_no ?? '0';
            $pId = $item->purchase->purchasable_id ?? $item->purchase->vendor_id ?? '0';
            $date = $item->purchase->entry_date ?? $item->purchase->current_date ?? '0';
            return "{$entryType}_{$invNo}_{$pId}_{$date}";
        });

        $extractNum = function ($val) {
            return (int) (preg_replace('/[^0-9]/', '', (string) $val) ?: 0);
        };

        $purchases = $groupedInvoices->map(function ($items) {
            $first = $items->first();
            $purchaseInfo = $first->purchase;
            $entryType = $first->entry_type ?? 'purchase';
            $entryTypeLabel = $first->entry_type_label ?? 'PJ';

            $isPurchase = $entryType === 'purchase';
            $wht = (float) ($purchaseInfo->wht ?? 0);
            $discount = (float) ($purchaseInfo->discount ?? 0);
            $allocations = ($isPurchase && isset($purchaseInfo->accountAllocations)) 
                ? $purchaseInfo->accountAllocations 
                : collect();

            return (object) [
                'id' => $purchaseInfo->id ?? null,
                'invoice_no' => $purchaseInfo->invoice_no ?? '',
                'entry_date' => $purchaseInfo->entry_date ?? null,
                'current_date' => $purchaseInfo->current_date ?? null,
                'created_at' => $purchaseInfo->created_at ?? null,
                'purchasable' => $purchaseInfo->purchasable ?? $purchaseInfo->vendor ?? null,
                'vendor' => $purchaseInfo->vendor ?? null,
                'wht' => $wht,
                'discount' => $discount,
                'accountAllocations' => $allocations,
                'entry_type' => $entryType,
                'entry_type_label' => $entryTypeLabel,
                'items' => $items,
            ];
        })->sortBy(function ($p) use ($extractNum) {
            return $extractNum($p->invoice_no);
        })->values();

        return view('admin_panel.reports.purchase.preview_actual_invoice', compact('purchases', 'from_date', 'to_date'));
    }

    private function partyKey($item): string
    {
        $purchase = $item->purchase;
        return (string) ($purchase->purchasable_id ?? $purchase->vendor_id ?? '0');
    }

    private function previewPartyWise($purchaseItems, $from_date, $to_date)
    {
        $grouped = $purchaseItems->groupBy(fn ($item) => $this->partyKey($item));

        return view('admin_panel.reports.purchase.preview_party', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewItemWise($purchaseItems, $from_date, $to_date)
    {
        $grouped = $purchaseItems->groupBy('product_id');

        return view('admin_panel.reports.purchase.preview_item', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewInvoiceWise($purchaseItems, $from_date, $to_date)
    {
        $invoices = $purchaseItems->groupBy(function ($item) {
            return $item->purchase->invoice_no;
        });

        return view('admin_panel.reports.purchase.preview_invoice', compact('invoices', 'from_date', 'to_date'));
    }
}
