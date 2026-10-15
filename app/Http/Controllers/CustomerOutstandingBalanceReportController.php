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
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        if (!$request->has('from_date')) {
            $request->merge([
                'from_date' => date('Y-m-01'),
                'to_date' => date('Y-m-d'),
                'report_type' => $request->input('report_type', 'short'),
            ]);
        }

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:short,detailed,brand_wise',
        ]);

        $data = $builder->build($request);

        $view = match ($data['report_type']) {
            'detailed' => 'admin_panel.reports.customer_outstanding.preview_detailed',
            'brand_wise' => 'admin_panel.reports.customer_outstanding.preview_brand_wise',
            default => 'admin_panel.reports.customer_outstanding.preview',
        };

        return view($view, $data);
    }
}
