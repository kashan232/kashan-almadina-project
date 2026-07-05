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
use Illuminate\Support\Facades\DB;
use App\Models\UserGroup;
use App\Models\User;
use App\Services\CustomerImportService;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['customerLedger', 'creator']);
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        if (!$isAdmin) {
            $userId = Auth::id();
            $userGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->toArray();
            
            $query->where(function($q) use ($userId, $userGroupIds) {
                if (empty($userGroupIds)) {
                    // Customer created by the user
                    $q->where('created_by', $userId);
                } else {
                    // Customer belongs to user's group
                    $q->where(function($sub) use ($userGroupIds) {
                        foreach ($userGroupIds as $groupId) {
                            $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                            $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                        }
                    });
                }
            });
        } else {
            // Admin can filter by user
            if ($request->has('created_by') && $request->created_by != '') {
                $query->where('created_by', $request->created_by);
            }
        }

        $customers = $query->withCount('sales')->latest()->get();
        $userGroups = UserGroup::all()->keyBy('id');
        $users = User::all(); // To populate filter dropdown

        return view('admin_panel.customers.index', compact('customers', 'userGroups', 'users'));
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
        $userGroups = UserGroup::all();
        $latestId = (string) \App\Support\ModuleIdSequence::peekNextId(
            'customers',
            \App\Support\ModuleIdSequence::CUSTOMER_MAIN_MIN,
            \App\Support\ModuleIdSequence::CUSTOMER_MAIN_MAX
        );
        $nextWalkinId = (string) \App\Support\ModuleIdSequence::peekNextId(
            'customers',
            \App\Support\ModuleIdSequence::CUSTOMER_WALKIN_MIN,
            \App\Support\ModuleIdSequence::CUSTOMER_WALKIN_MAX
        );
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        return view('admin_panel.customers.create', compact('latestId', 'nextWalkinId', 'SalesOfficer', 'zones', 'userGroups', 'isAdmin'));
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
            'opening_balance' => 'nullable|numeric',
            'address' => 'nullable',
            'address_ur' => 'nullable',
            'transport_ur' => 'nullable',
            'customer_type' => 'nullable',
            'user_group_ids' => 'nullable|array',
        ]);

        $userGroupIds = $data['user_group_ids'] ?? null;
        unset($data['user_group_ids']);

        app(CustomerImportService::class)->createCustomer(
            $data,
            Auth::id(),
            $userGroupIds
        );

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function importForm()
    {
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;

        return view('admin_panel.customers.import', compact('isAdmin'));
    }

    public function downloadImportTemplate()
    {
        $filename = 'customer_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () {
            app(CustomerImportService::class)->downloadTemplate();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function processImport(Request $request, CustomerImportService $importService)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        $defaultUserGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->map(fn ($id) => (int) $id)->all();

        $path = $request->file('import_file')->getRealPath();
        $result = $importService->importFromPath($path, Auth::id(), $isAdmin, $defaultUserGroupIds);

        $message = $result['imported'] . ' customer(s) imported successfully.';
        if ($result['skipped'] > 0) {
            $message .= ' ' . $result['skipped'] . ' row(s) skipped.';
        }

        return redirect()
            ->route('customers.import')
            ->with('import_success', $message)
            ->with('import_errors', $result['errors']);
    }


    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $zones = Zone::all();
        $SalesOfficer = SalesOfficer::all();
        $userGroups = UserGroup::all();
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        return view('admin_panel.customers.edit', compact('customer', 'zones', 'SalesOfficer', 'userGroups', 'isAdmin'));
    }


    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Validate input. For customer_id, ignore unique check for this record.
        $data = $request->validate([
            'customer_id' => ['required', 'string', \Illuminate\Validation\Rule::unique('customers', 'customer_id')->ignore($customer->id)],
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
            'opening_balance' => 'nullable|numeric',
            'address' => 'nullable',
            'address_ur' => 'nullable',
            'transport_ur' => 'nullable',
            'customer_type' => 'nullable',
            'user_group_ids' => 'nullable|array',
        ]);

        // Update model
        $customer->update($data);

        // Update ledger if opening balance changed
        $openingBalance = floatval($request->opening_balance ?? 0);
        
        // Find the first ledger entry (usually the opening balance)
        $firstLedger = CustomerLedger::where('customer_id', $customer->id)
            ->orderBy('id', 'asc')
            ->first();

        if ($firstLedger) {
            // If it's a pure opening balance entry (no transactions yet or marked as such)
            // For now, let's just update the opening/closing if it's the first record.
            // Note: In a real system, you'd want to recalculate all subsequent entries.
            $firstLedger->update([
                'opening_balance' => $openingBalance,
                'closing_balance' => $openingBalance + ($firstLedger->debit ?? 0) - ($firstLedger->credit ?? 0) // Basic sync
            ]);
        } else if ($openingBalance > 0) {
            // Create first entry if none exists
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => Auth::id(),
                'opening_balance' => $openingBalance,
                'previous_balance' => 0,
                'closing_balance' => $openingBalance,
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }


    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return redirect()->route('customers.index')->with('error', 'Customer not found.');
        }

        // Option A: simple DB check (non-deleted sales)
        $hasSales = DB::table('sales')
            ->where('partyType', 'customer')
            ->where('customer_id', $customer->id)
            ->exists();

        // Option B: if you want to consider soft-deleted sales as well:
        // $hasSales = Sale::withTrashed()->where('partyType','customer')->where('customer_id', $customer->id)->exists();

        if ($hasSales) {
            return redirect()->route('customers.index')->with('error', 'This customer has sale records and cannot be deleted.');
        }

        try {
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Customer delete failed: ' . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Cannot delete customer due to related records.');
        } catch (\Throwable $e) {
            \Log::error('Customer delete unexpected error: ' . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Something went wrong while deleting customer.');
        }
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
