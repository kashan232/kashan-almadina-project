<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\PurchaseAccountAllocaations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        
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

        $heads = AccountHead::orderBy('id')->get();
        $userGroups = UserGroup::all();
        $users = User::all();
        $nextHeadId = \App\Support\ModuleIdSequence::peekNextId(
            'account_heads',
            \App\Support\ModuleIdSequence::ACCOUNT_HEAD_MIN,
            \App\Support\ModuleIdSequence::ACCOUNT_HEAD_MAX
        );

        $selectedHeadId = request('head_id');
        if ($selectedHeadId === null && $heads->isNotEmpty()) {
            $selectedHeadId = (string) $heads->first()->id;
        }

        if ($selectedHeadId !== null && $selectedHeadId !== '') {
            $query->where('head_id', $selectedHeadId);
        }

        $accounts = $query->orderBy('account_code')->get();
        $selectedHead = $heads->firstWhere('id', (int) $selectedHeadId);

        return view('admin_panel.chart_of_accounts', compact(
            'accounts',
            'heads',
            'nextHeadId',
            'isAdmin',
            'userGroups',
            'users',
            'selectedHeadId',
            'selectedHead'
        ));
    }

    public function getNextAccountCode($headId)
    {
        return response()->json([
            'code' => Account::generateAccountCode((int) $headId),
        ]);
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

        if ($request->filled('head_id')) {
            AccountHead::where('id', $request->head_id)->update([
                'name' => $request->name,
                'status' => $status,
            ]);
            $message = 'Head updated successfully.';
        } else {
            AccountHead::create([
                'name' => $request->name,
                'status' => $status,
            ]);
            $message = 'Head added successfully.';
        }

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

        $existingInScope = Account::where('account_code', $request->account_code)->first();
        $accountCode = $request->account_code;

        if (!$existingInScope) {
            $accountCode = Account::generateAccountCode((int) $request->head_id);
        } elseif (DB::table('accounts')->where('account_code', $accountCode)->where('id', '!=', $existingInScope->id)->exists()) {
            return redirect()->back()->with('error', 'Account code already exists. Please refresh and try again.');
        }

        Account::updateOrCreate(
            ['account_code' => $accountCode],
            [
                'head_id'         => $request->head_id,
                'title'           => $request->title,
                'opening_balance' => $request->opening_balance ?? 0,
                'status'          => $status,
                'user_group_ids'  => $request->user_group_ids,
                'created_by'      => $existingInScope ? $existingInScope->created_by : Auth::id(),
            ]
        );

        return redirect()->back()->with('success', 'Account saved successfully.');
    }

    
}
