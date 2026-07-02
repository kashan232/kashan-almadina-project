<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
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
        // Extract filters
        $user_groups = $request->user_group ?? [];
        $sales_officers = $request->sales_officer ?? [];
        $warehouses = $request->warehouse ?? [];
        $categories = $request->category ?? [];
        $subcategories = $request->subcategory ?? [];
        $brands = $request->brand ?? [];
        $items = $request->item ?? [];
        $party_types = $request->party_type ?? [];
        $parties = $request->party ?? [];
        $invoice_no = $request->invoice_no;
        $report_type = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalPartyTypes = 3;
        $totalParties = Customer::count() + Vendor::count();
        $totalWarehouses = Warehouse::count() + 1;
        $totalCategories = Category::count();
        $totalSubcategories = Subcategory::count();
        $totalBrands = Brand::count();
        $totalProducts = Product::count();

        // Build Query
        $query = SaleItem::with(['sale.customer', 'product.brandRelation', 'warehouse'])
            ->whereHas('sale', function($q) use ($from_date, $to_date, $invoice_no, $parties, $party_types, $sales_officers, $user_groups, $totalGroups, $totalUsers, $totalPartyTypes, $totalParties) {
                $q->where('is_sale_order', 0);
                if (!empty($from_date)) {
                    $q->whereDate('created_at', '>=', $from_date);
                }
                if (!empty($to_date)) {
                    $q->whereDate('created_at', '<=', $to_date);
                }
                if (!empty($invoice_no)) {
                    $q->where('invoice_no', 'like', "%$invoice_no%");
                }
                if ($this->shouldApplyFilter($parties, $totalParties)) {
                    $q->whereIn('customer_id', $parties);
                }
                if ($this->shouldApplyFilter($sales_officers, $totalUsers)) {
                    $q->whereIn('created_by', $sales_officers);
                }
                if ($this->shouldApplyFilter($user_groups, $totalGroups)) {
                    $q->where(function ($sub) use ($user_groups) {
                        foreach ($user_groups as $gid) {
                            $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                                ->orWhereJsonContains('user_group_ids', (int) $gid);
                        }
                    });
                }
                if ($this->shouldApplyFilter($party_types, $totalPartyTypes)) {
                    $q->where(function ($pt) use ($party_types) {
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
            });

        // Apply Item level filters
        if ($this->shouldApplyFilter($warehouses, $totalWarehouses)) {
            $query->whereIn('warehouse_id', $warehouses);
        }
        if ($this->shouldApplyFilter($items, $totalProducts)) {
            $query->whereIn('product_id', $items);
        }
        if ($this->shouldApplyFilter($brands, $totalBrands) || $this->shouldApplyFilter($categories, $totalCategories) || $this->shouldApplyFilter($subcategories, $totalSubcategories)) {
            $query->whereHas('product', function($p) use ($brands, $categories, $subcategories, $totalBrands, $totalCategories, $totalSubcategories) {
                if ($this->shouldApplyFilter($brands, $totalBrands)) {
                    $p->whereIn('brand_id', $brands);
                }
                if ($this->shouldApplyFilter($categories, $totalCategories)) {
                    $p->whereIn('category_id', $categories);
                }
                if ($this->shouldApplyFilter($subcategories, $totalSubcategories)) {
                    $p->whereIn('sub_category_id', $subcategories);
                }
            });
        }

        $saleItems = $query->get();

        if ($report_type == 'Party Wise') {
            return $this->previewPartyWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Item Wise') {
            return $this->previewItemWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Invoice Wise') {
            return $this->previewInvoiceWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Claim Ratio') {
            return $this->previewClaimRatio($from_date, $to_date);
        } elseif ($report_type == 'Tax Summary') {
            return $this->previewTaxSummary($from_date, $to_date);
        } elseif ($report_type == 'Qty Wise') {
            return $this->previewQtyWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Sale vs List') {
            return $this->previewSaleVsList($from_date, $to_date);
        } else {
            return back()->with('error', 'Invalid Report Type Selected');
        }
    }

    private function previewSaleVsList($from_date, $to_date)
    {
        $saleItems = SaleItem::with(['sale.customer', 'product.brandRelation'])
            ->whereHas('sale', function($q) use ($from_date, $to_date) {
                $q->where('is_sale_order', 0);
                if ($from_date) $q->whereDate('created_at', '>=', $from_date);
                if ($to_date) $q->whereDate('created_at', '<=', $to_date);
            })->get();

        // Group by Item (Product)
        $grouped = $saleItems->groupBy('product_id');

        return view('admin_panel.reports.sales.preview_sale_vs_list', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewTaxSummary($from_date, $to_date)
    {
        $saleItems = SaleItem::with(['sale.customer', 'product.latestPrice'])
            ->whereHas('sale', function($q) use ($from_date, $to_date) {
                $q->where('is_sale_order', 0);
                if ($from_date) $q->whereDate('created_at', '>=', $from_date);
                if ($to_date) $q->whereDate('created_at', '<=', $to_date);
            })->get();

        // Group by Filer Type (Filer, Non Filer, Exempt)
        $grouped = $saleItems->groupBy(function($item) {
            $type = $item->sale->customer->filer_type ?? 'Non Filer';
            return ucwords(strtolower($type));
        })->map(function($items) {
            // Group by Customer ID
            return $items->groupBy(function($item) {
                return $item->sale->customer_id;
            });
        });

        return view('admin_panel.reports.sales.preview_tax_summary', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewClaimRatio($from_date, $to_date)
    {
        // 1. Fetch Sales Data grouped by Month
        $saleItems = SaleItem::with('sale')
            ->whereHas('sale', function($q) use ($from_date, $to_date) {
                $q->where('is_sale_order', 0);
                if ($from_date) $q->whereDate('created_at', '>=', $from_date);
                if ($to_date) $q->whereDate('created_at', '<=', $to_date);
            })->get();

        // 2. Fetch Claim Data grouped by Month
        $claims = \App\Models\CustomerClaim::whereDate('created_at', '>=', $from_date)
            ->whereDate('created_at', '<=', $to_date)
            ->get();

        // 3. Process Months
        $data = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        foreach($months as $monthName) {
            $monthSales = $saleItems->filter(function($si) use ($monthName) {
                return Carbon::parse($si->sale->created_at)->format('F') == $monthName;
            });
            
            $monthClaims = $claims->filter(function($c) use ($monthName) {
                return Carbon::parse($c->created_at)->format('F') == $monthName;
            });
            
            if ($monthSales->isNotEmpty() || $monthClaims->isNotEmpty()) {
                $qty = $monthSales->sum('sales_qty');
                $retail_amt = $monthSales->sum(function($si){ return ($si->retail_price ?? 0) * $si->sales_qty; });
                $sales_amt = $monthSales->sum('amount');
                $claim_qty = $monthClaims->count(); // Each record is 1 claim item
                
                $data[$monthName] = [
                    'qty' => $qty,
                    'retail_amount' => $retail_amt,
                    'sales_amount' => $sales_amt,
                    'claim_qty' => $claim_qty,
                    'claim_percentage' => $qty > 0 ? ($claim_qty / $qty) * 100 : 0
                ];
            }
        }

        return view('admin_panel.reports.sales.preview_claim_ratio', compact('data', 'from_date', 'to_date'));
    }

    private function previewQtyWise($saleItems, $from_date, $to_date)
    {
        // Group by Sub-Category (Product Sub-Category Relation)
        $grouped = $saleItems->groupBy(function($item) {
            return $item->product && $item->product->sub_category_relation ? $item->product->sub_category_relation->name : 'Other';
        });

        return view('admin_panel.reports.sales.preview_qty_wise', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewPartyWise($saleItems, $from_date, $to_date)
    {
        $grouped = $saleItems->groupBy(function($item) {
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
        // Group by Customer ID first, then by Invoice No
        $grouped = $saleItems->groupBy(function($item) {
            return $item->sale->customer_id;
        })->map(function($items) {
            return $items->groupBy(function($item) {
                return $item->sale->invoice_no;
            });
        });

        return view('admin_panel.reports.sales.preview_invoice', compact('grouped', 'from_date', 'to_date'));
    }
}
