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
    $customers = Customer::latest()->get(); // no status filter
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

        return view('admin_panel.customers.create', compact('latestId','SalesOfficer','zones'));
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
        'debit' => 'nullable|numeric',
        'credit' => 'nullable|numeric',
        'address' => 'nullable',
        'customer_type' => 'nullable',
    ]);

    $customer = Customer::create($data);

    $debit = floatval($request->debit ?? 0);
    $credit = floatval($request->credit ?? 0);
    $closingBalance = $debit - $credit;

    $userId = Auth::id();

    try {
        if ($debit > 0 || $credit > 0) {
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => $userId,
                // 'date' => now(),
                // 'description' => 'Opening Balance',
                // 'debit' => $debit,
                // 'credit' => $credit,
                'previous_balance' => 0,
                'closing_balance' => $closingBalance,
            ]);
        }
    } catch (\Exception $e) {
        dd('Ledger error: ' . $e->getMessage());
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

    $customers = Customer::where('customer_type',$type)->get(['id', 'customer_name']);

    return response()->json(['customers' => $customers]);
}
public function customer_ledger()
{
    if (Auth::check()) {
        $userId = Auth::id();
        $CustomerLedgers = CustomerLedger::with('customer')
            ->where('admin_or_user_id', $userId)
            ->get();

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

    // Ledger update
    $previous = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();
    $prevBalance = $previous->closing_balance ?? 0;

    // Prevent over-payment (optional)
    if ($request->amount > $prevBalance) {
        return back()->with('error', 'Amount exceeds available balance.');
    }

    $newClosing = $prevBalance - $request->amount;

    CustomerLedger::create([
        'customer_id' => $request->customer_id,
        'admin_or_user_id' => $userId,
        'date' => $request->payment_date,
        'description' => 'Payment to Customer',
        'debit' => 0,
        'credit' => $request->amount,
        'previous_balance' => $prevBalance,
        'closing_balance' => $newClosing,
    ]);

    return back()->with('success', 'Payment to customer recorded and ledger updated.');
}
}


// customer payment start


// View all customer payments


