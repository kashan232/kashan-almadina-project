<?php


namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Customer;
use App\Models\SubCustomer;
use App\Models\SalesOfficer;
use App\Models\OutstandingLoss;
use App\Models\SubCustomerLedger;
use App\Models\SubCustomerPayment; // ✅ Yahan top me, namespace ke saath
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubCustomerController extends Controller
{
    public function index()
    {
        $subCustomers = SubCustomer::with('mainCustomer')->latest()->get();
        return view('admin_panel.sub_customers.index', compact('subCustomers'));
    }

    public function create()
    {
                  $zones = Zone::all();
          $SalesOfficer = SalesOfficer::all();
        $mainCustomers = Customer::all();
        $latestId = 'SUB-' . str_pad(SubCustomer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        return view('admin_panel.sub_customers.create', compact('mainCustomers','latestId','SalesOfficer','zones'));
    }

    // public function create()
    // {
    //       $zones = Zone::all();
    //       $SalesOfficer = SalesOfficer::all();
    //     $latestId = 'CUST-' . str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
    //     return view('admin_panel.customers.create', compact('latestId','SalesOfficer','zones'));
    // }
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|unique:sub_customers',
            'customer_main_id' => 'required|exists:customers,id',
            'customer_name' => 'nullable',
            'customer_name_ur' => 'nullable',
            'cnic' => 'nullable',
            'filer_type' => 'nullable',
            'zone' => 'nullable',
            'customer_type' => 'nullable',
            'contact_person' => 'nullable',
            'mobile' => 'nullable',
            'email_address' => 'nullable|email',
            'contact_person_2' => 'nullable',
            'mobile_2' => 'nullable',
            'email_address_2' => 'nullable|email',
            'debit' => 'nullable|numeric',
            'credit' => 'nullable|numeric',
            'address' => 'nullable',
        ]);

        $subCustomer = SubCustomer::create($data);

        $debit = floatval($data['debit'] ?? 0);
        $credit = floatval($data['credit'] ?? 0);
        $closing = $debit - $credit;

        if ($debit > 0 || $credit > 0) {
            SubCustomerLedger::create([
                'sub_customer_id' => $subCustomer->id,
                'admin_or_user_id' => Auth::id(),
                'previous_balance' => 0,
                'closing_balance' => $closing,
            ]);
        }

        return redirect()->route('sub_customers.index')->with('success', 'SubCustomer created.');
    }

    public function toggleStatus($id)
    {
        $sub = SubCustomer::findOrFail($id);
        $balance = floatval($sub->debit) + floatval($sub->credit);

        if ($sub->status === 'active') {
            if ($balance > 0) {
                OutstandingLoss::create([
                    'sub_customer_id' => $sub->id,
                    'amount' => $balance,
                    'reason' => 'SubCustomer dues',
                ]);
            }
            $sub->status = 'inactive';
        } else {
            $sub->status = 'active';
        }

        $sub->save();
        return back()->with('success', 'SubCustomer status updated.');
    }

    public function edit($id)
    {
        $sub = SubCustomer::findOrFail($id);
        $mainCustomers = Customer::all();
        return view('admin_panel.sub_customers.edit', compact('sub', 'mainCustomers'));
    }

    public function update(Request $request, $id)
    {
        $sub = SubCustomer::findOrFail($id);

        $data = $request->except('_token');
        $sub->update($data);

        return redirect()->route('sub_customers.index')->with('success', 'SubCustomer updated.');
    }

    public function destroy($id)
    {
        $sub = SubCustomer::findOrFail($id);
        $sub->delete();
        return redirect()->route('sub_customers.index')->with('success', 'SubCustomer deleted.');
    }

    public function getLedger()
    {
        $userId = Auth::id();
        $ledgers = SubCustomerLedger::with('subCustomer')
            ->where('admin_or_user_id', $userId)
            ->get();

        return view('admin_panel.sub_customers.customer_ledger', compact('ledgers'));
    }

    public function getByType(Request $request)
    {
        $type = $request->get('type');

        $subCustomers = SubCustomer::where('customer_type', $type)->get(['id', 'customer_name']);
        return response()->json(['subCustomers' => $subCustomers]);
    }
    public function inactive()
{
    $subcustomers = SubCustomer::where('status', 'inactive')->latest()->get();
    return view('admin_panel.sub_customers.inactive', compact('subcustomers'));
}

public function payments()
{
    $payments = SubCustomerPayment::with('subCustomer')->latest()->get();
    $subCustomers = SubCustomer::where('status','active')->get();
    return view('admin_panel.sub_customers.customer_payments', compact('payments','subCustomers'));
}

public function storePayment(Request $request)
{
    $data = $request->validate([
        'sub_customer_id' => 'required|exists:sub_customers,id',
        'payment_date' => 'required|date',
        'amount' => 'required|numeric|min:0.01',
        'payment_method' => 'nullable|string|max:50',
        'note' => 'nullable|string',
    ]);

    $payment = SubCustomerPayment::create($data);

    // Update SubCustomer Ledger
    $sub = SubCustomer::find($data['sub_customer_id']);
    $previousClosing = SubCustomerLedger::where('sub_customer_id', $sub->id)->latest()->value('closing_balance') ?? 0;
    $closing = $previousClosing - $data['amount']; // Payment reduces balance

    SubCustomerLedger::create([
        'sub_customer_id' => $sub->id,
        'admin_or_user_id' => Auth::id(),
        'previous_balance' => $previousClosing,
        'closing_balance' => $closing,
        'description' => 'Payment Received',
    ]);

    return redirect()->route('sub_customers.payments')->with('success', 'Payment recorded successfully.');
}

}
