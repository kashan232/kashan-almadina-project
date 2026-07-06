<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\CustomerLedger;
use App\Models\ExpenseVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Models\Narration;
use App\Models\PaymentVoucher;
use App\Models\ReceiptsVoucher;
use App\Models\VendorLedger;
use App\Services\PartyLedgerService;
use App\Models\IncomeVoucher;
use App\Models\AdjustmentVoucher;
use App\Models\JournalVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    private function getDateColumn($table, $fallback = 'DATE(created_at)')
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'entry_date')) {
                $cache[$table] = "COALESCE(entry_date, $fallback)";
            } else {
                $cache[$table] = $fallback;
            }
        }
        return $cache[$table];
    }

    private function sumVoucherDiscounts(object $voucher): float
    {
        $discountList = json_decode($voucher->discount_value, true);
        $total = 0;
        if (is_array($discountList)) {
            foreach ($discountList as $disc) {
                $total += (float) $disc;
            }
        }

        return $total;
    }

    private function adjustVoucherDiscountAccounts(object $voucher, bool $isReceipt, bool $reverse = false): void
    {
        $discAccIds = json_decode($voucher->discount_account_id, true) ?? [];
        $discValues = json_decode($voucher->discount_value, true) ?? [];
        $sign = $reverse ? -1 : 1;
        if (!$isReceipt) {
            $sign *= -1;
        }

        foreach ($discAccIds as $idx => $accId) {
            $disc = (float) ($discValues[$idx] ?? 0);
            if ($disc <= 0 || !$accId) {
                continue;
            }

            $acc = Account::find($accId);
            if ($acc) {
                $acc->opening_balance = (float) ($acc->opening_balance ?? 0) + ($sign * $disc);
                $acc->save();
            }
        }
    }

    private function voucherDiscountFieldsFromRequest(Request $request): array
    {
        return [
            'discount_value' => json_encode($request->input('discount_value', [])),
            'discount_head' => json_encode($request->input('discount_head', [])),
            'discount_account_id' => json_encode($request->input('discount_account_id', [])),
        ];
    }

    private function validateReceiptPaymentRequest(Request $request, string $partyTypeField, string $partyIdField, bool $withDiscount = true): void
    {
        $rules = [
            'entry_date'   => 'required|date',
            'entry_time'   => 'required',
            $partyTypeField => 'required',
            $partyIdField   => 'required',
        ];

        $messages = [
            'entry_date.required' => 'Entry Date is required.',
            'entry_time.required' => 'Entry Time is required.',
            $partyTypeField . '.required' => 'Party Type is required.',
            $partyIdField . '.required'   => 'Party is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($v) use ($request, $withDiscount) {
            $amounts = $request->input('amount', []);
            if (!is_array($amounts)) {
                $amounts = [];
            }

            $hasValidRow = false;

            foreach ($amounts as $idx => $rawAmt) {
                $amt = (float) str_replace(',', '', (string) $rawAmt);
                $disc = (float) ($request->input('discount_value', [])[$idx] ?? 0);

                if ($amt <= 0 && $disc <= 0) {
                    continue;
                }

                $hasValidRow = true;

                if (empty($request->input('row_account_head', [])[$idx] ?? null)) {
                    $v->errors()->add("row_account_head.$idx", 'Account Head is required.');
                }
                if (empty($request->input('row_account_id', [])[$idx] ?? null)) {
                    $v->errors()->add("row_account_id.$idx", 'Destination Account is required.');
                }
                if ($amt <= 0 && $disc <= 0) {
                    $v->errors()->add("amount.$idx", 'Amount is required.');
                }

                if ($withDiscount && $disc > 0) {
                    if (empty($request->input('discount_head', [])[$idx] ?? null)) {
                        $v->errors()->add("discount_head.$idx", 'Discount Head is required.');
                    }
                    if (empty($request->input('discount_account_id', [])[$idx] ?? null)) {
                        $v->errors()->add("discount_account_id.$idx", 'Discount Sub Head is required.');
                    }
                }
            }

            if (!$hasValidRow) {
                $v->errors()->add('amount.0', 'At least one line with amount or discount is required.');
            }
        });

        $validator->validate();
    }

    private function validateExpenseRequest(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'entry_date'  => 'required|date',
            'vendor_type' => 'required',
            'vendor_id'   => 'required',
        ], [
            'entry_date.required'  => 'Entry Date is required.',
            'vendor_type.required' => 'Party Type is required.',
            'vendor_id.required'   => 'Party is required.',
        ]);

        $validator->after(function ($v) use ($request) {
            $amounts = $request->input('amount', []);
            if (!is_array($amounts)) {
                $amounts = [];
            }

            $hasValidRow = false;

            foreach ($amounts as $idx => $rawAmt) {
                $amt = (float) str_replace(',', '', (string) $rawAmt);
                if ($amt <= 0) {
                    continue;
                }

                $hasValidRow = true;

                if (empty($request->input('row_account_head', [])[$idx] ?? null)) {
                    $v->errors()->add("row_account_head.$idx", 'Account Head is required.');
                }
                if (empty($request->input('row_account_id', [])[$idx] ?? null)) {
                    $v->errors()->add("row_account_id.$idx", 'Destination Account is required.');
                }
            }

            if (!$hasValidRow) {
                $v->errors()->add('amount.0', 'At least one line with amount is required.');
            }
        });

        $validator->validate();
    }

    private function validateIncomeRequest(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'entry_date'   => 'required|date',
            'account_head' => 'required',
            'account_id'   => 'required',
        ], [
            'entry_date.required'   => 'Entry Date is required.',
            'account_head.required' => 'Main Account Head is required.',
            'account_id.required'   => 'Account is required.',
        ]);

        $validator->after(function ($v) use ($request) {
            $amounts = $request->input('amount', []);
            if (!is_array($amounts)) {
                $amounts = [];
            }

            $hasValidRow = false;

            foreach ($amounts as $idx => $rawAmt) {
                $amt = (float) str_replace(',', '', (string) $rawAmt);
                if ($amt <= 0) {
                    continue;
                }

                $hasValidRow = true;

                if (empty($request->input('party_type', [])[$idx] ?? null)) {
                    $v->errors()->add("party_type.$idx", 'Party Type is required.');
                }
                if (empty($request->input('party_id', [])[$idx] ?? null)) {
                    $v->errors()->add("party_id.$idx", 'Party is required.');
                }
            }

            if (!$hasValidRow) {
                $v->errors()->add('amount.0', 'At least one line with amount is required.');
            }
        });

        $validator->validate();
    }

    public function index($type)
    {

        // Sirf selected type ka data laa lo
        $vouchers = Voucher::where('voucher_type', $type)->latest()->get();
        $narration = Narration::where('expense_head', $type)->get();

        return view('admin_panel.accounts.expenses', [
            'vouchers' => $vouchers,
            'type' => $type,
            'narration' => $narration
        ]);
    }


    public function store(Request $request)
    {
        // Validate that arrays are present and match in length
        $request->validate([
            'date' => 'required',
            'type' => 'required',
            'person' => 'required',
            'narration' => 'required',
            'amount' => 'required',
        ]);

        // Loop through each row and create a voucher
        foreach ($request->date as $index => $date) {
            Voucher::create([
                'voucher_type' => $request->sub_head,
                'sales_officer' => auth()->user()->name,
                'entry_date' => $request->entry_date[$index] ?? now()->toDateString(),
                'entry_time' => $request->entry_time[$index] ?? now()->toTimeString(),
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

        //yahan accounts bhi show karwayn all heads 
        // bank cash  
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

    public function all_recepit_vochers(Request $request)
    {
        $query = \App\Models\ReceiptsVoucher::query()->standalone();

        $dateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        if ($request->filled('start_date')) {
            $query->whereDate(DB::raw($dateCol), '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate(DB::raw($dateCol), '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            // Mapping for draft/posted
            $query->where('status', $request->status);
        }

        $receipts = $query->orderBy('id', 'DESC')->get();

        foreach ($receipts as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            // 🧩 Check if type is numeric → account-based
            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            // Attach new properties to the object
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.all_recepit_vochers', compact('receipts'));
    }


    public function print($id)
    {
        $voucher = ReceiptsVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // Rows build
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ If type is numeric → means from Account Head
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            // ✅ If vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If walkin
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }







    public function recepit_vochers($id = null)
    {
        if (!$id) {
            // No ID passed, so we show a fresh "Add" page.
            // We create a "temp" object to represent the next possible RVID
            $receipt = new ReceiptsVoucher([
                'rvid' => ReceiptsVoucher::generateInvoiceNo(),
                'status' => 'draft',
                'receipt_date' => now()->toDateString(),
                'entry_date' => now()->toDateString(),
            ]);
        } else {
            $receipt = ReceiptsVoucher::findOrFail($id);
            if ($receipt->isSaleLinked()) {
                return redirect()->route('all-recepit-vochers')
                    ->with('error', 'This receipt belongs to a Sale and is managed from the Sale screen.');
            }
        }

        $narrations = \App\Models\Narration::where('expense_head', 'Receipts Voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        return view('admin_panel.vochers.reciepts_vouchers', compact('narrations', 'AccountHeads', 'receipt'));
    }

    public function ajax_save_receipt(Request $request)
    {
        $this->validateReceiptPaymentRequest($request, 'vendor_type', 'vendor_id', true);

        try {
            $id = $request->input('id');
            
            if ($id) {
                $voucher = ReceiptsVoucher::findOrFail($id);
                if ($voucher->status === 'posted') {
                    return response()->json(['success' => false, 'message' => 'Cannot edit a posted voucher.']);
                }
            } else {
                $voucher = ReceiptsVoucher::create([
                    'rvid'       => ReceiptsVoucher::generateInvoiceNo(),
                    'entry_date' => $request->entry_date ?? now()->toDateString(),
                    'entry_time' => $request->entry_time ?? now()->toTimeString(),
                    'status'     => 'draft'
                ]);
            }

            $narrationIds = [];
            $nIds = $request->input('narration_id', []);
            
            foreach ($nIds as $index => $narrId) {
                if (empty($narrId)) {
                    $narrationIds[] = "";
                    continue;
                }
                
                // 🧩 If narrId is NOT numeric, it means user typed a new narration (Select2 Tag)
                if (!is_numeric($narrId)) {
                    $existing = \App\Models\Narration::where('narration', $narrId)
                        ->where('expense_head', 'Receipts Voucher')
                        ->first();
                    
                    if ($existing) {
                        $narrationIds[] = (string)$existing->id;
                    } else {
                        $new = \App\Models\Narration::create([
                            'expense_head' => 'Receipts Voucher',
                            'narration'    => $narrId,
                        ]);
                        $narrationIds[] = (string)$new->id;
                    }
                } else {
                    $narrationIds[] = (string)$narrId;
                }
            }

            $voucher->update([
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'entry_time'       => $request->entry_time,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,
                'narration_id'     => json_encode($narrationIds),
                'reference_no'     => json_encode($request->input('reference_no', [])),
                'row_account_head' => json_encode($request->input('row_account_head', [])),
                'row_account_id'   => json_encode($request->input('row_account_id', [])),
                'discount_value'   => json_encode($request->input('discount_value', [])),
                'discount_head'    => json_encode($request->input('discount_head', [])),
                'discount_account_id' => json_encode($request->input('discount_account_id', [])),
                'kg'               => json_encode($request->input('kg', [])),
                'rate'               => json_encode($request->input('rate', [])),
                'amount'           => json_encode($request->input('amount', [])),
                'total_amount'     => (float) str_replace(',', '', $request->total_amount),
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Draft saved successfully!', 
                'id' => $voucher->id, 
                'rvid' => $voucher->rvid
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function post_receipt(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $voucher = ReceiptsVoucher::findOrFail($id);
            if ($voucher->status === 'posted') {
                return back()->with('error', 'Already posted.');
            }

            // Perform ledger updates
            $amount = (float)$voucher->total_amount;
            $rvid = $voucher->rvid;

            $discountList = json_decode($voucher->discount_value, true);
            $totalDiscount = $this->sumVoucherDiscounts($voucher);
            $totalCreditAmount = $amount + $totalDiscount;

            $partyLedger = app(PartyLedgerService::class);
            $rvDate = $voucher->entry_date ?? now()->toDateString();

            if (in_array($voucher->type, ['vendor', 'customer', 'walkin'], true)) {
                $partyLedger->postReceiptCredit(
                    $voucher->type,
                    (int) $voucher->party_id,
                    $totalCreditAmount,
                    $rvDate,
                    "Receipt Voucher #$rvid"
                );
            } else {
                $account = Account::find($voucher->party_id);
                if ($account) {
                    $account->opening_balance = (float)($account->opening_balance ?? 0) - $totalCreditAmount;
                    $account->save();
                }
            }

            // Update row accounts (Sources) -> PLUS
            $rowAccountIds = json_decode($voucher->row_account_id, true);
            $amountsList = json_decode($voucher->amount, true);
            
            if (is_array($rowAccountIds) && is_array($amountsList)) {
                foreach ($rowAccountIds as $index => $accId) {
                    $rowAmount = (float)($amountsList[$index] ?? 0);
                    if ($rowAmount > 0 && $accId) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = (float)($rowAccount->opening_balance ?? 0) + $rowAmount;
                            $rowAccount->save();
                        }
                    }
                }
            }

            $this->adjustVoucherDiscountAccounts($voucher, true, false);

            $voucher->status = 'posted';
            $voucher->save();

            DB::commit();
            return redirect()->route('all-recepit-vochers')->with('success', 'Voucher posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function unpost_receipt($id)
    {
        DB::beginTransaction();
        try {
            $voucher = ReceiptsVoucher::findOrFail($id);
            if ($voucher->status !== 'posted') {
                return back()->with('error', 'Only posted vouchers can be unposted.');
            }

            $amount = (float)$voucher->total_amount;
            $totalDiscount = $this->sumVoucherDiscounts($voucher);
            $totalCreditAmount = $amount + $totalDiscount;
            
            $partyLedger = app(PartyLedgerService::class);
            $rvDate = $voucher->entry_date ?? now()->toDateString();

            if (in_array($voucher->type, ['vendor', 'customer', 'walkin'], true)) {
                $partyLedger->appendReversal(
                    $voucher->type,
                    (int) $voucher->party_id,
                    0,
                    $totalCreditAmount,
                    $rvDate,
                    "Unpost Receipt Voucher #{$voucher->rvid}"
                );
            } else {
                $account = Account::find($voucher->party_id);
                if ($account) {
                    $account->opening_balance = (float)($account->opening_balance ?? 0) + $totalCreditAmount;
                    $account->save();
                }
            }

            // 2. Reverse row accounts (Sources) -> Subtract back
            $rowAccountIds = json_decode($voucher->row_account_id, true);
            $amountsList = json_decode($voucher->amount, true);
            if (is_array($rowAccountIds) && is_array($amountsList)) {
                foreach ($rowAccountIds as $index => $accId) {
                    $rowAmount = (float)($amountsList[$index] ?? 0);
                    if ($rowAmount > 0 && $accId) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = (float)($rowAccount->opening_balance ?? 0) - $rowAmount;
                            $rowAccount->save();
                        }
                    }
                }
            }

            $this->adjustVoucherDiscountAccounts($voucher, true, true);

            $voucher->status = 'draft';
            $voucher->save();

            DB::commit();
            return back()->with('success', 'Voucher unposted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function store_rec_vochers(Request $request)
    {
        // Fallback for non-ajax
        return $this->post_receipt($request, $request->id);
    }
    
    public function cancel_receipt($id)
    {
        $voucher = ReceiptsVoucher::findOrFail($id);
        if ($voucher->status === 'posted') {
            return back()->with('error', 'Cannot cancel a posted voucher.');
        }
        $voucher->delete();
        return redirect()->route('all-recepit-vochers')->with('success', 'Voucher cancelled.');
    }

    public function Payment_vochers($id = null)
    {
        $narrations = \App\Models\Narration::where('expense_head', 'Payment voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::get();

        if ($id) {
            $receipt = PaymentVoucher::findOrFail($id);
            $nextPVID = $receipt->pvid;
        } else {
            $receipt = new PaymentVoucher([
                'pvid' => PaymentVoucher::generateInvoiceNo(),
                'status' => 'draft',
                'entry_date' => now()->toDateString(),
                'receipt_date' => now()->toDateString(),
            ]);
            $nextPVID = $receipt->pvid;
        }

        return view('admin_panel.vochers.payment_vochers.payment_vouchers', compact('narrations', 'AccountHeads', 'nextPVID', 'receipt'));
    }

    public function ajax_save_payment(Request $request)
    {
        $this->validateReceiptPaymentRequest($request, 'party_type', 'party_id', true);

        $id = $request->id;
        $narrationIds = [];
        $nIds = $request->input('narration_id', []);

        foreach ($nIds as $narrId) {
            if (empty($narrId)) {
                $narrationIds[] = "";
                continue;
            }
            if (!is_numeric($narrId)) {
                $new = \App\Models\Narration::firstOrCreate(
                    ['narration' => $narrId, 'expense_head' => 'Payment voucher']
                );
                $narrationIds[] = (string)$new->id;
            } else {
                $narrationIds[] = (string)$narrId;
            }
        }

        $data = [
            'receipt_date'     => $request->receipt_date,
            'entry_date'       => $request->entry_date,
            'entry_time'       => $request->entry_time,
            'type'             => $request->party_type, // Single from Header
            'party_id'         => $request->party_id,   // Single from Header
            'tel'              => $request->tel,
            'remarks'          => $request->remarks,
            'narration_id'     => json_encode($narrationIds),
            'reference_no'     => json_encode($request->reference_no),
            'row_account_head' => json_encode($request->row_account_head), // JSON from Rows
            'row_account_id'   => json_encode($request->row_account_id),   // JSON from Rows
            'discount_value'   => json_encode($request->discount_value),
            'discount_head'    => json_encode($request->input('discount_head', [])),
            'discount_account_id' => json_encode($request->input('discount_account_id', [])),
            'kg'               => json_encode($request->kg),
            'rate'             => json_encode($request->rate),
            'amount'           => json_encode($request->amount),
            'total_amount'     => str_replace(',', '', $request->total_amount),
        ];

        if ($id) {
            $voucher = PaymentVoucher::find($id);
            $voucher->update($data);
        } else {
            $data['pvid'] = PaymentVoucher::generateInvoiceNo();
            $data['status'] = 'draft';
            $voucher = PaymentVoucher::create($data);
        }

        return response()->json(['success' => true, 'id' => $voucher->id, 'pvid' => $voucher->pvid]);
    }

    public function post_payment($id)
    {
        $voucher = PaymentVoucher::findOrFail($id);
        if ($voucher->status == 'posted') return back()->with('error', 'Already posted.');

        DB::beginTransaction();
        try {
            $voucher->status = 'posted';
            $voucher->save();

            $totalAmount = (float)$voucher->total_amount;
            $pvid = $voucher->pvid;
            $discountList = json_decode($voucher->discount_value, true) ?? [];

            // Multiple Accounts (Sources) -> MINUS
            $accIds = json_decode($voucher->row_account_id, true);
            $rowAmounts = json_decode($voucher->amount, true);
            
            if (is_array($accIds)) {
                foreach ($accIds as $index => $accId) {
                    $rowAmount = (float)($rowAmounts[$index] ?? 0);
                    if ($accId && $rowAmount > 0) {
                        $acc = Account::find($accId);
                        if ($acc) {
                            $acc->opening_balance -= $rowAmount;
                            $acc->save();
                        }
                    }
                }
            }

            $this->adjustVoucherDiscountAccounts($voucher, false, false);

            // One Party (Destination) -> PLUS (amount + discount per row)
            $partyId = $voucher->party_id;
            $pType = $voucher->type;

            $partyLedger = app(PartyLedgerService::class);
            $pvDate = $voucher->entry_date ?? now()->toDateString();

            if ($partyId && $pType && is_array($rowAmounts)) {
                foreach ($rowAmounts as $index => $rowAmount) {
                    $rowAmount = (float) $rowAmount;
                    $rowDiscount = (float) ($discountList[$index] ?? 0);
                    $partyImpact = $rowAmount + $rowDiscount;
                    if ($partyImpact <= 0) {
                        continue;
                    }

                    if (in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                        $partyLedger->postPaymentDebit(
                            $pType,
                            (int) $partyId,
                            $partyImpact,
                            $pvDate,
                            "Payment Voucher #$pvid"
                        );
                    } else {
                        $partyAcc = Account::find($partyId);
                        if ($partyAcc) {
                            $partyAcc->opening_balance += $partyImpact;
                            $partyAcc->save();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('Payment-vochers')->with('success', 'Voucher posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function unpost_payment($id)
    {
        $voucher = PaymentVoucher::findOrFail($id);
        if ($voucher->status != 'posted') return back()->with('error', 'Not posted.');

        DB::beginTransaction();
        try {
            $voucher->status = 'draft';
            $voucher->save();

            $totalAmount = (float)$voucher->total_amount;
            $totalDiscount = $this->sumVoucherDiscounts($voucher);
            $rowAmounts = json_decode($voucher->amount, true);
            $discountList = json_decode($voucher->discount_value, true) ?? [];
            $partyId = $voucher->party_id;
            $pType = $voucher->type;

            // 1. Reverse Multiple Accounts (Sources) -> Add back
            $accIds = json_decode($voucher->row_account_id, true);
            if (is_array($accIds)) {
                foreach ($accIds as $index => $accId) {
                    $rowAmount = (float)($rowAmounts[$index] ?? 0);
                    if ($accId && $rowAmount > 0) {
                        $acc = Account::find($accId);
                        if ($acc) {
                            $acc->opening_balance += $rowAmount;
                            $acc->save();
                        }
                    }
                }
            }

            $this->adjustVoucherDiscountAccounts($voucher, false, true);

            $partyLedger = app(PartyLedgerService::class);
            $pvDate = $voucher->entry_date ?? now()->toDateString();

            if ($partyId && in_array($pType, ['vendor', 'customer', 'walkin'], true) && is_array($rowAmounts)) {
                foreach ($rowAmounts as $index => $rowAmount) {
                    $partyImpact = (float) $rowAmount + (float) ($discountList[$index] ?? 0);
                    if ($partyImpact <= 0) {
                        continue;
                    }
                    $partyLedger->appendReversal(
                        $pType,
                        (int) $partyId,
                        $partyImpact,
                        0,
                        $pvDate,
                        "Unpost Payment Voucher #{$voucher->pvid}"
                    );
                }
            } elseif ($partyId && !in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                $partyAcc = Account::find($partyId);
                if ($partyAcc) {
                    $partyAcc->opening_balance -= ($totalAmount + $totalDiscount);
                    $partyAcc->save();
                }
            }
            
            DB::commit();
            return back()->with('success', 'Voucher unposted. Status reset to draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function cancel_payment($id)
    {
        $voucher = PaymentVoucher::findOrFail($id);
        $voucher->delete();
        return redirect()->route('all-Payment-vochers')->with('success', 'Voucher deleted.');
    }

    public function store_Pay_vochers(Request $request)
    {
        // 🧩 Standard Validation
        $request->validate([
            'vendor_type' => 'required',
            'vendor_id'   => 'required',
            'total_amount' => 'required|numeric|min:0.01',
        ], [
            'vendor_type.required' => 'Please select a Party Type',
            'vendor_id.required'   => 'Please select a Party',
            'total_amount.required' => 'Voucher total cannot be zero',
        ]);

        DB::beginTransaction();
        try {
            $pvid = PaymentVoucher::generateInvoiceNo();
            $narrationIds = [];
            $nIds = $request->input('narration_id', []);

            foreach ($nIds as $index => $narrId) {
                if (empty($narrId)) {
                    $narrationIds[] = "";
                    continue;
                }
                
                // 🧩 If narrId is NOT numeric, it means user typed a new narration (Select2 Tag)
                if (!is_numeric($narrId)) {
                    $expenseHead = 'Payment voucher';
                    $existing = \App\Models\Narration::where('narration', $narrId)
                        ->where('expense_head', $expenseHead)
                        ->first();
                    
                    if ($existing) {
                        $narrationIds[] = (string)$existing->id;
                    } else {
                        $new = \App\Models\Narration::create([
                            'expense_head' => $expenseHead,
                            'narration'    => $narrId,
                        ]);
                        $narrationIds[] = (string)$new->id;
                    }
                } else {
                    $narrationIds[] = (string)$narrId; // force string format
                }
            }
            $voucherData = [
                'pvid'             => $pvid,
                'receipt_date'     => $request->receipt_date,
                'entry_date'       => $request->entry_date,
                'entry_time'       => $request->entry_time,
                'type'             => $request->vendor_type,
                'party_id'         => $request->vendor_id,
                'tel'              => $request->tel,
                'remarks'          => $request->remarks,
                'narration_id' => json_encode($narrationIds),
                'reference_no'     => json_encode($request->reference_no),
            'row_account_head' => json_encode($request->row_account_head),
            'row_account_id'   => json_encode($request->row_account_id),
            'discount_value'   => json_encode($request->discount_value),
            'discount_head'    => json_encode($request->input('discount_head', [])),
            'discount_account_id' => json_encode($request->input('discount_account_id', [])),
                'kg'               => json_encode($request->kg),
                'rate'             => json_encode($request->rate),
                'amount'           => json_encode($request->amount),
                'total_amount'     => str_replace(',', '', $request->total_amount),
            ];

            PaymentVoucher::create($voucherData);

            DB::commit();
            return back()->with('success', 'Payment Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function all_Payment_vochers(Request $request)
    {
        $query = \App\Models\PaymentVoucher::query();

        if ($request->filled('start_date')) {
            $query->whereDate('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('entry_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('id', 'DESC')->get();

        foreach ($payments as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();
                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')
                    ->where('id', $voucher->party_id)
                    ->where('customer_type', 'Walking Customer')
                    ->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;

            $discountRaw = json_decode($voucher->discount_value, true);
            if (is_array($discountRaw)) {
                $voucher->total_discount = array_sum(array_map('floatval', $discountRaw));
            } else {
                $voucher->total_discount = (float)$voucher->discount_value;
            }
        }

        return view('admin_panel.vochers.payment_vochers.all_payment_vochers', compact('payments'));
    }

    public function Paymentprint($id)
    {
        $voucher = \App\Models\PaymentVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Build detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ Account Head type (numeric)
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;

            // ✅ Vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Walking customer
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }


    public function expense_vochers($id = null)
    {
        $receipt = $id ? \App\Models\ExpenseVoucher::findOrFail($id) : new \App\Models\ExpenseVoucher();
        $nextRvid = $receipt->evid ?: \App\Models\ExpenseVoucher::generateInvoiceNo();
        
        $AccountHeads = AccountHead::get();
        $accounts = Account::get();
        $narrationsList = \App\Models\Narration::where('expense_head', 'Expense voucher')->pluck('narration', 'id');

        return view('admin_panel.vochers.expense_vochers.expense_vouchers', compact('receipt', 'AccountHeads', 'accounts', 'narrationsList', 'nextRvid'));
    }

    public function ajax_save_expense(Request $request)
    {
        $this->validateExpenseRequest($request);

        $narrationIds = [];
        $nIds = $request->input('narration_id', []);
        foreach ($nIds as $index => $narrId) {
            if (empty($narrId)) { $narrationIds[] = ""; continue; }
            if (!is_numeric($narrId)) {
                $expenseHead = 'Expense voucher';
                $existing = \App\Models\Narration::where('narration', (string)$narrId)->where('expense_head', $expenseHead)->first();
                if ($existing) { $narrationIds[] = (string)$existing->id; }
                else {
                    $new = \App\Models\Narration::create(['expense_head' => $expenseHead, 'narration' => (string)$narrId]);
                    $narrationIds[] = (string)$new->id;
                }
            } else { $narrationIds[] = (string)$narrId; }
        }

        $data = [
            'entry_date'       => $request->entry_date,
            'entry_time'       => $request->entry_time ?? date('H:i'),
            'type'             => $request->vendor_type,
            'party_id'         => $request->vendor_id,
            'remarks'          => $request->remarks,
            'narration_id'     => json_encode($narrationIds),
            'row_account_head' => json_encode($request->row_account_head),
            'row_account_id'   => json_encode($request->row_account_id),
            'amount'           => json_encode($request->amount),
            'total_amount'     => str_replace(',', '', $request->total_amount),
            'status'           => 'draft'
        ];

        if ($request->id) {
            $voucher = ExpenseVoucher::findOrFail($request->id);
            if ($voucher->status === 'posted') return response()->json(['success' => false, 'message' => 'Cannot edit posted voucher']);
            $voucher->update($data);
        } else {
            $data['evid'] = ExpenseVoucher::generateInvoiceNo();
            $voucher = ExpenseVoucher::create($data);
        }

        return response()->json(['success' => true, 'id' => $voucher->id, 'evid' => $voucher->evid]);
    }

    public function post_expense(Request $request, $id = null)
    {
        $id = $id ?: $request->id;
        $voucher = ExpenseVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Already posted');

        DB::beginTransaction();
        try {
            $amount = (float)$voucher->total_amount;

            $partyLedger = app(PartyLedgerService::class);
            $evDate = $voucher->entry_date ?? now()->toDateString();

            if (in_array($voucher->type, ['vendor', 'customer', 'walkin'], true)) {
                $partyLedger->postExpenseCredit(
                    $voucher->type,
                    (int) $voucher->party_id,
                    $amount,
                    $evDate,
                    "Expense Voucher #$voucher->evid"
                );
            } else {
                $account = Account::find($voucher->party_id);
                if ($account) {
                    $account->opening_balance -= $amount;
                    $account->save();
                }
            }

            // Row side (Expense destination) -> PLUS
            $accIds = json_decode($voucher->row_account_id, true) ?? [];
            $amounts = json_decode($voucher->amount, true) ?? [];
            foreach ($accIds as $index => $accId) {
                $rowAmount = isset($amounts[$index]) ? (float)$amounts[$index] : 0;
                if ($rowAmount > 0) {
                    $acc = Account::find($accId);
                    if ($acc) {
                        $acc->opening_balance += $rowAmount;
                        $acc->save();
                    }
                }
            }

            $voucher->status = 'posted';
            $voucher->save();
            DB::commit();
            return redirect()->route('all-expense-vochers')->with('success', 'Expense Voucher posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function unpost_expense($id)
    {
        $voucher = ExpenseVoucher::findOrFail($id);
        if ($voucher->status !== 'posted') return back()->with('error', 'Only posted vouchers can be unposted');

        DB::beginTransaction();
        try {
            $amount = (float)$voucher->total_amount;

            // Revert Header party ledger via reversal row (keeps chain intact)
            if (in_array($voucher->type, ['vendor', 'customer', 'walkin'], true)) {
                app(PartyLedgerService::class)->appendReversal(
                    $voucher->type,
                    (int) $voucher->party_id,
                    0,
                    $amount,
                    $voucher->entry_date ?? now()->toDateString(),
                    "Unpost Expense Voucher #$voucher->evid"
                );
            } else {
                $account = Account::find($voucher->party_id);
                if ($account) { $account->opening_balance += $amount; $account->save(); }
            }

            // Revert Rows
            $accIds = json_decode($voucher->row_account_id, true) ?? [];
            $amounts = json_decode($voucher->amount, true) ?? [];
            foreach ($accIds as $index => $accId) {
                $rowAmount = isset($amounts[$index]) ? (float)$amounts[$index] : 0;
                if ($rowAmount > 0) {
                    $acc = Account::find($accId);
                    if ($acc) { $acc->opening_balance -= $rowAmount; $acc->save(); }
                }
            }

            $voucher->status = 'draft';
            $voucher->save();
            DB::commit();
            return back()->with('success', 'Expense Voucher unposted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel_expense($id)
    {
        $voucher = ExpenseVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Cannot delete posted voucher');
        $voucher->delete();
        return redirect()->route('all-expense-vochers')->with('success', 'Voucher deleted');
    }




    public function all_expense_vochers(Request $request)
    {
        $query = \App\Models\ExpenseVoucher::query();

        $dateCol = $this->getDateColumn('expense_vouchers');
        if ($request->filled('start_date')) {
            $query->whereDate(DB::raw($dateCol), '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate(DB::raw($dateCol), '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('id', 'DESC')->get();

        foreach ($expenses as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account = DB::table('accounts')->where('id', $voucher->party_id)->first();
                $typeLabel = $accountHead->name ?? 'Account';
                $partyName = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;
        }

        return view('admin_panel.vochers.expense_vochers.all_expense_vochers', compact('expenses'));
    }




    public function expenseprint($id)
    {
        $voucher = \App\Models\ExpenseVoucher::findOrFail($id);

        // Decode JSON arrays safely
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Prepare detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float)($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party Setup Based on Type
        $party = null;
        $previousBalance = 0;

        if (is_numeric($voucher->type)) {
            // ✅ Account Head type (numeric)
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object)[
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;
        } elseif ($voucher->type === 'vendor') {
            // ✅ Vendor Type
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'customer') {
            // ✅ Customer Type
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'walkin') {
            // ✅ Walking Customer
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.expense_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    // ==========================================
    // INCOME VOUCHER METHODS
    // ==========================================

    public function income_vochers($id = null)
    {
        $receipt = $id ? IncomeVoucher::findOrFail($id) : new IncomeVoucher();
        $AccountHeads = DB::table('account_heads')->get();
        // Narrations specifically for Income voucher or 'all'
        $narrationsList = DB::table('narrations')->where('expense_head', 'Income voucher')->pluck('narration', 'id');
        
        $nextIvid = null;
        if (!$id) {
            $last = IncomeVoucher::orderBy('id', 'desc')->first();
            $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->ivid) + 1 : 1;
            $nextIvid = str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        return view('admin_panel.vochers.income_vouchers.income_vouchers', compact('receipt', 'AccountHeads', 'narrationsList', 'nextIvid'));
    }

    public function showIncome($id)
    {
        $receipt = IncomeVoucher::findOrFail($id);
        $AccountHeads = DB::table('account_heads')->get();
        $narrationsList = DB::table('narrations')->where('expense_head', 'Income voucher')->pluck('narration', 'id');
        $viewMode = true;

        return view('admin_panel.vochers.income_vouchers.income_vouchers', compact('receipt', 'AccountHeads', 'narrationsList', 'viewMode'));
    }

    public function ajax_save_income(Request $request)
    {
        $this->validateIncomeRequest($request);

        try {
            $id = $request->id;
            $voucher = $id ? IncomeVoucher::findOrFail($id) : new IncomeVoucher();

            if (!$id) {
                $last = IncomeVoucher::orderBy('id', 'desc')->first();
                $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->ivid) + 1 : 1;
                $voucher->ivid = str_pad($num, 3, '0', STR_PAD_LEFT);
                $voucher->status = 'draft';
            }

            // 🧩 Narration Tag handling (Auto-create narrations if they are tags)
            $narrationIds = [];
            foreach ($request->input('narration_id', []) as $narrId) {
                if (empty($narrId)) { $narrationIds[] = ""; continue; }
                if (!is_numeric($narrId)) {
                    $new = \App\Models\Narration::firstOrCreate(
                        ['narration' => $narrId, 'expense_head' => 'Income voucher']
                    );
                    $narrationIds[] = (string)$new->id;
                } else {
                    $narrationIds[] = (string)$narrId;
                }
            }

                $voucher->entry_date   = $request->entry_date;
                $voucher->entry_time   = $request->entry_time ?? date('H:i');
                $voucher->account_head = $request->account_head;
            $voucher->account_id   = $request->account_id;
            $voucher->remarks      = $request->remarks;
            $voucher->narration_id = json_encode($narrationIds);
            $voucher->party_type   = json_encode($request->input('party_type', []));
            $voucher->party_id     = json_encode($request->input('party_id', []));
            $voucher->reference_no = json_encode($request->input('reference_no', []));
            $voucher->amount       = json_encode($request->input('amount', []));
            $voucher->total_amount = str_replace(',', '', $request->total_amount);
            
            $voucher->save();

            return response()->json(['success' => true, 'id' => $voucher->id, 'ivid' => $voucher->ivid]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function post_income($id)
    {
        $voucher = IncomeVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Already posted.');

        DB::beginTransaction();
        try {
            $totalAmount = (float)$voucher->total_amount;
            $ivid = $voucher->ivid;

            $partyLedger = app(PartyLedgerService::class);
            $ivDate = $voucher->entry_date ?? now()->toDateString();

            // Header account (destination) -> debit
            $hType = strtolower($voucher->account_head ?? '');
            if (in_array($hType, ['vendor', 'customer', 'walkin', 'subcustomer'], true)) {
                $partyLedger->postIncomeDebit(
                    $hType === 'subcustomer' ? 'subcustomer' : ($hType === 'walkin' ? 'customer' : $hType),
                    (int) $voucher->account_id,
                    $totalAmount,
                    $ivDate,
                    "Income Voucher #$ivid (Header)"
                );
            } else {
                $headerAcc = \App\Models\Account::find($voucher->account_id);
                if ($headerAcc) {
                    $headerAcc->opening_balance += $totalAmount;
                    $headerAcc->save();
                }
            }

            // Row parties -> debit (matches GL IV)
            $types = json_decode($voucher->party_type, true) ?? [];
            $pIds = json_decode($voucher->party_id, true) ?? [];
            $amounts = json_decode($voucher->amount, true) ?? [];

            foreach ($pIds as $idx => $pId) {
                $rowAmount = (float) ($amounts[$idx] ?? 0);
                $pType = $types[$idx] ?? '';
                if ($rowAmount <= 0) {
                    continue;
                }

                if (in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                    $partyLedger->postIncomeDebit($pType, (int) $pId, $rowAmount, $ivDate, "Income Voucher #$ivid");
                } else {
                    $acc = \App\Models\Account::find($pId);
                    if ($acc) {
                        $acc->opening_balance -= $rowAmount;
                        $acc->save();
                    }
                }
            }

            $voucher->status = 'posted';
            $voucher->save();

            DB::commit();
            return redirect()->route('income-vochers')->with('success', 'Income Voucher Posted Successfully and Ledgers Updated');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function unpost_income($id)
    {
        $voucher = IncomeVoucher::findOrFail($id);
        if ($voucher->status !== 'posted') return back()->with('error', 'Voucher is not posted.');

        DB::beginTransaction();
        try {
            $totalAmount = (float)$voucher->total_amount;
            $ivid = $voucher->ivid;

            $partyLedger = app(PartyLedgerService::class);
            $ivDate = $voucher->entry_date ?? now()->toDateString();

            $hType = strtolower($voucher->account_head ?? '');
            if (in_array($hType, ['vendor', 'customer', 'walkin', 'subcustomer'], true)) {
                $partyLedger->appendReversal(
                    $hType === 'subcustomer' ? 'subcustomer' : ($hType === 'walkin' ? 'customer' : $hType),
                    (int) $voucher->account_id,
                    $totalAmount,
                    0,
                    $ivDate,
                    "Unpost Income Voucher #$ivid (Header)"
                );
            } else {
                $headerAcc = \App\Models\Account::find($voucher->account_id);
                if ($headerAcc) {
                    $headerAcc->opening_balance -= $totalAmount;
                    $headerAcc->save();
                }
            }

            $types = json_decode($voucher->party_type, true) ?? [];
            $pIds = json_decode($voucher->party_id, true) ?? [];
            $amounts = json_decode($voucher->amount, true) ?? [];

            foreach ($pIds as $idx => $pId) {
                $rowAmount = (float) ($amounts[$idx] ?? 0);
                $pType = $types[$idx] ?? '';
                if ($rowAmount <= 0) {
                    continue;
                }

                if (in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                    $partyLedger->appendReversal($pType, (int) $pId, $rowAmount, 0, $ivDate, "Unpost Income Voucher #$ivid");
                } else {
                    $acc = \App\Models\Account::find($pId);
                    if ($acc) {
                        $acc->opening_balance += $rowAmount;
                        $acc->save();
                    }
                }
            }

            $voucher->status = 'draft';
            $voucher->save();

            DB::commit();
            return redirect()->route('income-vochers', $id)->with('success', 'Income Voucher Unposted and Ledgers Reversed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error reversing ledgers: ' . $e->getMessage());
        }
    }

    public function cancel_income($id)
    {
        $voucher = IncomeVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Cannot delete posted voucher. Unpost first.');
        $voucher->delete();
        return redirect()->route('all-income-vochers')->with('success', 'Income Voucher Deleted Successfully.');
    }

    public function all_income_vochers(Request $request)
    {
        $query = IncomeVoucher::query();

        $dateCol = $this->getDateColumn('income_vouchers');
        if ($request->filled('start_date')) $query->whereDate(DB::raw($dateCol), '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate(DB::raw($dateCol), '<=', $request->end_date);
        if ($request->filled('status')) $query->where('status', $request->status);

        $incomes = $query->with('creator')->orderBy('id', 'DESC')->get();

        foreach ($incomes as $v) {
            $pNames = [];
            $typesRaw = json_decode($v->party_type, true);
            if (!is_array($typesRaw)) $typesRaw = [$v->party_type];
            $pIdsRaw = json_decode($v->party_id, true);
            if (!is_array($pIdsRaw)) $pIdsRaw = [$v->party_id];
            
            foreach ($pIdsRaw as $idx => $pId) {
                if (empty($pId)) continue;
                $pType = $typesRaw[$idx] ?? '';
                if ($pType === 'vendor') $pNames[] = DB::table('vendors')->where('id', $pId)->value('name');
                elseif (in_array($pType, ['customer', 'walkin', 'walking'])) $pNames[] = DB::table('customers')->where('id', $pId)->value('customer_name');
                else $pNames[] = DB::table('accounts')->where('id', $pId)->value('title');
            }
            
            $v->party_name = count($pNames) > 1 ? count(array_unique($pNames)) . ' Parties' : ($pNames[0] ?? '-');
            
            $accountHead = DB::table('account_heads')->where('id', $v->account_head)->first();
            if (count(array_unique($typesRaw)) > 1) {
                $v->type_label = 'Multiple Types';
            } else {
                $firstType = $typesRaw[0] ?? 'Account';
                if (is_numeric($firstType)) {
                    $accHead = DB::table('account_heads')->where('id', $firstType)->first();
                    $v->type_label = $accHead->name ?? 'Account';
                } elseif (in_array($firstType, ['walkin', 'walking'])) {
                    $v->type_label = 'Walk-in';
                } else {
                    $v->type_label = ucfirst($firstType);
                }
            }
            
            $discountRaw = json_decode($v->discount_value, true);
            $v->total_discount = is_array($discountRaw) ? array_sum(array_map('floatval', $discountRaw)) : (float)$v->discount_value;
            $v->total_amount = $v->total_amount ?: 0;
        }

        return view('admin_panel.vochers.income_vouchers.all_income_vouchers', compact('incomes'));
    }

    public function incomeprint($id)
    {
        $voucher = IncomeVoucher::findOrFail($id);
        
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $types = json_decode($voucher->party_type, true) ?? [];
        $pIds = json_decode($voucher->party_id, true) ?? [];
        $refs = json_decode($voucher->reference_no, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        $rows = [];
        foreach ($narrations as $i => $nId) {
            if (empty($nId)) continue;
            
            $pName = '-';
            $type = $types[$i] ?? null;
            $pid = $pIds[$i] ?? null;
            
            if ($type == 'vendor') $pName = DB::table('vendors')->where('id', $pid)->value('name');
            elseif (in_array($type, ['customer', 'walkin'])) $pName = DB::table('customers')->where('id', $pid)->value('customer_name');
            else $pName = DB::table('accounts')->where('id', $pid)->value('title');

            $rows[] = [
                'narration' => DB::table('narrations')->where('id', $nId)->value('narration'),
                'party_name' => $pName,
                'party_type' => $type,
                'reference' => $refs[$i] ?? '',
                'amount' => (float)($amounts[$i] ?? 0)
            ];
        }

        $headerAccount = DB::table('accounts')->where('id', $voucher->account_id)->first();
        
        // Match Expense Voucher print variables
        $party = $headerAccount;
        $party->head_name = DB::table('account_heads')->where('id', $party->head_id)->value('name');
        $party->name = $party->title;
        $party->phone = $party->account_code; // Using code as phone placeholder for account type
        
        $previousBalance = (float)($headerAccount->opening_balance ?? 0);

        return view('admin_panel.vochers.income_vouchers.print', compact('voucher', 'rows', 'headerAccount', 'party', 'previousBalance'));
    }


    public function adjustmentprint($id)
    {
        $voucher = AdjustmentVoucher::findOrFail($id);
        $narrs = json_decode($voucher->narration_id, true) ?? [];
        $accHeads = json_decode($voucher->account_head, true) ?? [];
        $accIds = json_decode($voucher->account_id, true) ?? [];
        $refs = json_decode($voucher->reference_no, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        $rows = [];
        for ($i = 0; $i < count($narrs); $i++) {
            $nId = $narrs[$i] ?? "";
            $aid = $accIds[$i] ?? "";
            $hId = $accHeads[$i] ?? "";

            $accName = DB::table('accounts')->where('id', $aid)->value('title');
            $headName = DB::table('account_heads')->where('id', $hId)->value('name');

            $rows[] = [
                'narration' => DB::table('narrations')->where('id', $nId)->value('narration'),
                'account_name' => $accName,
                'head_name' => $headName,
                'reference' => $refs[$i] ?? '',
                'amount' => (float)($amounts[$i] ?? 0)
            ];
        }

        $party = null;
        $type = $voucher->party_type;
        $pid = $voucher->party_id;
        if ($type == 'vendor') $party = DB::table('vendors')->where('id', $pid)->first();
        elseif (in_array($type, ['customer', 'walkin'])) $party = DB::table('customers')->where('id', $pid)->first();
        else {
            $party = DB::table('accounts')->where('id', $pid)->first();
            if ($party) {
                // Formatting for uniform print object
                $party->name = $party->title;
                $party->phone = $party->account_code;
            }
        }
        
        $party->head_name = (is_numeric($type)) ? DB::table('account_heads')->where('id', $type)->value('name') : ucfirst($type);
        
        $previousBalance = 0;
        if ($type == 'vendor') {
            $previousBalance = (float)DB::table('vendor_ledgers')->where('vendor_id', $pid)->orderBy('id','desc')->value('closing_balance');
        } elseif (in_array($type, ['customer', 'walkin'])) {
            $previousBalance = (float)DB::table('customer_ledgers')->where('customer_id', $pid)->orderBy('id','desc')->value('closing_balance');
        } else {
            $previousBalance = (float)DB::table('accounts')->where('id', $pid)->value('opening_balance');
        }

        return view('admin_panel.vochers.adjustment_vouchers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    public function adjustment_vochers($id = null)
    {
        $receipt = $id ? AdjustmentVoucher::findOrFail($id) : new AdjustmentVoucher();
        $AccountHeads = DB::table('account_heads')->get();
        // Narrations specifically for Adjustment voucher or 'all'
        $narrationsList = DB::table('narrations')->where('expense_head', 'Adjustment voucher')->pluck('narration', 'id');
        
        $nextAvid = null;
        if (!$id) {
            $last = AdjustmentVoucher::orderBy('id', 'desc')->first();
            $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->avid) + 1 : 1;
            $nextAvid = str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        return view('admin_panel.vochers.adjustment_vouchers.adjustment_vouchers', compact('receipt', 'AccountHeads', 'narrationsList', 'nextAvid'));
    }

    public function ajax_save_adjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'party_type' => 'required',
            'party_id' => 'required',
            'entry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->only(['entry_date', 'party_type', 'party_id', 'remarks', 'total_amount']);
            if (isset($data['total_amount'])) {
                $data['total_amount'] = str_replace(',', '', $data['total_amount']);
            }
            
            // Handle Narrations (Select2 Tags)
            $narrationIds = [];
            foreach ($request->input('narration_id', []) as $nId) {
                if ($nId && !is_numeric($nId)) {
                    $newN = \App\Models\Narration::firstOrCreate(['narration' => $nId, 'expense_head' => 'Adjustment voucher']);
                    $narrationIds[] = (string)$newN->id;
                } else {
                    $narrationIds[] = (string)$nId;
                }
            }

            $data['narration_id'] = json_encode($narrationIds);
            $data['account_head'] = json_encode($request->account_head);
            $data['account_id'] = json_encode($request->account_id);
            $data['reference_no'] = json_encode($request->reference_no);
            $data['amount'] = json_encode($request->amount);

            if ($request->id) {
                $voucher = AdjustmentVoucher::findOrFail($request->id);
                if ($voucher->status == 'posted') return response()->json(['success' => false, 'message' => 'Cannot edit posted voucher.'], 403);
                $voucher->update($data);
            } else {
                $last = AdjustmentVoucher::orderBy('id', 'desc')->first();
                $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->avid) + 1 : 1;
                $data['avid'] = str_pad($num, 3, '0', STR_PAD_LEFT);
                $data['status'] = 'draft';
                $voucher = AdjustmentVoucher::create($data);
            }

            return response()->json(['success' => true, 'id' => $voucher->id, 'avid' => $voucher->avid]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function post_adjustment($id)
    {
        $voucher = AdjustmentVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Already posted.');

        DB::beginTransaction();
        try {
            $totalAmount = (float)$voucher->total_amount;
            $avid = $voucher->avid;

            // 1. Update Header Party (Source) -> DEBIT (Increase balance)
            $pType = $voucher->party_type;
            $pId = $voucher->party_id;

            $partyLedger = app(PartyLedgerService::class);
            $avDate = $voucher->entry_date ?? now()->toDateString();

            if (in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                $partyLedger->postPaymentDebit($pType, (int) $pId, $totalAmount, $avDate, "Adjustment Voucher #$avid");
            } else {
                // Head/Account based Source
                $headerAcc = \App\Models\Account::find($pId);
                if ($headerAcc) {
                    $headerAcc->opening_balance += $totalAmount;
                    $headerAcc->save();
                }
            }

            // 2. Update Row Accounts (Destinations) -> CREDIT (Decrease)
            $accHeads = json_decode($voucher->account_head, true) ?? [];
            $accIds = json_decode($voucher->account_id, true) ?? [];
            $amounts = json_decode($voucher->amount, true) ?? [];

            foreach ($accIds as $idx => $accId) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                if ($rowAmount <= 0) continue;

                $rType = $accHeads[$idx] ?? '';

                if (in_array($rType, ['vendor', 'customer', 'walkin'], true)) {
                    $partyLedger->postReceiptCredit($rType, (int) $accId, $rowAmount, $avDate, "Adjustment Voucher #$avid");
                } else {
                    $rowAcc = \App\Models\Account::find($accId);
                    if ($rowAcc) {
                        $rowAcc->opening_balance -= $rowAmount;
                        $rowAcc->save();
                    }
                }
            }

            $voucher->status = 'posted';
            $voucher->save();

            DB::commit();
            return back()->with('success', 'Adjustment Voucher posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }


    public function cancel_adjustment($id)
    {
        $voucher = AdjustmentVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Cannot cancel a posted voucher.');
        $voucher->delete();
        return redirect()->route('all-adjustment-vochers')->with('success', 'Adjustment Voucher deleted successfully.');
    }

    public function all_adjustment_vochers(Request $request)
    {
        $query = AdjustmentVoucher::query();
        $dateCol = $this->getDateColumn('adjustment_vouchers');
        if ($request->filled('start_date')) $query->whereDate(DB::raw($dateCol), '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate(DB::raw($dateCol), '<=', $request->end_date);
        if ($request->filled('status')) $query->where('status', $request->status);

        $vouchers = $query->orderBy('id', 'DESC')->get();

        foreach ($vouchers as $v) {
            $partyCode = '-';
            if (is_numeric($v->party_type)) {
                $head = DB::table('account_heads')->where('id', $v->party_type)->first();
                $acc = DB::table('accounts')->where('id', $v->party_id)->first();
                $typeLabel = $head->name ?? 'Account';
                $partyName = $acc->title ?? '-';
                $partyCode = $acc->account_code ?? $v->party_id;
            } elseif ($v->party_type === 'vendor') {
                $vendor = DB::table('vendors')->where('id', $v->party_id)->first();
                $partyName = $vendor->name ?? '-';
                $partyCode = $vendor->id ?? '-';
                $typeLabel = 'Vendor';
            } elseif ($v->party_type === 'customer' || $v->party_type === 'walkin') {
                $customer = DB::table('customers')->where('id', $v->party_id)->first();
                $partyName = $customer->customer_name ?? '-';
                $partyCode = $customer->id ?? '-';
                $typeLabel = ($v->party_type === 'walkin') ? 'Walk-in' : 'Customer';
            }

            $v->type_label = $typeLabel;
            $v->party_name = $partyName;
            $v->party_code = $partyCode;

            // Extract Accounts (Side 2)
            $rowAccIds = json_decode($v->account_id, true) ?? [];
            $rowAccHeads = json_decode($v->account_head, true) ?? [];
            $accountsList = [];
            foreach ($rowAccIds as $idx => $aid) {
                if (!$aid) continue;
                $headOrType = $rowAccHeads[$idx] ?? null;
                
                if ($headOrType === 'vendor') {
                    $acc = DB::table('vendors')->where('id', $aid)->first();
                    if ($acc) $accountsList[] = "[Vendor] " . $acc->name;
                } elseif ($headOrType === 'customer' || $headOrType === 'walkin') {
                    $acc = DB::table('customers')->where('id', $aid)->first();
                    if ($acc) $accountsList[] = "[" . ucfirst($headOrType) . "] " . $acc->customer_name;
                } else {
                    $acc = DB::table('accounts')->where('id', $aid)->first();
                    if ($acc) {
                        $headName = DB::table('account_heads')->where('id', $headOrType)->value('name');
                        $accountsList[] = ($headName ? "[$headName] " : "") . $acc->title . ($acc->account_code ? " (#{$acc->account_code})" : "");
                    }
                }
            }
            $v->accounts_detail = implode('<br>', $accountsList);
        }

        return view('admin_panel.vochers.adjustment_vouchers.all_adjustment_vouchers', compact('vouchers'));
    }

    // ==========================================
    // JOURNAL VOUCHER (GENERAL VOUCHER) METHODS
    // ==========================================

    public function journal_vochers($id = null)
    {
        $receipt = $id ? JournalVoucher::findOrFail($id) : new JournalVoucher();
        $AccountHeads = DB::table('account_heads')->get();
        // Filter narrations for Journal Voucher
        $narrationsList = DB::table('narrations')->where('expense_head', 'Journal voucher')->pluck('narration', 'id');
        
        $nextJvid = null;
        if (!$id) {
            $last = JournalVoucher::orderBy('id', 'desc')->first();
            $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->jvid) + 1 : 1;
            $nextJvid = str_pad($num, 3, '0', STR_PAD_LEFT);
        }

        return view('admin_panel.vochers.journal_vouchers.journal_vouchers', compact('receipt', 'AccountHeads', 'narrationsList', 'nextJvid'));
    }

    public function ajax_save_journal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $pTypes = $request->input('party_type', []);
            $pIds = $request->input('party_id', []);

            if (count($pIds) < 2) {
                return response()->json(['success' => false, 'message' => 'At least 2 rows are required for a Journal Voucher.'], 422);
            }

            // Row-wise validation (Only Type and Name required now)
            foreach ($pTypes as $idx => $type) {
                if (empty($type) || empty($pIds[$idx])) {
                    return response()->json(['success' => false, 'message' => 'Please select Party Type and Name for all rows.'], 422);
                }
            }

            $totalDr = (float)str_replace(',', '', $request->total_debit);
            $totalCr = (float)str_replace(',', '', $request->total_credit);
            if (abs($totalDr - $totalCr) > 0.01) {
                return response()->json(['success' => false, 'message' => 'Total Debit must equal Total Credit.'], 422);
            }

            $data = $request->only(['entry_date', 'reference_no', 'remarks']);
            $data['total_debit'] = $totalDr;
            $data['total_credit'] = $totalCr;
            
            // Handle Narrations
            $narrationIds = [];
            foreach ($request->input('narration_id', []) as $nId) {
                if ($nId && !is_numeric($nId)) {
                    $newN = \App\Models\Narration::firstOrCreate(['narration' => $nId, 'expense_head' => 'Journal voucher']);
                    $narrationIds[] = (string)$newN->id;
                } else {
                    $narrationIds[] = (string)$nId;
                }
            }

            $data['narration_id'] = json_encode($narrationIds);
            $data['party_type'] = json_encode($request->party_type);
            $data['party_id'] = json_encode($request->party_id);
            
            // Strip commas from row debits and credits
            $debits = array_map(function($val) { return (float)str_replace(',', '', $val); }, $request->debit ?? []);
            $credits = array_map(function($val) { return (float)str_replace(',', '', $val); }, $request->credit ?? []);
            
            $data['debit'] = json_encode($debits);
            $data['credit'] = json_encode($credits);
            $data['dr_cr'] = json_encode([]); // No longer needed but stored for structure

            if ($request->id) {
                $voucher = JournalVoucher::findOrFail($request->id);
                if ($voucher->status == 'posted') return response()->json(['success' => false, 'message' => 'Cannot edit posted voucher.'], 403);
                $voucher->update($data);
            } else {
                $last = JournalVoucher::orderBy('id', 'desc')->first();
                $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->jvid) + 1 : 1;
                $data['jvid'] = str_pad($num, 3, '0', STR_PAD_LEFT);
                $data['status'] = 'draft';
                $voucher = JournalVoucher::create($data);
            }

            return response()->json(['success' => true, 'id' => $voucher->id, 'jvid' => $voucher->jvid]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('JV Save Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function post_journal($id)
    {
        $voucher = JournalVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Already posted.');

        DB::beginTransaction();
        try {
            $jvid = $voucher->jvid;
            $entryDate = $voucher->entry_date ?: now();

            // 1. Post Rows (Each row is a separate entry)
            $pTypes = json_decode($voucher->party_type, true) ?? [];
            $pIds = json_decode($voucher->party_id, true) ?? [];
            $debits = json_decode($voucher->debit, true) ?? [];
            $credits = json_decode($voucher->credit, true) ?? [];

            $partyLedger = app(PartyLedgerService::class);

            foreach ($pTypes as $idx => $type) {
                $dr = (float) ($debits[$idx] ?? 0);
                $cr = (float) ($credits[$idx] ?? 0);
                if ($dr == 0.0 && $cr == 0.0) {
                    continue;
                }

                $pid = $pIds[$idx] ?? null;
                if (!$pid) {
                    continue;
                }

                if (in_array($type, ['vendor', 'customer', 'walkin'], true)) {
                    $partyLedger->append($type, (int) $pid, [
                        'date' => $entryDate,
                        'description' => "Journal Voucher #$jvid",
                        'debit' => $dr,
                        'credit' => $cr,
                    ]);
                } else {
                    // It's an Account Head
                    $acc = \App\Models\Account::find($pid);
                    if ($acc) {
                        $acc->opening_balance += ($dr - $cr);
                        $acc->save();
                    }
                }
            }

            $voucher->status = 'posted';
            $voucher->save();
            DB::commit();
            return redirect()->route('all-journal-vochers')->with('success', 'Journal Voucher posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel_journal($id)
    {
        $voucher = JournalVoucher::findOrFail($id);
        if ($voucher->status === 'posted') return back()->with('error', 'Cannot delete posted voucher.');
        $voucher->delete();
        return redirect()->route('all-journal-vochers')->with('success', 'Journal Voucher deleted.');
    }

    public function all_journal_vochers(Request $request)
    {
        $query = JournalVoucher::query();
        $dateCol = $this->getDateColumn('journal_vouchers');
        if ($request->filled('start_date')) $query->whereDate(DB::raw($dateCol), '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate(DB::raw($dateCol), '<=', $request->end_date);
        if ($request->filled('status')) $query->where('status', $request->status);

        $vouchers = $query->orderBy('id', 'DESC')->get();

        foreach ($vouchers as $v) {
            // Safe JSON decode — json_decode can return int/string/null, must use is_array()
            $decoded = json_decode($v->party_type, true);
            $pTypes = is_array($decoded) ? $decoded : [];

            $decoded = json_decode($v->party_id, true);
            $pIds = is_array($decoded) ? $decoded : [];

            $decoded = json_decode($v->debit, true);
            $debits = is_array($decoded) ? $decoded : [];

            $decoded = json_decode($v->credit, true);
            $credits = is_array($decoded) ? $decoded : [];

            $summary = [];

            // Set type_label and party_name from first row for blade display
            $v->type_label = '-';
            $v->party_name = '-';

            foreach ($pTypes as $idx => $type) {
                $pid = $pIds[$idx] ?? null;
                if (!$pid) continue;

                $pName = '-';
                if ($type === 'vendor') $pName = DB::table('vendors')->where('id', $pid)->value('name') ?? '-';
                elseif ($type === 'customer' || $type === 'walkin') $pName = DB::table('customers')->where('id', $pid)->value('customer_name') ?? '-';
                else $pName = DB::table('accounts')->where('id', $pid)->value('title') ?? '-';

                // Set first row as header display
                if ($idx === 0) {
                    if (is_numeric($type)) {
                        $accHead = DB::table('account_heads')->where('id', $type)->first();
                        $v->type_label = $accHead->name ?? 'Account';
                    } elseif (in_array($type, ['walkin', 'walking'])) {
                        $v->type_label = 'Walk-in';
                    } else {
                        $v->type_label = ucfirst($type);
                    }
                    $v->party_name = $pName;
                }

                $dr = (float)($debits[$idx] ?? 0);
                $cr = (float)($credits[$idx] ?? 0);
                $summary[] = "($pName) Dr: $dr, Cr: $cr";
            }
            $v->accounts_detail = implode('<br>', $summary);
        }

        return view('admin_panel.vochers.journal_vouchers.all_journal_vouchers', compact('vouchers'));
    }

    public function journalprint($id)
    {
        $voucher = JournalVoucher::findOrFail($id);
        $narrs = json_decode($voucher->narration_id, true) ?? [];
        $pTypes = json_decode($voucher->party_type, true) ?? [];
        $pIds = json_decode($voucher->party_id, true) ?? [];
        $debits = json_decode($voucher->debit, true) ?? [];
        $credits = json_decode($voucher->credit, true) ?? [];

        $rows = [];
        for ($i = 0; $i < count($pTypes); $i++) {
            $type = $pTypes[$i] ?? "";
            $pid = $pIds[$i] ?? "";
            if (!$pid) continue;

            $pName = '-';
            $pCode = '-';
            if ($type === 'vendor') {
                $p = DB::table('vendors')->where('id', $pid)->first();
                $pName = $p->name ?? '-';
                $pCode = $p->id ?? '-';
            } elseif ($type === 'customer' || $type === 'walkin') {
                $p = DB::table('customers')->where('id', $pid)->first();
                $pName = $p->customer_name ?? '-';
                $pCode = $p->id ?? '-';
            } else {
                $p = DB::table('accounts')->where('id', $pid)->first();
                $pName = $p->title ?? '-';
                $pCode = $p->account_code ?? '-';
            }

            $rows[] = [
                'narration' => DB::table('narrations')->where('id', $narrs[$i] ?? null)->value('narration'),
                'account_name' => $pName,
                'account_code' => $pCode,
                'debit' => (float)($debits[$i] ?? 0),
                'credit' => (float)($credits[$i] ?? 0)
            ];
        }

        return view('admin_panel.vochers.journal_vouchers.print', compact('voucher', 'rows'));
    }
}

