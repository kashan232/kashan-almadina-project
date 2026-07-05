<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\UserGroup;
use App\Services\CustomerOutstandingBalanceReportBuilder;
use Illuminate\Http\Request;

class CustomerOutstandingBalanceReportController extends Controller
{
    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $customers = Customer::orderBy('customer_name')->get();

        return view('admin_panel.reports.customer_outstanding.index', compact('userGroups', 'customers'));
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
