<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\PurchaseAccountAllocaations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserGroup;
use App\Models\User;

class AccountsHeadController extends Controller
{
    // public function index (){
    //     return view('admin_panel.chart_of_accounts',);
    // }
    // public function narration (){
    //     return view('admin_panel.accounts.narration',);
    // }

    public function index()
    {
        $query = Account::with('head');
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::user()->usertype == 'admin';
        
        if (!$isAdmin) {
            $userId = Auth::id();
            $userGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->toArray();
            
            $query->where(function($q) use ($userId, $userGroupIds) {
                $q->where('created_by', $userId);
                if (!empty($userGroupIds)) {
                    foreach ($userGroupIds as $groupId) {
                        $q->orWhereJsonContains('user_group_ids', (string)$groupId);
                    }
                }
             });
        } else {
            // Admin can filter by user
            if (request('created_by')) {
                $query->where('created_by', request('created_by'));
            }
        }

        $accounts = $query->get();
        $heads = AccountHead::all();
        $userGroups = UserGroup::all();
        $users = User::all(); // To populate filter for Admin
        // Calculate next Head Code (ID)
        $nextHeadId = AccountHead::max('id') + 1;
        return view('admin_panel.chart_of_accounts', compact('accounts', 'heads', 'nextHeadId', 'isAdmin', 'userGroups', 'users'));
    }

    public function getNextAccountCode($headId)
    {
        $lastAccount = Account::where('head_id', $headId)->orderBy('id', 'desc')->first();

        if ($lastAccount && is_numeric($lastAccount->account_code)) {
            $nextCode = $lastAccount->account_code + 1;
        } else {
            // Default format: HeadID + 001
            $nextCode = $headId . '001';
        }

        return response()->json(['code' => $nextCode]);
    }

    public function purcahse_account_allocation(Request $request)
    {
        $query = PurchaseAccountAllocaations::with(['head', 'account', 'purchase']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $histories = $query->latest()->get();

        return view('admin_panel.purcahse_account_allocation', compact('histories'));
    }

    public function storeHead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'head_id' => 'nullable|exists:account_heads,id',
            'status' => 'nullable|in:on',
        ]);

        $status = $request->status === 'on' ? 1 : 0;

        AccountHead::updateOrCreate(
            ['id' => $request->head_id],
            [
                'name' => $request->name,
                'status' => $status,
            ]
        );

        $message = $request->head_id ? 'Head updated successfully.' : 'Head added successfully.';
        return redirect()->back()->with('success', $message);
    }


    public function storeAccount(Request $request)
    {
        $request->validate([
            'head_id'        => 'required|exists:account_heads,id',
            'account_code'   => 'required',
            'title'          => 'required|string|max:150',
            'opening_balance' => 'nullable|numeric',
            'status'         => 'nullable|in:on',
            'user_group_ids' => 'nullable|array',
        ]);

        // Support both creating and updating in one method or just create
        // Actually the form currently only does store.
        
        $status = $request->status === 'on' ? 1 : 0;

        Account::updateOrCreate(
            ['account_code' => $request->account_code],
            [
                'head_id'         => $request->head_id,
                'title'           => $request->title,
                'opening_balance' => $request->opening_balance ?? 0,
                'status'          => $status,
                'user_group_ids'  => $request->user_group_ids,
                'created_by'      => Auth::id(),
            ]
        );

        return redirect()->back()->with('success', 'Account saved successfully.');
    }

    
}
