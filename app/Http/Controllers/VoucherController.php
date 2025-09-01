<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Models\Narration;

class VoucherController extends Controller
{
 public function index($type)
{

    // Sirf selected type ka data laa lo
    $vouchers = Voucher::where('voucher_type', $type)->latest()->get();
        $narration = Narration::where('expense_head',$type)->get();

    return view('admin_panel.accounts.expenses', [
        'vouchers' => $vouchers,
        'type' => $type,
        'narration'=>$narration
    ]);
}


public function store(Request $request)
{
    // Validate that arrays are present and match in length
    $request->validate([
        'date' => 'required|array',
        'type' => 'required|array',
        'person' => 'required|array',
        'narration' => 'required|array',
        'amount' => 'required|array',
    ]);

    // Loop through each row and create a voucher
    foreach ($request->date as $index => $date) {
        Voucher::create([
            'voucher_type' => $request->sub_head,
            'sales_officer' => auth()->user()->name,
            'date' => $date,
            'type' => $request->type[$index],
            'person' => $request->person[$index],
            'sub_head' => $request->sub_head[$index] ?? null,
            'narration' => $request->narration[$index],
            'amount' => $request->amount[$index],
            'status' => 'draft',
        ]);
    }

    return back()->with('success', 'Vouchers saved successfully!');
}


    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        //
    }
public function receipt($id)
{
    $voucher = Voucher::findOrFail($id);

    $customerName = $voucher->person; // Default
    $customerAddress = '-';
    $closingBalance = 0;

    if ($voucher->type === 'Main Customer' && $voucher->mainCustomer) {
        $customerName = $voucher->mainCustomer->customer_name;
        $customerAddress = $voucher->mainCustomer->address;
        $closingBalance = $voucher->mainCustomer->closing_balance;
    } elseif ($voucher->type === 'Sub Customer' && $voucher->subCustomer) {
        $customerName = $voucher->subCustomer->customer_name;
        $customerAddress = $voucher->subCustomer->address;
        $closingBalance = $voucher->subCustomer->closing_balance;
    }

    return view('admin_panel.accounts.receipt', compact('voucher', 'customerName', 'customerAddress', 'closingBalance'));
}




    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        //
    }
}
