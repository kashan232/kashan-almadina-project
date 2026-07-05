<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\StockHoldReleaseReportBuilder;
use Illuminate\Http\Request;

class StockHoldReleaseReportController extends Controller
{
    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $products = Product::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.stock_hold_release.index', compact(
            'userGroups',
            'warehouses',
            'vendors',
            'customers',
            'products',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request, StockHoldReleaseReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:party,item',
        ]);

        $data = $builder->build($request);

        return view('admin_panel.reports.stock_hold_release.preview', $data);
    }
}
