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
        $user = auth()->user();
        $isAdmin = !$user || $user->roles->pluck('name')->contains('Admin') || $user->id == 1;

        $userGroups = UserGroup::orderBy('group_name')
            ->when(!$isAdmin, function ($q) use ($user) {
                $groupIds = $user ? $user->userGroups()->pluck('user_groups.id')->toArray() : [];
                $q->whereIn('id', $groupIds);
            })
            ->get();

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
            'report_type' => 'required|in:party,item,detailed,hold_only,release_only',
        ]);

        $data = $builder->build($request);

        if ($request->report_type === 'detailed') {
            return view('admin_panel.reports.stock_hold_release.preview_detailed', $data);
        }

        if ($request->report_type === 'hold_only') {
            return view('admin_panel.reports.stock_hold_release.preview_hold_only', $data);
        }

        if ($request->report_type === 'release_only') {
            return view('admin_panel.reports.stock_hold_release.preview_release_only', $data);
        }

        return view('admin_panel.reports.stock_hold_release.preview', $data);
    }
}
