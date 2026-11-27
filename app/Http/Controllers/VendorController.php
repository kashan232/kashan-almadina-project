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
use App\Rules\GlobalUniqueName;

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
                        $q->orWhereJsonContains('user_group_ids', (string)$groupId)
                          ->orWhereJsonContains('user_group_ids', (int)$groupId);
                    }
                }
            });
        } else {
            // Admin can filter by user
            if ($request->has('created_by') && $request->created_by != '') {
                $query->where('created_by', $request->created_by);
            }
        }

        if ($request->filled('group_id')) {
            $groupId = $request->group_id;
            $query->where(function($q) use ($groupId) {
                $q->whereJsonContains('user_group_ids', (string) $groupId)
                  ->orWhereJsonContains('user_group_ids', (int) $groupId);
            });
        }

        $vendors = $query->withInactive()->withCount('purchases')->latest()->get();
        $userGroups = UserGroup::all();
        $users = User::all();

        return view('admin_panel.vendors.index', compact('vendors', 'userGroups', 'users', 'isAdmin'));
    }

    public function auditIndex(Request $request)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $startDate = $request->start_date ?? '2020-01-01';
        $endDate = $request->end_date ?? date('Y-m-d');

        $query = Vendor::with(['creator']);
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
        if (!$isAdmin) {
            $userId = Auth::id();
            $userGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->toArray();
            $query->where(function($q) use ($userId, $userGroupIds) {
                if (empty($userGroupIds)) {
                    $q->where('created_by', $userId);
                } else {
                    $q->where(function($sub) use ($userGroupIds) {
                        foreach ($userGroupIds as $groupId) {
                            $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                            $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                        }
                    });
                }
            });
        } else {
            if ($request->has('created_by') && $request->created_by != '') {
                $query->where('created_by', $request->created_by);
            }
        }

        if ($request->filled('group_id')) {
            $groupId = $request->group_id;
            $query->where(function($q) use ($groupId) {
                $q->whereJsonContains('user_group_ids', (string) $groupId)
                  ->orWhereJsonContains('user_group_ids', (int) $groupId);
            });
        }

        if ($request->filled('vendor_name')) {
            $query->where('name', 'like', '%' . $request->vendor_name . '%');
        }

        $allVendors = $query->withInactive()->latest()->get();
        $userGroups = UserGroup::all();
        $users = User::all();
        $ledgerController = app(\App\Http\Controllers\GeneralLedgerController::class);

        // Fetch IDs of vendors with any transaction activity in 1 fast UNION query
        $activeVendorIds = DB::query()
            ->select('id')
            ->from(function ($q) {
                $q->select('vendor_id as id')->from('purchases')
                    ->union(DB::table('purchase_returns')->select('vendor_id as id'))
                    ->union(DB::table('receipts_vouchers')->select('party_id as id'))
                    ->union(DB::table('payment_vouchers')->select('party_id as id'))
                    ->union(DB::table('journal_vouchers')->select('party_id as id'))
                    ->union(DB::table('expense_vouchers')->select('party_id as id'))
                    ->union(DB::table('claim_item_receipts')->select('party_id as id'))
                    ->union(DB::table('claim_credit_notes')->select('party_id as id'))
                    ->union(DB::table('vendor_ledgers')->select('vendor_id as id'));
            }, 'active_v')
            ->whereNotNull('id')
            ->pluck('id')
            ->flip()
            ->toArray();

        $auditRows = [];
        foreach ($allVendors as $vendor) {
            $partyId = (int)$vendor->id;
            $hasOpening = (float) ($vendor->opening_balance ?? 0) != 0.0;
            $hasActivity = isset($activeVendorIds[$partyId]);

            if (!$hasOpening && !$hasActivity) {
                $auditRows[] = [
                    'id' => $vendor->id,
                    'vendor_id' => $vendor->vendor_id,
                    'name' => $vendor->name,
                    'opening' => 0.0,
                    'sales' => 0.0,
                    'c_rep' => 0.0,
                    's_ret' => 0.0,
                    'cir' => 0.0,
                    'purchases' => 0.0,
                    'pur_ret' => 0.0,
                    'clm_cn' => 0.0,
                    'receipts' => 0.0,
                    'payments' => 0.0,
                    'income' => 0.0,
                    'exp_dis' => 0.0,
                    'jv_dr' => 0.0,
                    'jv_cr' => 0.0,
                    'av_dr' => 0.0,
                    'av_cr' => 0.0,
                    'calc_balance' => 0.0,
                    'saved_balance' => 0.0,
                    'gl_balance' => 0.0,
                    'diff' => 0.0,
                ];
                continue;
            }

            $opening = (float)$ledgerController->calculateOpeningBalance('vendor', $partyId, $startDate);
            $txns = $ledgerController->fetchTransactions('vendor', $partyId, $startDate, $endDate);

            $txnDebits = 0.0;
            $txnCredits = 0.0;

            $purchases = 0.0;
            $purRet = 0.0;
            $payments = 0.0;
            $receipts = 0.0;
            $income = 0.0;
            $sRet = 0.0;
            $sales = 0.0;
            $cRep = 0.0;
            $cir = 0.0;
            $clmCn = 0.0;
            $expDis = 0.0;
            $jvDr = 0.0;
            $jvCr = 0.0;
            $avDr = 0.0;
            $avCr = 0.0;

            foreach ($txns as $t) {
                $debit = (float)($t['debit'] ?? 0);
                $credit = (float)($t['credit'] ?? 0);
                $txnDebits += $debit;
                $txnCredits += $credit;

                $ref = strtoupper((string)($t['ref'] ?? ''));
                $desc = strtolower((string)($t['desc'] ?? ''));

                if ($ref === 'PJ') {
                    if ($credit > 0) $purchases += $credit;
                    if ($debit > 0) $purchases -= $debit;
                } elseif ($ref === 'PRJ') {
                    if ($debit > 0) $purRet += $debit;
                    if ($credit > 0) $purchases += $credit;
                } elseif ($ref === 'PV') {
                    if ($debit > 0) $payments += $debit;
                    if ($credit > 0) $payments -= $credit;
                } elseif ($ref === 'RV') {
                    if ($credit > 0) {
                        if (str_contains($desc, 'discount')) $expDis += $credit;
                        else $receipts += $credit;
                    }
                    if ($debit > 0) $payments += $debit;
                } elseif ($ref === 'IV') {
                    if ($credit > 0) $income += $credit;
                    if ($debit > 0) $income -= $debit;
                } elseif (in_array($ref, ['SRJ', 'SR'], true)) {
                    if ($credit > 0) $sRet += $credit;
                    if ($debit > 0) {
                        if (str_contains($desc, 'discount')) $expDis -= $debit;
                        else $sRet -= $debit;
                    }
                } elseif ($ref === 'SJ') {
                    if ($debit > 0) $sales += $debit;
                    if ($credit > 0) {
                        if (str_contains($desc, 'discount')) $expDis += $credit;
                        else $receipts += $credit;
                    }
                } elseif ($ref === 'CLM') {
                    if ($debit > 0) $cRep += $debit;
                    if ($credit > 0) $clmCn += $credit;
                } elseif ($ref === 'CIR') {
                    if ($debit > 0) $cir += $debit;
                    if ($credit > 0) $cir -= $credit;
                } elseif ($ref === 'EV' || $ref === 'VO') {
                    if ($credit > 0) $expDis += $credit;
                    if ($debit > 0) $payments += $debit;
                } elseif ($ref === 'JV') {
                    if ($debit > 0) $jvDr += $debit;
                    if ($credit > 0) $jvCr += $credit;
                } elseif ($ref === 'AV') {
                    if ($debit > 0) $avDr += $debit;
                    if ($credit > 0) $avCr += $credit;
                } else {
                    if ($debit > 0) $jvDr += $debit;
                    if ($credit > 0) $jvCr += $credit;
                }
            }

            // GL-aligned formula (DR positive): Opening + DR Additions - CR Deductions
            $calculatedTrueBalance = $opening + $sales + $payments + $cRep + $jvDr + $avDr + $cir + $purRet
                - ($purchases + $sRet + $clmCn + $receipts + $income + $expDis + $jvCr + $avCr);

            $glBalance = $opening + $txnDebits - $txnCredits;

            $latestLedger = VendorLedger::where('vendor_id', $vendor->id)->latest('id')->first();
            $savedBalance = $latestLedger ? (float)$latestLedger->closing_balance : (float)($vendor->closing_balance ?? 0.0);

            $diff = $calculatedTrueBalance - $glBalance;

            $auditRows[] = [
                'id' => $vendor->id,
                'vendor_id' => $vendor->vendor_id,
                'name' => $vendor->name,
                'opening' => $opening,
                'sales' => $sales,
                'c_rep' => $cRep,
                's_ret' => $sRet,
                'cir' => $cir,
                'purchases' => $purchases,
                'pur_ret' => $purRet,
                'clm_cn' => $clmCn,
                'receipts' => $receipts,
                'payments' => $payments,
                'income' => $income,
                'exp_dis' => $expDis,
                'jv_dr' => $jvDr,
                'jv_cr' => $jvCr,
                'av_dr' => $avDr,
                'av_cr' => $avCr,
                'calc_balance' => $calculatedTrueBalance,
                'saved_balance' => $savedBalance,
                'gl_balance' => $glBalance,
                'diff' => $diff,
            ];
        }

        return view('admin_panel.vendors.audit', compact('auditRows', 'startDate', 'endDate', 'userGroups', 'users', 'isAdmin'));
    }

    public function syncLedgers(Request $request)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $gl = app(\App\Http\Controllers\GeneralLedgerController::class);
        $vendors = Vendor::withInactive()->get();

        $syncedCount = 0;
        foreach ($vendors as $v) {
            $trueBal = round((float)$gl->calculateOpeningBalance('vendor', $v->id, '2099-12-31'), 2);
            $latestLedger = VendorLedger::where('vendor_id', $v->id)->latest('id')->first();

            if (!$latestLedger) {
                if ($trueBal != 0.0) {
                    VendorLedger::create([
                        'vendor_id' => $v->id,
                        'admin_or_user_id' => Auth::id() ?? 1,
                        'date' => date('Y-m-d'),
                        'description' => 'Ledger Auto-Sync Baseline',
                        'opening_balance' => (float)($v->opening_balance ?? 0),
                        'previous_balance' => (float)($v->opening_balance ?? 0),
                        'debit' => $trueBal > 0 ? $trueBal : 0,
                        'credit' => $trueBal < 0 ? abs($trueBal) : 0,
                        'closing_balance' => $trueBal,
                    ]);
                    $syncedCount++;
                }
            } else {
                $stored = round((float)$latestLedger->closing_balance, 2);
                if (abs($trueBal - $stored) > 0.01) {
                    $latestLedger->closing_balance = $trueBal;
                    $latestLedger->save();
                    $syncedCount++;
                }
            }

            if (round((float)($v->closing_balance ?? 0), 2) !== $trueBal) {
                $v->closing_balance = $trueBal;
                $v->save();
            }
        }

        return back()->with('success', "All vendor ledgers synced with General Ledger! ({$syncedCount} records updated)");
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
        if ($request->has('opening_balance')) {
            $rawOb = $request->input('opening_balance');
            $cleanOb = (is_null($rawOb) || $rawOb === '') ? 0 : (float) str_replace(',', '', (string) $rawOb);
            $request->merge(['opening_balance' => $cleanOb]);
        }

        $request->validate([
            'name' => ['required', 'string', new GlobalUniqueName('vendors')],
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
        if ($request->has('opening_balance')) {
            $rawOb = $request->input('opening_balance');
            $cleanOb = (is_null($rawOb) || $rawOb === '') ? 0 : (float) str_replace(',', '', (string) $rawOb);
            $request->merge(['opening_balance' => $cleanOb]);
        }

        $request->validate([
            'name' => ['required', 'string', new GlobalUniqueName('vendors', $id)],
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
