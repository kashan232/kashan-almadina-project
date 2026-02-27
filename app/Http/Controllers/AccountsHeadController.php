<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\PurchaseAccountAllocaations;
use Illuminate\Http\Request;

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
        $accounts = Account::with('head')->get();
        $heads = AccountHead::all();
        // Calculate next Head Code (ID)
        $nextHeadId = AccountHead::max('id') + 1;
        return view('admin_panel.chart_of_accounts', compact('accounts', 'heads', 'nextHeadId'));
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

    public function purcahse_account_allocation()
    {
        $histories = PurchaseAccountAllocaations::with(['head', 'account', 'purchase'])->get();

        return view('admin_panel.purcahse_account_allocation', compact('histories'));
    }

    public function storeHead(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        AccountHead::create(['name' => $request->name]);
        return redirect()->back()->with('success', 'Head added successfully.');
    }


    public function storeAccount(Request $request)
    {
        $request->validate([
            'head_id'        => 'required|exists:account_heads,id',
            'account_code'   => 'required|unique:accounts,account_code',
            'title'          => 'required|string|max:150',
            'opening_balance' => 'nullable|numeric',
            'status'         => 'nullable|in:on',
        ]);

        // Set status (1 = active, 0 = inactive)
        $status = $request->status === 'on' ? 1 : 0;

        Account::create([
            'head_id'         => $request->head_id,
            'account_code'    => $request->account_code,
            'title'           => $request->title,
            'opening_balance' => $request->opening_balance ?? 0,
            'status'          => $status,
        ]);

        return redirect()->back()->with('success', 'Account added successfully.');
    }

    
}
