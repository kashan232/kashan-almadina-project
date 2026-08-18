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
use App\Support\ModuleIdSequence;

class AccountsHeadController extends Controller
{
    public function index()
    {
        $query = Account::withInactive()->with('head');
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
            if (request('created_by')) {
                $query->where('created_by', request('created_by'));
            }
        }

        $heads = AccountHead::withInactive()
            ->orderByRaw('CASE WHEN id >= ? THEN 0 ELSE 1 END', [ModuleIdSequence::ACCOUNT_HEAD_MIN])
            ->orderBy('id')
            ->get();
        $activeHeads = AccountHead::query()
            ->orderByRaw('CASE WHEN id >= ? THEN 0 ELSE 1 END', [ModuleIdSequence::ACCOUNT_HEAD_MIN])
            ->orderBy('id')
            ->get();
        $userGroups = UserGroup::all()->keyBy('id');
        $users = User::all();
        $nextHeadId = ModuleIdSequence::peekNextMainHeadId();

        $accounts = $query->orderBy('head_id')->orderBy('account_code')->get();

        return view('admin_panel.chart_of_accounts', compact(
            'accounts',
            'heads',
            'activeHeads',
            'nextHeadId',
            'isAdmin',
            'userGroups',
            'users'
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

            return redirect()->route('view_all')->with('success', $message);
        }

        $head = AccountHead::create([
            'name' => $request->name,
            'status' => $status,
        ]);
        $message = 'Head added successfully.';

        return redirect()->route('view_all')->with('success', $message);
    }


    public function storeAccount(Request $request)
    {
        if ($request->has('opening_balance')) {
            $rawOb = $request->input('opening_balance');
            $cleanOb = (is_null($rawOb) || $rawOb === '') ? 0 : (float) str_replace(',', '', (string) $rawOb);
            $request->merge(['opening_balance' => $cleanOb]);
        }

        $request->validate([
            'id'              => 'nullable|integer',
            'head_id'         => 'required|exists:account_heads,id',
            'title'           => 'required|string|max:150',
            'opening_balance' => 'nullable|numeric',
            'status'          => 'nullable|in:on',
            'user_group_ids'  => 'nullable|array',
        ]);


        $status = $request->status === 'on' ? 1 : 0;
        $groupIds = !empty($request->user_group_ids) ? array_values($request->user_group_ids) : null;

        // EDIT: an explicit id targets one existing account. Update that exact
        // record so assigning extra groups never spawns a duplicate account.
        if ($request->filled('id')) {
            $account = Account::withInactive()
                ->withoutGlobalScope(\App\Scopes\GroupIsolationScope::class)
                ->find($request->id);

            if (!$account) {
                return redirect()->route('view_all')->with('error', 'Account not found.');
            }

            $account->update([
                'head_id'         => $request->head_id,
                'title'           => $request->title,
                'opening_balance' => $request->opening_balance ?? 0,
                'status'          => $status,
                'user_group_ids'  => $groupIds,
            ]);

            return redirect()->route('view_all')->with('success', 'Account updated successfully.');
        }

        // CREATE: always mint a fresh, collision-free code server-side.
        $accountCode = Account::generateAccountCode((int) $request->head_id);

        Account::create([
            'head_id'         => $request->head_id,
            'account_code'    => $accountCode,
            'title'           => $request->title,
            'type'            => 'Debit',
            'opening_balance' => $request->opening_balance ?? 0,
            'status'          => $status,
            'user_group_ids'  => $groupIds,
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('view_all')->with('success', 'Account saved successfully.');
    }

    
}
