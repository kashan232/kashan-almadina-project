<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Zone;
use App\Models\SalesOfficer;
use App\Models\OutstandingLoss; // make sure to import this
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerPayment;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('customerLedger')->latest()->get(); // Eager load the customer ledger relationship

        return view('admin_panel.customers.index', compact('customers'));
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        // dd($customer);
        // Check if changing to inactive
        if ($customer->status === 'active') {
            $balance = floatval($customer->debit) + floatval($customer->credit);

            if ($balance > 0) {
                // Save outstanding loss
                OutstandingLoss::create([
                    'customer_id' => $customer->id,
                    'amount' => $balance,
                    'reason' => 'dues ',
                ]);
            }

            $customer->status = 'inactive';
        } else {
            $customer->status = 'active';
        }

        $customer->save();
        return redirect()->back()->with('success', 'customer status updated.');
    }

    public function outstandingLosses()
    {
        $losses = \App\Models\OutstandingLoss::with('customer')->latest()->get();
        return view('admin_panel.customers.outstanding_losses', compact('losses'));
    }

    public function markInactive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = 'inactive';
        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer marked as inactive.');
    }

    public function inactiveCustomers()
    {
        $customers = Customer::where('status', 'inactive')->latest()->get();
        return view('admin_panel.customers.inactive', compact('customers'));
    }

    public function create()
    {
        $zones = Zone::all();
        $SalesOfficer = SalesOfficer::all();
        $latestId = 'CUST-' . str_pad(Customer::max('id') + 1, 1, STR_PAD_LEFT);
        //     $latestId = 'CUST-' . str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);

        return view('admin_panel.customers.create', compact('latestId', 'SalesOfficer', 'zones'));
    }

    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'customer_id' => 'required|unique:customers',
    //         'customer_name' => 'nullable',
    //         'customer_name_ur' => 'nullable',
    //         'cnic' => 'nullable',
    //         'filer_type' => 'nullable',
    //         'zone' => 'nullable',
    //         'customer_type' => 'nullable',
    //         'sales_oficer' => 'nullable',
    //         'contact_person' => 'nullable',
    //         'mobile' => 'nullable',
    //         'email_address' => 'nullable|email',
    //         'contact_person_2' => 'nullable',
    //         'mobile_2' => 'nullable',
    //         'email_address_2' => 'nullable|email',
    //         'debit' => 'nullable|numeric',
    //         'credit' => 'nullable|numeric',
    //         'address' => 'nullable',
    //     ]);

    //     Customer::create($data);

    //     return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    // }
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|unique:customers',
            'customer_name' => 'nullable',
            'customer_name_ur' => 'nullable',
            'cnic' => 'nullable',
            'filer_type' => 'nullable',
            'zone' => 'nullable',
            'contact_person' => 'nullable',
            'mobile' => 'nullable',
            'email_address' => 'nullable|email',
            'contact_person_2' => 'nullable',
            'mobile_2' => 'nullable',
            'email_address_2' => 'nullable|email',
            'opening_balance' => 'nullable|numeric', // Replace Debit and Credit with Opening Balance
            'address' => 'nullable',
            'customer_type' => 'nullable',
        ]);

        $customer = Customer::create($data);

        $openingBalance = floatval($request->opening_balance ?? 0);

        $userId = Auth::id();

        try {
            if ($openingBalance > 0) {
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'admin_or_user_id' => $userId,
                    'opening_balance' => $openingBalance,
                    'previous_balance' => 0,
                    'closing_balance' => $openingBalance, // Store opening balance as closing balance
                ]);
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to create customer ledger entry: ' . $e->getMessage());
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('admin_panel.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $data = $request->except('_token');

        $customer->update($data);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
    public function getByType(Request $request)
    {
        $type = $request->get('type');

        $customers = Customer::where('customer_type', $type)->get(['id', 'customer_name']);

        return response()->json(['customers' => $customers]);
    }
    public function customer_ledger()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $CustomerLedgers = CustomerLedger::with('customer')->where('admin_or_user_id', $userId)->get();
            return view('admin_panel.customers.customer_ledger', compact('CustomerLedgers'));
        } else {
            return redirect()->back();
        }
    }

    public function customer_payments()
    {
        $payments = CustomerPayment::with('customer')->orderByDesc('id')->get();
        $customers = Customer::all();
        return view('admin_panel.customers.customer_payments', compact('payments', 'customers'));
    }

    // Store a customer payment
    public function store_customer_payment(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // Save in payments table
        $payment = CustomerPayment::create([
            'customer_id' => $request->customer_id,
            'admin_or_user_id' => $userId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
        ]);

        // Get the latest ledger entry for the customer
        $previousLedger = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();
        // If a previous ledger exists, use its closing_balance; else, start from 0
        $prevBalance = $previousLedger ? $previousLedger->closing_balance : 0;

        // Prevent over-payment (optional)
        if ($request->amount > $prevBalance) {
            return back()->with('error', 'Amount exceeds available balance.');
        }

        // Calculate the new closing balance
        $newClosing = $prevBalance - $request->amount;

        // If a previous ledger exists, update it; otherwise, create a new one
        if ($previousLedger) {
            // Update the existing ledger entry with the new closing balance
            $previousLedger->update([
                'previous_balance' => $prevBalance,
                'closing_balance' => $newClosing,
            ]);
        } else {
            // Create a new ledger entry if no previous ledger exists
            CustomerLedger::create([
                'customer_id' => $request->customer_id,
                'admin_or_user_id' => $userId,
                'date' => $request->payment_date,
                'previous_balance' => 0, // Starting from zero since no previous ledger
                'closing_balance' => $request->amount, // New closing balance is the payment amount
            ]);
        }

        return back()->with('success', 'Payment to customer recorded and ledger updated.');
    }

    public function getClosingBalance($id)
    {
        // Get the latest ledger entry for the customer
        $ledger = CustomerLedger::where('customer_id', $id)->latest()->first();

        // Check if the ledger exists, if not return 0
        if ($ledger) {
            return response()->json([
                'closing_balance' => $ledger->closing_balance,
            ]);
        }

        // If no ledger entry is found, return a default closing balance of 0
        return response()->json(['closing_balance' => 0]);
    }
}

// customer payment start

// View all customer payments
