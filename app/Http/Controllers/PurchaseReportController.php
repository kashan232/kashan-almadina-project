<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Product;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    public function index()
    {
        $vendors = Vendor::all();
        $warehouses = Warehouse::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $brands = Brand::all();
        $products = Product::all();

        return view('admin_panel.reports.purchase.index', compact(
            'vendors', 'warehouses', 'categories', 'subcategories', 'brands', 'products'
        ));
    }

    public function preview(Request $request)
    {
        // Extract filters
        $vendors = $request->vendor ?? [];
        $warehouses = $request->warehouse ?? [];
        $categories = $request->category ?? [];
        $subcategories = $request->subcategory ?? [];
        $brands = $request->brand ?? [];
        $items = $request->item ?? [];
        $invoice_no = $request->invoice_no;
        $report_type = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        // Build Query
        $query = PurchaseItem::with(['purchase.vendor', 'product.brandRelation', 'product.sub_category_relation', 'product.latestPrice'])
            ->whereHas('purchase', function($q) use ($from_date, $to_date, $invoice_no, $vendors) {
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
                if (!empty($vendors)) {
                    $q->whereIn('vendor_id', $vendors);
                }
            });

        // Apply Item level filters
        if (!empty($warehouses)) {
            $query->whereHas('purchase', function($q) use ($warehouses) {
                $q->whereIn('warehouse_id', $warehouses);
            });
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
