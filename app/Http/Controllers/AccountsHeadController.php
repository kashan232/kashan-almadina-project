<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use Illuminate\Http\Request;

class AccountsHeadController extends Controller
{
    // public function index (){
    //     return view('admin_panel.chart_of_accounts',);
    // }
    // public function narration (){
    //     return view('admin_panel.accounts.narration',);
    // }

    public function index() {
        $accounts = Account::with('head')->get();
        $heads = AccountHead::all();
        return view('admin_panel.chart_of_accounts', compact('accounts', 'heads'));
    }

    public function storeHead(Request $request) {
        $request->validate(['name'=>'required|string|max:100']);
        AccountHead::create(['name'=>$request->name]);
        return redirect()->back()->with('success', 'Head added successfully.');
    }

    public function storeAccount(Request $request) {
        $request->validate([
            'head_id'=>'required|exists:account_heads,id',
            'account_code'=>'required|unique:accounts,account_code',
            'title'=>'required|string|max:150',
            'debit'=>'nullable|numeric',
            'credit'=>'nullable|numeric'
        ]);
        Account::create([
            'head_id'=>$request->head_id,
            'account_code'=>$request->account_code,
            'title'=>$request->title,
            'debit'=>$request->debit ?? 0,
            'credit'=>$request->credit ?? 0,
            'status'=>$request->status ?? 1
        ]);
        return redirect()->back()->with('success', 'Account added successfully.');
    }

}
