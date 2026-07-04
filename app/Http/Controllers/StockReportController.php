<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\UserGroup;
use App\Models\Warehouse;
use App\Services\StockReportBuilder;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.stock.index', compact(
            'userGroups',
            'warehouses',
            'categories',
            'brands',
            'products',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request, StockReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $data = $builder->build($request);

        return view('admin_panel.reports.stock.preview', $data);
    }
}
