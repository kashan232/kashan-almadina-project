<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Product;

class PurchaseReportController extends Controller
{
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

        return view('admin_panel.reports.purchase.index', compact(
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
        $parties = $request->party ?? $request->vendor ?? [];
        $invoice_no = $request->invoice_no;
        $report_type = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        // Build Query
        $query = PurchaseItem::with(['purchase.vendor', 'purchase.purchasable', 'product.brandRelation', 'product.sub_category_relation', 'product.latestPrice'])
            ->whereHas('purchase', function($q) use ($from_date, $to_date, $invoice_no, $parties, $party_types, $sales_officers, $user_groups, $warehouses) {
                $q->where('status', 'Posted');
                if (!empty($from_date)) {
                    $q->whereDate('created_at', '>=', $from_date);
                }
                if (!empty($to_date)) {
                    $q->whereDate('created_at', '<=', $to_date);
                }
                if (!empty($invoice_no)) {
                    $q->where('invoice_no', 'like', "%$invoice_no%");
                }
                if (!empty($parties)) {
                    $q->where(function ($sub) use ($parties) {
                        $sub->whereIn('vendor_id', $parties)
                            ->orWhereIn('purchasable_id', $parties);
                    });
                }
                if (!empty($sales_officers)) {
                    $q->whereIn('created_by', $sales_officers);
                }
                if (!empty($user_groups)) {
                    $q->where(function ($sub) use ($user_groups) {
                        foreach ($user_groups as $gid) {
                            $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                                ->orWhereJsonContains('user_group_ids', (int) $gid);
                        }
                    });
                }
                if (!empty($warehouses)) {
                    $q->whereIn('warehouse_id', $warehouses);
                }
                if (!empty($party_types)) {
                    $q->where(function ($pt) use ($party_types) {
                        if (in_array('Vendor', $party_types)) {
                            $pt->orWhere('purchasable_type', Vendor::class);
                        }
                        if (in_array('Main Customer', $party_types)) {
                            $mainCustomerIds = Customer::where('customer_type', 'Main Customer')->pluck('id');
                            $pt->orWhere(function ($sq) use ($mainCustomerIds) {
                                $sq->where('purchasable_type', Customer::class)
                                    ->whereIn('purchasable_id', $mainCustomerIds);
                            });
                        }
                        if (in_array('Walking Customer', $party_types)) {
                            $walkinCustomerIds = Customer::where('customer_type', 'Walking Customer')->pluck('id');
                            $pt->orWhere(function ($sq) use ($walkinCustomerIds) {
                                $sq->where('purchasable_type', Customer::class)
                                    ->whereIn('purchasable_id', $walkinCustomerIds);
                            });
                        }
                    });
                }
            });

        // Apply Item level filters
        if (!empty($items)) {
            $query->whereIn('product_id', $items);
        }
        if (!empty($brands) || !empty($categories) || !empty($subcategories)) {
            $query->whereHas('product', function($p) use ($brands, $categories, $subcategories) {
                if (!empty($brands)) {
                    $p->whereIn('brand_id', $brands);
                }
                if (!empty($categories)) {
                    $p->whereIn('category_id', $categories);
                }
                if (!empty($subcategories)) {
                    $p->whereIn('sub_category_id', $subcategories);
                }
            });
        }

        $purchaseItems = $query->get();

        if ($report_type == 'Party Wise') {
            return $this->previewPartyWise($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Item Wise') {
            return $this->previewItemWise($purchaseItems, $from_date, $to_date);
        } elseif ($report_type == 'Invoice Wise') {
            return $this->previewInvoiceWise($purchaseItems, $from_date, $to_date);
        } else {
            return back()->with('error', 'Invalid Report Type Selected');
        }
    }

    private function previewPartyWise($purchaseItems, $from_date, $to_date)
    {
        $grouped = $purchaseItems->groupBy(function($item) {
            return $item->purchase->vendor_id;
        });

        return view('admin_panel.reports.purchase.preview_party', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewItemWise($purchaseItems, $from_date, $to_date)
    {
        $grouped = $purchaseItems->groupBy('product_id');

        return view('admin_panel.reports.purchase.preview_item', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewInvoiceWise($purchaseItems, $from_date, $to_date)
    {
        // Group by Vendor ID first, then by Invoice No
        $grouped = $purchaseItems->groupBy(function($item) {
            return $item->purchase->vendor_id;
        })->map(function($items) {
            return $items->groupBy(function($item) {
                return $item->purchase->invoice_no;
            });
        });

        return view('admin_panel.reports.purchase.preview_invoice', compact('grouped', 'from_date', 'to_date'));
    }
}
