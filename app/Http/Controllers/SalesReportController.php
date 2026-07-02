<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $warehouses = Warehouse::all();
        $categories = Category::orderBy('name')->get();
        $subcategories = Subcategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.sales.index', compact(
            'userGroups', 'users', 'warehouses', 'categories', 'subcategories',
            'brands', 'products', 'customers', 'vendors', 'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $report_type = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $saleItems = $this->buildReportLines($request);

        if ($report_type == 'Party Wise') {
            return $this->previewPartyWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Item Wise') {
            return $this->previewItemWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Invoice Wise') {
            return $this->previewInvoiceWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Claim Ratio') {
            return $this->previewClaimRatio($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Tax Summary') {
            return $this->previewTaxSummary($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Qty Wise') {
            return $this->previewQtyWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Sale vs List') {
            return $this->previewSaleVsList($saleItems, $from_date, $to_date);
        } else {
            return back()->with('error', 'Invalid Report Type Selected');
        }
    }

    private function buildReportLines(Request $request): Collection
    {
        $transactionType = $request->input('transaction_type', 'sale');
        $lines = collect();

        if (in_array($transactionType, ['sale', 'both'], true)) {
            $lines = $lines->merge(
                $this->fetchSaleLines($request)->map(fn ($item) => $this->wrapSaleLine($item, 1))
            );
        }

        if (in_array($transactionType, ['sale_return', 'both'], true)) {
            $sign = $transactionType === 'both' ? -1 : 1;
            $lines = $lines->merge(
                $this->fetchReturnLines($request)->map(fn ($item) => $this->wrapReturnLine($item, $sign))
            );
        }

        return $lines->values();
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
            'parties' => $request->party ?? [],
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

    private function applySaleHeaderFilters($query, array $filters): void
    {
        $from_date = $filters['from_date'];
        $to_date = $filters['to_date'];
        $invoice_no = $filters['invoice_no'];
        $parties = $filters['parties'];
        $party_types = $filters['party_types'];
        $sales_officers = $filters['sales_officers'];
        $user_groups = $filters['user_groups'];

        $query->where('is_sale_order', 0);

        if (!empty($from_date)) {
            $query->whereDate('created_at', '>=', $from_date);
        }
        if (!empty($to_date)) {
            $query->whereDate('created_at', '<=', $to_date);
        }
        if (!empty($invoice_no)) {
            $query->where('invoice_no', 'like', "%{$invoice_no}%");
        }
        if ($this->shouldApplyFilter($parties, $filters['totalParties'])) {
            $query->whereIn('customer_id', $parties);
        }
        if ($this->shouldApplyFilter($sales_officers, $filters['totalUsers'])) {
            $query->whereIn('created_by', $sales_officers);
        }
        if ($this->shouldApplyFilter($user_groups, $filters['totalGroups'])) {
            $query->where(function ($sub) use ($user_groups) {
                foreach ($user_groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }
        if ($this->shouldApplyFilter($party_types, $filters['totalPartyTypes'])) {
            $query->where(function ($pt) use ($party_types) {
                $customerTypes = array_values(array_intersect($party_types, ['Main Customer', 'Walking Customer']));
                if (in_array('Vendor', $party_types)) {
                    $pt->orWhere('partyType', 'vendor');
                }
                if (!empty($customerTypes)) {
                    $pt->orWhere(function ($sq) use ($customerTypes) {
                        $sq->where(function ($s2) {
                            $s2->whereIn('partyType', ['customer', 'walking'])
                                ->orWhereNull('partyType');
                        })->whereHas('customer', function ($c) use ($customerTypes) {
                            $c->whereIn('customer_type', $customerTypes);
                        });
                    });
                }
            });
        }
    }

    private function applyReturnHeaderFilters($query, array $filters): void
    {
        $from_date = $filters['from_date'];
        $to_date = $filters['to_date'];
        $invoice_no = $filters['invoice_no'];
        $parties = $filters['parties'];
        $party_types = $filters['party_types'];
        $sales_officers = $filters['sales_officers'];
        $user_groups = $filters['user_groups'];

        if (!empty($from_date)) {
            $query->whereDate('current_date', '>=', $from_date);
        }
        if (!empty($to_date)) {
            $query->whereDate('current_date', '<=', $to_date);
        }
        if (!empty($invoice_no)) {
            $query->where('invoice_no', 'like', "%{$invoice_no}%");
        }
        if ($this->shouldApplyFilter($parties, $filters['totalParties'])) {
            $query->whereIn('customer_id', $parties);
        }
        if ($this->shouldApplyFilter($sales_officers, $filters['totalUsers'])) {
            $query->whereIn('created_by', $sales_officers);
        }
        if ($this->shouldApplyFilter($user_groups, $filters['totalGroups'])) {
            $query->where(function ($sub) use ($user_groups) {
                foreach ($user_groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }
        if ($this->shouldApplyFilter($party_types, $filters['totalPartyTypes'])) {
            $query->where(function ($pt) use ($party_types) {
                $customerTypes = array_values(array_intersect($party_types, ['Main Customer', 'Walking Customer']));
                if (in_array('Vendor', $party_types)) {
                    $pt->orWhere('party_type', 'vendor');
                }
                if (!empty($customerTypes)) {
                    $pt->orWhere(function ($sq) use ($customerTypes) {
                        $sq->whereIn('party_type', ['customer', 'walking'])
                            ->whereHas('customer', function ($c) use ($customerTypes) {
                                $c->whereIn('customer_type', $customerTypes);
                            });
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

    private function fetchSaleLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = SaleItem::with([
            'sale.customer',
            'product.brandRelation',
            'product.sub_category_relation',
            'warehouse',
        ])->whereHas('sale', function ($q) use ($filters) {
            $this->applySaleHeaderFilters($q, $filters);
        });

        if ($this->shouldApplyFilter($filters['warehouses'], $filters['totalWarehouses'])) {
            $query->whereIn('warehouse_id', $filters['warehouses']);
        }

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function fetchReturnLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = SaleReturnItem::with([
            'saleReturn.customer',
            'product.brandRelation',
            'product.sub_category_relation',
            'warehouse',
        ])->whereHas('saleReturn', function ($q) use ($filters) {
            $this->applyReturnHeaderFilters($q, $filters);
        });

        if ($this->shouldApplyFilter($filters['warehouses'], $filters['totalWarehouses'])) {
            $query->whereIn('warehouse_id', $filters['warehouses']);
        }

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function wrapSaleLine(SaleItem $item, int $sign): object
    {
        if ($sign !== 1) {
            $item = clone $item;
            $item->sales_qty = $sign * (float) $item->sales_qty;
            $item->discount_amount = $sign * (float) ($item->discount_amount ?? 0);
            $item->amount = $sign * (float) ($item->amount ?? 0);
        }

        $item->setAttribute('entry_type', 'sale');
        $item->setAttribute('entry_type_label', 'Sale');

        return $item;
    }

    private function wrapReturnLine(SaleReturnItem $item, int $sign): object
    {
        $return = $item->saleReturn;
        $qty = (float) $item->sales_qty;
        $discountAmount = (float) ($item->discount_amount ?? 0);
        $amount = (float) ($item->amount ?? 0);
        $salesRate = $qty > 0
            ? ((float) $item->sales_price - ($discountAmount / $qty))
            : (float) $item->sales_price;

        $party = $return?->customer;
        $reportCustomer = $party;
        if ($return && $return->party_type === 'vendor' && $party) {
            $reportCustomer = (object) [
                'customer_name' => $party->name ?? 'N/A',
                'cnic' => $party->cnic ?? '',
                'filer_type' => $return->filer_type ?? 'Non Filer',
            ];
        }

        $returnDate = $return?->current_date ?? $return?->entry_date ?? now();

        $pseudoSale = (object) [
            'customer_id' => $return?->customer_id,
            'invoice_no' => $return?->invoice_no,
            'created_at' => $returnDate,
            'customer' => $reportCustomer,
            'partyType' => $return?->party_type,
        ];

        return (object) [
            'sale' => $pseudoSale,
            'product_id' => $item->product_id,
            'product' => $item->product,
            'warehouse_id' => $item->warehouse_id,
            'warehouse' => $item->warehouse,
            'sales_qty' => $sign * $qty,
            'retail_price' => $item->retail_price ?? 0,
            'sales_rate' => $salesRate,
            'sales_price' => $item->sales_price ?? 0,
            'discount_amount' => $sign * $discountAmount,
            'amount' => $sign * $amount,
            'entry_type' => 'sale_return',
            'entry_type_label' => 'Sale Return',
        ];
    }

    private function previewSaleVsList($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy('product_id');

        return view('admin_panel.reports.sales.preview_sale_vs_list', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewTaxSummary($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy(function ($item) {
            $type = $item->sale->customer?->filer_type ?? 'Non Filer';
            return ucwords(strtolower($type));
        })->map(function ($items) {
            return $items->groupBy(function ($item) {
                return $item->sale->customer_id;
            });
        });

        return view('admin_panel.reports.sales.preview_tax_summary', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewClaimRatio($saleItems, $from_date, $to_date)
    {
        $claims = \App\Models\CustomerClaim::whereDate('created_at', '>=', $from_date)
            ->whereDate('created_at', '<=', $to_date)
            ->get();

        $data = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        foreach ($months as $monthName) {
            $monthSales = $saleItems->filter(function ($si) use ($monthName) {
                return Carbon::parse($si->sale->created_at)->format('F') == $monthName;
            });

            $monthClaims = $claims->filter(function ($c) use ($monthName) {
                return Carbon::parse($c->created_at)->format('F') == $monthName;
            });

            if ($monthSales->isNotEmpty() || $monthClaims->isNotEmpty()) {
                $qty = $monthSales->sum('sales_qty');
                $retail_amt = $monthSales->sum(function ($si) {
                    return ($si->retail_price ?? 0) * $si->sales_qty;
                });
                $sales_amt = $monthSales->sum('amount');
                $claim_qty = $monthClaims->count();

                $data[$monthName] = [
                    'qty' => $qty,
                    'retail_amount' => $retail_amt,
                    'sales_amount' => $sales_amt,
                    'claim_qty' => $claim_qty,
                    'claim_percentage' => $qty > 0 ? ($claim_qty / $qty) * 100 : 0,
                ];
            }
        }

        return view('admin_panel.reports.sales.preview_claim_ratio', compact('data', 'from_date', 'to_date'));
    }

    private function previewQtyWise($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy(function ($item) {
            return $item->product && $item->product->brandRelation
                ? $item->product->brandRelation->name
                : 'Other';
        });

        return view('admin_panel.reports.sales.preview_qty_wise', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewPartyWise($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy(function ($item) {
            return $item->sale->customer_id;
        });

        return view('admin_panel.reports.sales.preview_party', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewItemWise($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy('product_id');

        return view('admin_panel.reports.sales.preview_item', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewInvoiceWise($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy(function ($item) {
            return $item->sale->customer_id;
        })->map(function ($items) {
            return $items->groupBy(function ($item) {
                return $item->sale->invoice_no;
            });
        });

        return view('admin_panel.reports.sales.preview_invoice', compact('grouped', 'from_date', 'to_date'));
    }
}
