<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Services\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;
use App\Models\UserGroup;
use App\Models\User;

class VendorController extends Controller
{
    // VendorController.php aur WarehouseController.php same hoga
    public function index(Request $request)
    {
        $query = Vendor::with(['latestLedger', 'creator']);

        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        // Check if user is NOT an admin
        if (!$isAdmin) {
            $userId = Auth::id();
            $userGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->toArray();
            
            $query->where(function($q) use ($userId, $userGroupIds) {
                // Vendor created by the user
                $q->where('created_by', $userId);
                
                // OR Vendor belongs to user's group
                if (!empty($userGroupIds)) {
                    foreach ($userGroupIds as $groupId) {
                        $q->orWhereJsonContains('user_group_ids', (string)$groupId);
                    }
                }
            });
        } else {
            // Admin can filter by user
            if ($request->has('created_by') && $request->created_by != '') {
                $query->where('created_by', $request->created_by);
            }
        }

        $vendors = $query->withInactive()->withCount('purchases')->latest()->get();
        $userGroups = UserGroup::all();
        $users = User::all();

        return view('admin_panel.vendors.index', compact('vendors', 'userGroups', 'users', 'isAdmin'));
    }


    public function create()
    {
        $userGroups = UserGroup::all();
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        $nextVendorId = \App\Support\ModuleIdSequence::peekNextId(
            'vendors',
            \App\Support\ModuleIdSequence::VENDOR_MIN,
            \App\Support\ModuleIdSequence::VENDOR_MAX
        );

        return view('admin_panel.vendors.create', compact('userGroups', 'isAdmin', 'nextVendorId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'user_group_ids' => 'nullable|array',
        ]);

        $userId = Auth::id();

        // New vendor
        $vendor = Vendor::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => 'active',
            'opening_balance' => intval($request->opening_balance ?? 0),
            'user_group_ids' => $request->user_group_ids,
            'created_by' => $userId,
        ]);

        $opening = floatval($request->opening_balance ?? 0);
        app(PartyLedgerService::class)->postOpeningBalance('vendor', $vendor->id, $opening, $userId);

        return redirect('/vendor')->with('success', 'Vendor saved successfully');
    }

    public function edit($id)
    {
        $vendor = Vendor::withInactive()->findOrFail($id);
        $userGroups = UserGroup::all();
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        return view('admin_panel.vendors.edit', compact('vendor', 'userGroups', 'isAdmin'));
    }

    public function toggleStatus($id)
    {
        $vendor = Vendor::withInactive()->findOrFail($id);
        $vendor->status = $vendor->status === 'active' ? 'inactive' : 'active';
        $vendor->save();

        return redirect()->back()->with('success', 'Vendor status updated.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'user_group_ids' => 'nullable|array',
        ]);

        $vendor = Vendor::withInactive()->findOrFail($id);
        $vendor->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'opening_balance' => floatval($request->opening_balance ?? 0),
            'user_group_ids' => $request->user_group_ids,
        ]);

        // Sync ledger opening row and recalculate running balances.
        $opening = floatval($request->opening_balance ?? 0);
        app(PartyLedgerService::class)->syncOpeningBalance('vendor', $vendor->id, $opening, Auth::id());

        return redirect('/vendor')->with('success', 'Vendor updated successfully');
    }





    public function delete($id)
    {
        $vendor = Vendor::find($id);

        if (! $vendor) {
            return back()->with('error', 'Vendor not found.');
        }

        // Option 1: Simple existence check (counts only non-deleted purchases)
        $hasPurchases = DB::table('purchases')->where('vendor_id', $vendor->id)->exists();

        // Option 2: If Purchase model uses SoftDeletes and you want to check even soft-deleted purchases:
        // $hasPurchases = Purchase::withTrashed()->where('vendor_id', $vendor->id)->exists();

        if ($hasPurchases) {
            return back()->with('error', 'This vendor has purchase records and cannot be deleted.');
        }

        try {
            $vendor->delete(); // or forceDelete() if you really want to permanently delete
            return back()->with('success', 'Deleted Successfully');
        } catch (\Illuminate\Database\QueryException $e) {
            // foreign key constraint etc.
            \Log::error("Vendor delete failed: " . $e->getMessage());
            return back()->with('error', 'Cannot delete vendor due to related records.');
        } catch (\Throwable $e) {
            \Log::error("Vendor delete unexpected error: " . $e->getMessage());
            return back()->with('error', 'Something went wrong while deleting vendor.');
        }
    }

    public function allLedgers()
    {
        $ledgers = VendorLedger::with('vendor')->orderBy('date')->get();

        return view('admin_panel.vendors.ledger', compact('ledgers'));
    }
    public function payments_index()
    {
        $payments = VendorPayment::with('vendor')->latest()->get();
        $vendors = Vendor::all();
        return view('admin_panel.vendors.payments', compact('payments', 'vendors'));
    }
    public function payments_store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'amount' => 'required|numeric|min:0.01', // total amount (balance)
            'amount_paid' => 'required|numeric|min:0.01', // jo actually pay hua
            'payment_method' => 'nullable|string',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $userId = Auth::id();

        // Save in vendor_payments table
        $payment = VendorPayment::create([
            'vendor_id'        => $request->vendor_id,
            'admin_or_user_id' => $userId,
            'amount'           => $request->amount,
            'amount_paid'      => $request->amount_paid,
            'payment_method'   => $request->payment_method,
            'payment_date'     => $request->payment_date,
            'note'             => $request->note,
        ]);

        // Payment to vendor — debit party (reduces CR / increases DR side).
        app(PartyLedgerService::class)->append('vendor', (int) $request->vendor_id, [
            'date' => $request->payment_date,
            'description' => 'Vendor Payment' . ($request->note ? ': ' . $request->note : ''),
            'debit' => (float) $request->amount_paid,
            'credit' => 0,
            'admin_or_user_id' => $userId,
        ]);

        return back()->with('success', 'Payment to vendor recorded and ledger updated.');
    }



    // app/Http/Controllers/VendorController.php
    public function getClosingBalance($id)
    {
        $latestLedger = VendorLedger::where('vendor_id', $id)
            ->latest('id')
            ->first();

        if ($latestLedger) {
            return response()->json(['closing_balance' => $latestLedger->closing_balance]);
        } else {
            return response()->json(['closing_balance' => 0]);
        }
    }
}
