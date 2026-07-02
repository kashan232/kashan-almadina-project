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
        $userGroups = UserGroup::orderBy('group_name')->get();
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

        if ($report_type == 'Party Wise') {
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
        $transactionType = $request->input('transaction_type', 'purchase');
        $lines = collect();

        if (in_array($transactionType, ['purchase', 'both'], true)) {
            $lines = $lines->merge(
                $this->fetchPurchaseLines($request)->map(fn ($item) => $this->wrapPurchaseLine($item, 1))
            );
        }

        if (in_array($transactionType, ['purchase_return', 'both'], true)) {
            $sign = $transactionType === 'both' ? -1 : 1;
            $lines = $lines->merge(
                $this->fetchReturnLines($request)->map(fn ($item) => $this->wrapReturnLine($item, $sign))
            );
        }

        if (in_array($transactionType, ['claim_credit_note', 'both'], true)) {
            $sign = $transactionType === 'both' ? -1 : 1;
            $lines = $lines->merge(
                $this->fetchClaimCreditNoteLines($request)->map(fn ($item) => $this->wrapClaimCreditNoteLine($item, $sign))
            );
        }

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
                $q->withoutGlobalScopes()->with(['vendor', 'purchasable', 'warehouse', 'user']);
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
        if ($sign === 1) {
            $item->setAttribute('entry_type', 'purchase');
            $item->setAttribute('entry_type_label', 'Purchase');
            return $item;
        }

        return (object) [
            'purchase' => $item->purchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * (float) $item->qty,
            'form_rate' => $item->form_rate,
            'form_line_total' => $sign * (float) $item->form_line_total,
            'entry_type' => 'purchase',
            'entry_type_label' => 'Purchase',
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
            'invoice_no' => $return?->invoice_no,
            'current_date' => $return?->current_date,
            'purchasable' => $return?->purchasable,
            'vendor' => $return?->purchasable_type === Vendor::class ? $return?->purchasable : null,
            'purchasable_id' => $return?->purchasable_id,
            'vendor_id' => $return?->vendor_id,
        ];

        return (object) [
            'purchase' => $pseudoPurchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * $qty,
            'form_rate' => $formRate,
            'form_line_total' => $sign * $formLineTotal,
            'entry_type' => 'purchase_return',
            'entry_type_label' => 'Purchase Return',
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
            'invoice_no' => $note?->voucher_no,
            'current_date' => $note?->date,
            'purchasable' => $party,
            'vendor' => $note?->party_type === 'vendor' ? $party : null,
            'purchasable_id' => $note?->party_id,
            'vendor_id' => $note?->party_type === 'vendor' ? $note?->party_id : null,
        ];

        return (object) [
            'purchase' => $pseudoPurchase,
            'product' => $item->product,
            'product_id' => $item->product_id,
            'qty' => $sign * $qty,
            'form_rate' => $formRate,
            'form_line_total' => $sign * $formLineTotal,
            'entry_type' => 'claim_credit_note',
            'entry_type_label' => 'Claim Credit Note',
        ];
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
        $grouped = $purchaseItems->groupBy(fn ($item) => $this->partyKey($item))
            ->map(function ($items) {
                return $items->groupBy(fn ($item) => $item->purchase->invoice_no);
            });

        return view('admin_panel.reports.purchase.preview_invoice', compact('grouped', 'from_date', 'to_date'));
    }
}
