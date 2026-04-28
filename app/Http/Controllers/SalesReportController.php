<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Zone;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index()
    {
        $zones = Zone::all();
        $users = User::all();
        $warehouses = Warehouse::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $brands = Brand::all();
        $products = Product::all();
        $customers = Customer::all();

        return view('admin_panel.reports.sales.index', compact(
            'zones', 'users', 'warehouses', 'categories', 'subcategories', 'brands', 'products', 'customers'
        ));
    }

    public function preview(Request $request)
    {
        // Extract filters
        $zones = $request->zone ?? [];
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

        // Build Query
        $query = SaleItem::with(['sale.customer', 'product.brandRelation', 'warehouse'])
            ->whereHas('sale', function($q) use ($from_date, $to_date, $invoice_no, $parties, $zones, $party_types) {
                if ($from_date) {
                    $q->whereDate('created_at', '>=', $from_date);
                }
                if ($to_date) {
                    $q->whereDate('created_at', '<=', $to_date);
                }
                if ($invoice_no) {
                    $q->where('invoice_no', 'like', "%$invoice_no%");
                }
                if (!empty($parties)) {
                    $q->whereIn('customer_id', $parties);
                }
                if (!empty($zones) || !empty($party_types)) {
                    $q->whereHas('customer', function($c) use ($zones, $party_types) {
                        if (!empty($zones)) {
                            $c->whereIn('zone', $zones);
                        }
                        if (!empty($party_types)) {
                            $c->whereIn('customer_type', $party_types);
                        }
                    });
                }
            });

        // Apply Item level filters
        if (!empty($warehouses)) {
            $query->whereIn('warehouse_id', $warehouses);
        }
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

        $saleItems = $query->get();

        if ($report_type == 'Party Wise') {
            return $this->previewPartyWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Item Wise') {
            return $this->previewItemWise($saleItems, $from_date, $to_date);
        } elseif ($report_type == 'Invoice Wise') {
            return $this->previewInvoiceWise($saleItems, $from_date, $to_date);
        } else {
            return back()->with('error', 'Invalid Report Type Selected');
        }
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
