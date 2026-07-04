<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\UserGroup;
use App\Models\Warehouse;
use App\Services\StockReportBuilder;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function index()
    {
        return $this->filterView('admin_panel.reports.stock.index', [
            'pageTitle' => 'Stock Report Filters',
            'previewRoute' => route('reports.stock.preview'),
            'fixedReportType' => 'summary',
        ]);
    }

    public function ledgerIndex()
    {
        return $this->filterView('admin_panel.reports.stock.index', [
            'pageTitle' => 'Item Stock Ledger Filters',
            'previewRoute' => route('reports.stock-ledger.preview'),
            'fixedReportType' => 'ledger',
        ]);
    }

    public function preview(Request $request, StockReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:summary,ledger',
        ]);

        $data = $builder->build($request);

        return view('admin_panel.reports.stock.preview', $data);
    }

    public function ledgerPreview(Request $request, StockReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'item' => 'required|array|min:1',
            'item.*' => 'integer|exists:products,id',
        ], [
            'item.required' => 'Please select at least one Item for the Item Stock Ledger.',
            'item.min' => 'Please select at least one Item for the Item Stock Ledger.',
        ]);

        $data = $builder->buildLedger($request);

        return view('admin_panel.reports.stock.preview_ledger', $data);
    }

    private function filterView(string $view, array $extra = [])
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $categories = Category::orderBy('name')->get();
        $subcategories = Subcategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view($view, array_merge(compact(
            'userGroups',
            'warehouses',
            'categories',
            'subcategories',
            'brands',
            'products',
            'shopGroupIds'
        ), $extra));
    }
}
