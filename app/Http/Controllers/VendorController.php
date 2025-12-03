<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    // VendorController.php aur WarehouseController.php same hoga
    public function index()
    {
        // eager load latestLedger and also load purchases_count for delete logic
        $vendors = Vendor::with('latestLedger')->withCount('purchases')->get();

        return view('admin_panel.vendors.index', compact('vendors'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $userId = Auth::id();

        if ($request->id) {
            // Update vendor
            $vendor = Vendor::findOrFail($request->id);
            $vendor->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'opening_balance' => intval($request->opening_balance ?? 0),
            ]);

            // Yahan agar edit ke waqt ledger bhi update karna hai to extra logic dalna hoga
        } else {
            // New vendor
            $vendor = Vendor::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'opening_balance' => intval($request->opening_balance ?? 0),
            ]);

            $opening = intval($request->opening_balance ?? 0);
            VendorLedger::create([
                'vendor_id'        => $vendor->id,
                'admin_or_user_id' => $userId,
                'date'             => now(),
                'description'      => 'Opening Balance',
                'opening_balance'  => $opening,
                'previous_balance' => 0,
                'closing_balance'  => $opening,
            ]);
        }

        return back()->with('success', 'Vendor saved successfully');
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

        // Get the latest ledger entry for the vendor
        $previousLedger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();

        // Agar ledger pehle se hai to uska closing_balance use karenge
        $prevBalance = $previousLedger ? $previousLedger->closing_balance : 0;

        // Over-payment check
        if ($request->amount_paid > $prevBalance) {
            return back()->with('error', 'Paid amount exceeds available balance.');
        }

        // Naya closing balance calculate hoga
        $newClosing = $prevBalance - $request->amount_paid;

        if ($previousLedger) {
            // Sirf update hoga
            $previousLedger->update([
                'previous_balance' => $prevBalance,
                'closing_balance'  => $newClosing,
            ]);
        } else {
            // Sirf pehli dafa create hoga (aur isme amount_paid nahi jayega)
            VendorLedger::create([
                'vendor_id'        => $request->vendor_id,
                'admin_or_user_id' => $userId,
                'date'             => $request->payment_date,
                'description'      => 'Opening Balance',
                'previous_balance' => 0,
                'closing_balance'  => $request->amount, // Opening balance ke equal
            ]);
        }

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
