<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Services\DailyReportBuilder;
use Illuminate\Http\Request;

class DailyReportController extends Controller
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
        $accounts = Account::with('accountHead')->orderBy('account_code')->get();

        return view('admin_panel.reports.daily_activity.index', compact('userGroups', 'customers', 'vendors', 'accounts'));
    }

    public function preview(Request $request, DailyReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $data = $builder->build($request);

        return view('admin_panel.reports.daily_activity.preview', $data);
    }
}
