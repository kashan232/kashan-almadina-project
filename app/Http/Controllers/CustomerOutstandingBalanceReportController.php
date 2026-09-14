<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Services\CustomerOutstandingBalanceReportBuilder;
use Illuminate\Http\Request;

class CustomerOutstandingBalanceReportController extends Controller
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

        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();

        return view('admin_panel.reports.customer_outstanding.index', compact('userGroups', 'customers', 'vendors'));
    }

    public function preview(Request $request, CustomerOutstandingBalanceReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:short,detailed',
        ]);

        $data = $builder->build($request);

        $view = $data['report_type'] === 'detailed'
            ? 'admin_panel.reports.customer_outstanding.preview_detailed'
            : 'admin_panel.reports.customer_outstanding.preview';

        return view($view, $data);
    }
}
