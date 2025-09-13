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
        // dd( $accounts->toArray());
        $heads = AccountHead::all();

        return view('admin_panel.chart_of_accounts', compact('accounts', 'heads'));
    }

    public function storeHead(Request $request) {
        $request->validate(['name'=>'required|string|max:100']);
        AccountHead::create(['name'=>$request->name]);
        return redirect()->back()->with('success', 'Head added successfully.');
    }

   
   public function storeAccount(Request $request)
{
    // dd($request->toArray());
    // Validate the incoming request data
    $request->validate([
        'head_id' => 'required|exists:account_heads,id',
        'account_code' => 'required|unique:accounts,account_code',
        'title' => 'required|string|max:150',
        'debit' => 'nullable|numeric',
        'credit' => 'nullable|numeric',
        'status' => 'nullable|in:on',  // Allow only 'on' for status
        'opening_balance' => 'nullable|numeric', // Validate opening_balance
    ]);

    // Set the status to 1 if 'on' is checked, otherwise 0
    $status = $request->status === 'on' ? 1 : 0;

    // Set the opening balance or default to 0 if not provided
    $opening_balance = $request->opening_balance ?? 0;

    // Create the new account
    Account::create([
        'head_id' => $request->head_id,
        'account_code' => $request->account_code,
        'title' => $request->title,
        'debit' => $request->debit ?? 0,
        'credit' => $request->credit ?? 0,
        'status' => $status,  // Save the boolean value
        'opening_balance' => $opening_balance,  // Save the opening balance
    ]);

    // Redirect back with a success message
    return redirect()->back()->with('success', 'Account added successfully.');
}




}
