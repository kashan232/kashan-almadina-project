<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ReceiptsVoucher;
use App\Models\PaymentVoucher;
use App\Models\JournalVoucher;
use App\Models\ExpenseVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneralLedgerController extends Controller
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

    public function index()
    {
        $heads = AccountHead::orderBy('name')->get();
        $accounts = Account::orderBy('title')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        return view('admin_panel.reports.general_ledger.index', compact('heads', 'accounts', 'customers', 'vendors'));
    }

    public function getAccountsByHead($headId)
    {
        $accounts = Account::where('head_id', $headId)->orderBy('title')->get(['id', 'account_code', 'title']);
        return response()->json($accounts);
    }

    public function searchUnified(Request $request)
    {
        $query = $request->get('query');
        $type = $request->get('type', 'all'); // 'all', 'account', 'vendor', 'customer'

        $results = [];

        if ($type == 'all' || $type == 'account') {
            $accounts = Account::where('title', 'LIKE', "%{$query}%")
                ->orWhere('account_code', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function($a) {
                    return [
                        'id' => $a->id,
                        'text' => $a->title . ' (' . $a->account_code . ')',
                        'code' => $a->account_code,
                        'title' => $a->title,
                        'type' => 'account',
                        'tel' => ''
                    ];
                });
            $results = array_merge($results, $accounts->toArray());
        }

        if ($type == 'all' || $type == 'vendor') {
            $vendors = Vendor::where('name', 'LIKE', "%{$query}%")
                ->orWhere('vendor_id', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function($v) {
                    return [
                        'id' => $v->id,
                        'text' => $v->name . ' (' . $v->vendor_id . ') [Vendor]',
                        'code' => $v->vendor_id,
                        'title' => $v->name,
                        'type' => 'vendor',
                        'tel' => $v->mobile
                    ];
                });
            $results = array_merge($results, $vendors->toArray());
        }

        if ($type == 'all' || $type == 'customer') {
            $customers = Customer::where('customer_name', 'LIKE', "%{$query}%")
                ->orWhere('customer_id', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function($c) {
                    return [
                        'id' => $c->id,
                        'text' => $c->customer_name . ' (' . $c->customer_id . ') [Customer]',
                        'code' => $c->customer_id,
                        'title' => $c->customer_name,
                        'type' => 'customer',
                        'tel' => $c->mobile
                    ];
                });
            $results = array_merge($results, $customers->toArray());
        }

        return response()->json($results);
    }

    public function lookupByCode(Request $request)
    {
        $code = $request->get('code');

        // Check Account
        $account = Account::where('account_code', $code)->first();
        if ($account) {
            return response()->json([
                'found' => true,
                'id' => $account->id,
                'title' => $account->title,
                'type' => 'account',
                'tel' => '',
                'head_id' => $account->head_id
            ]);
        }

        // Check Vendor
        $vendor = Vendor::where('vendor_id', $code)->orWhere('name', $code)->first();
        if ($vendor) {
            return response()->json([
                'found' => true,
                'id' => $vendor->id,
                'title' => $vendor->name,
                'type' => 'vendor',
                'tel' => $vendor->mobile
            ]);
        }

        // Check Customer
        $customer = Customer::where('customer_id', $code)->orWhere('customer_name', $code)->first();
        if ($customer) {
            return response()->json([
                'found' => true,
                'id' => $customer->id,
                'title' => $customer->customer_name,
                'type' => 'customer',
                'tel' => $customer->mobile
            ]);
        }

        return response()->json(['found' => false]);
    }

    public function preview(Request $request)
    {
        $type = $request->ac_type;
        $id = $request->ac_id;
        $report_mode = $request->report_mode; // 'details' or 'summary'
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $orientation = $request->orientation ?? 'portrait';

        if (!$id || !$type) {
            return back()->with('error', 'Please select an account/party first.');
        }

        if ($type == 'account') {
            $account_info = Account::with('head')->findOrFail($id);
        } elseif ($type == 'customer') {
            $account_info = Customer::findOrFail($id);
        } else {
            $account_info = Vendor::findOrFail($id);
        }

        $openingBalance = $this->calculateOpeningBalance($type, $id, $startDate);
        
        if ($report_mode == 'summary') {
            $transactions = $this->fetchSummaryTransactions($type, $id, $startDate, $endDate);
            return view('admin_panel.reports.general_ledger.preview_summary', compact('transactions', 'openingBalance', 'startDate', 'endDate', 'account_info', 'type', 'orientation'));
        } else {
            $transactions = $this->fetchTransactions($type, $id, $startDate, $endDate);
            return view('admin_panel.reports.general_ledger.preview_details', compact('transactions', 'openingBalance', 'startDate', 'endDate', 'account_info', 'type', 'orientation'));
        }
    }

    private function fetchSummaryTransactions($type, $id, $start, $end) {
        $typeArray = ($type === 'customer') ? ['customer', 'walking', 'walkin'] : [$type];
        $transactions = [];

        if ($type == 'account') {
            return $this->fetchTransactions($type, $id, $start, $end);
        }

        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (SJ) - Aggregate
        $salesDateCol = $this->getDateColumn('sales');
        $sales = Sale::where('customer_id', $id)->whereIn('partyType', $typeArray)
            ->whereBetween(DB::raw($salesDateCol), [$start, $end])
            ->get();
        foreach ($sales as $sale) {
            $transactions[] = [
                'created_at' => $sale->created_at,
                'id' => $sale->id,
                'date' => $sale->entry_date ?: $sale->created_at,
                'ref' => 'SJ',
                'inv' => $sale->invoice_no,
                'desc' => 'Sales',
                'qty' => (float)$sale->quantity,
                'debit' => (float)$sale->total_balance,
                'credit' => 0,
                'priority' => 10,
                'sort_inv' => preg_replace('/[^0-9]/', '', $sale->invoice_no)
            ];
        }

        // 2. Purchase Returns (PRJ) - Aggregate
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where('status', 'Posted')
            ->whereBetween(DB::raw($prDateCol), [$start, $end])
            ->get();
        foreach ($pReturns as $pr) {
            $transactions[] = [
                'created_at' => $pr->created_at,
                'id' => $pr->id,
                'date' => $pr->entry_date ?: $pr->created_at,
                'ref' => 'PRJ',
                'inv' => $pr->invoice_no,
                'desc' => 'Purchase Return',
                'qty' => (float)DB::table('purchase_return_items')->where('purchase_return_id', $pr->id)->sum('qty'),
                'debit' => (float)($pr->subtotal + $pr->wht),
                'credit' => (float)$pr->discount
            ];
        }

        // 3. Payments (PV)
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = PaymentVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
        foreach ($payments as $pv) {
            $transactions[] = [
                'created_at' => $pv->created_at,
                'id' => $pv->id,
                'date' => $pv->entry_date ?: $pv->created_at,
                'ref' => 'PV',
                'inv' => $pv->pvid,
                'desc' => $pv->remarks ?? 'Payment Voucher',
                'qty' => 0, 'debit' => (float)$pv->total_amount, 'credit' => 0
            ];
        }

        // 3.1 Expenses (EV) - Credit
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = \App\Models\ExpenseVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($evDateCol), [$start, $end])->get();
        foreach ($expenses as $ev) {
            $accIds = json_decode($ev->row_account_id, true) ?? [];
            $amounts = json_decode($ev->amount, true) ?? [];
            $narrIds = json_decode($ev->narration_id, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                if ($rowAmount <= 0) continue;

                $accName = DB::table('accounts')->where('id', $aid)->value('title');
                
                $narrText = '';
                if (isset($narrIds[$idx])) {
                    if (is_numeric($narrIds[$idx])) {
                        $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                    } else {
                        $narrText = $narrIds[$idx];
                    }
                }
                
                $descParts = [];
                if ($narrText) $descParts[] = $narrText;
                if (!empty($ev->remarks)) $descParts[] = $ev->remarks;
                
                $baseDesc = !empty($descParts) ? implode(' ; ', $descParts) : 'Expense Voucher';
                $desc = $accName ? $baseDesc . ' (Expense Account: ' . $accName . ')' : $baseDesc;

                $transactions[] = [
                    'created_at' => $ev->created_at,
                    'id' => $ev->id . '_' . $idx,
                    'date' => $ev->entry_date ?: $ev->created_at,
                    'ref' => 'EV',
                    'inv' => $ev->evid,
                    'desc' => $desc,
                    'qty' => 0, 'debit' => 0, 'credit' => $rowAmount
                ];
            }
        }

        // 3.2 Generic Vouchers (VO)
        $vouchers = DB::table('vouchers')->where('person', $id)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw("COALESCE(date, DATE(created_at))"), [$start, $end])->get();
        foreach ($vouchers as $v) {
            if (str_contains($v->narration ?? '', 'Discount on Sale Return Posted:')) {
                continue;
            }

            $ref = 'VO';
            $inv = $v->voucher_type;
            if (str_contains($v->narration ?? '', 'Discount on Sale:')) {
                $ref = 'SJ';
                $inv = trim(str_replace('Discount on Sale:', '', $v->narration));
            }

            $transactions[] = [
                'created_at' => $v->created_at,
                'id' => $v->id,
                'date' => $v->date ?: $v->created_at,
                'ref' => $ref,
                'inv' => $inv,
                'desc' => $v->narration ?? $v->voucher_type,
                'qty' => 0,
                'debit' => ($v->type == 'Debit') ? (float)$v->amount : 0,
                'credit' => ($v->type == 'Credit') ? (float)$v->amount : 0,
                'priority' => str_contains($v->narration ?? '', 'Discount on Sale:') ? 12 : 52,
                'sort_inv' => str_contains($v->narration ?? '', 'Discount on Sale:') ? preg_replace('/[^0-9]/', '', $v->narration) : preg_replace('/[^0-9]/', '', $v->voucher_type ?? '')
            ];
        }

        // 4. JV
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereJsonContains('party_type', $type)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if ($pid == $id) {
                    // For Summary Mode, we skip JVs linked to Purchases and Purchase Returns because the 'PJ'/'PRJ' line shows the Net Amount.
                    // Showing both would double-count the discount/tax impact.
                    if (str_starts_with($jv->jvid, 'PJ-') || str_starts_with($jv->jvid, 'PRJ-')) {
                        continue;
                    }

                    $desc = $jv->remarks ?? 'Journal Voucher';
                    
                    // Cleanup for Purchase-related JVs (if they somehow pass)
                    if (str_starts_with($inv, 'PJ-')) {
                        $inv_num = preg_replace('/^PJ-[A-Z]+-/', '', $inv);
                        $ref = 'PJ-' . $inv_num;
                        $inv = '';

                        $pTypes = json_decode($jv->party_type, true) ?? [];
                        $oppType = $pTypes[0] ?? null;
                        $oppId = $pIds[0] ?? null;

                        if ($oppType && $oppId) {
                            $oppType = strtolower($oppType);
                            if ($oppType === 'vendor') {
                                $partyName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $oppId)->value('name');
                                $desc = 'Vendor: ' . ($partyName ?: 'Unknown');
                            } elseif (in_array($oppType, ['customer', 'walkin', 'walking', 'subcustomer'])) {
                                $partyName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $oppId)->value('customer_name');
                                $desc = ucfirst($oppType) . ': ' . ($partyName ?: 'Unknown');
                            }
                        }
                    }

                    $transactions[] = [
                        'created_at' => $jv->created_at,
                        'id' => $jv->id,
                        'date' => $jv->entry_date ?: $jv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $desc,
                        'qty' => 0, 
                        'debit' => (float)($debits[$idx] ?? 0), 
                        'credit' => (float)($credits[$idx] ?? 0),
                        'priority' => 2 // Posting impacts come second
                    ];
                }
            }
        }

        // 5. Purchases (PJ) - Aggregate
        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where('status', 'Posted')
            ->whereBetween(DB::raw($pjDateCol), [$start, $end])
            ->get();
        foreach ($purchases as $p) {
            $transactions[] = [
                'created_at' => $p->created_at,
                'id' => $p->id,
                'date' => $p->entry_date ?: $p->created_at,
                'ref' => 'PJ',
                'inv' => $p->invoice_no,
                'desc' => 'Purchase',
                'qty' => (float)DB::table('purchase_items')->where('purchase_id', $p->id)->sum('qty'),
                'debit' => 0,
                'credit' => (float)$p->net_amount,
                'priority' => 1 // Items come first
            ];
        }

        // 6. Sale Returns (SRJ) - Aggregate
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $sReturns = SaleReturn::with('sale')->where('customer_id', $id)->whereIn('party_type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($srDateCol), [$start, $end])
            ->get();
        foreach ($sReturns as $sr) {
            $desc = 'Sale Return';
            if ($sr->sale) {
                $desc .= ' (SR ' . $sr->sale->invoice_no . ')';
            }
            $transactions[] = [
                'created_at' => $sr->created_at,
                'id' => $sr->id,
                'date' => $sr->entry_date ?: $sr->current_date,
                'ref' => 'SRJ',
                'inv' => preg_replace('/[^0-9]/', '', substr($sr->invoice_no, strlen('SR-'))) ?: $sr->invoice_no,
                'desc' => $desc,
                'qty' => (float)$sr->quantity,
                'debit' => 0,
                'credit' => (float)$sr->sub_total2,
                'priority' => 20
            ];
            if ((float)$sr->discount_amount > 0) {
                $descDisc = 'Discount';
                if ($sr->sale) {
                    $descDisc .= ' (SR ' . $sr->sale->invoice_no . ')';
                }
                $transactions[] = [
                    'created_at' => $sr->created_at,
                    'id' => $sr->id . '_disc',
                    'date' => $sr->entry_date ?: $sr->current_date,
                    'ref' => 'SRJ',
                    'inv' => preg_replace('/[^0-9]/', '', substr($sr->invoice_no, strlen('SR-'))) ?: $sr->invoice_no,
                    'desc' => $descDisc,
                    'qty' => 0,
                    'debit' => (float)$sr->discount_amount,
                    'credit' => 0,
                    'priority' => 21
                ];
            }
        }

        // 7. Receipts (RV)
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $receipts = ReceiptsVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $accIds = json_decode($rv->row_account_id, true) ?? [];
            $amounts = json_decode($rv->amount, true) ?? [];
            $narrIds = json_decode($rv->narration_id, true) ?? [];
            $discounts = json_decode($rv->discount_value, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                $rowDiscount = (float)($discounts[$idx] ?? 0);
                if ($rowAmount <= 0 && $rowDiscount <= 0) continue;

                $accName = DB::table('accounts')->where('id', $aid)->value('title');
                $narrText = '';
                if (isset($narrIds[$idx])) {
                    if (is_numeric($narrIds[$idx])) {
                        $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                    } else {
                        $narrText = $narrIds[$idx];
                    }
                }
                $descParts = [];
                if (!empty($rv->remarks) && !str_starts_with($rv->remarks, 'Auto-generated from Sale:')) {
                    $descParts[] = $rv->remarks;
                }
                $desc = !empty($descParts) ? implode(' ; ', $descParts) : 'Receipt Voucher';

                $ref = 'RV';
                $inv = $rv->rvid;
                if (str_contains($rv->remarks ?? '', 'Auto-generated from Sale:')) {
                    $ref = 'SJ';
                    $inv = trim(str_replace('Auto-generated from Sale:', '', $rv->remarks));
                }

                if ($rowAmount > 0) {
                    $transactions[] = [
                        'created_at' => $rv->created_at,
                        'id' => $rv->id . '_' . $idx,
                        'date' => $rv->entry_date ?: $rv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $desc,
                        'qty' => 0, 'debit' => 0, 'credit' => $rowAmount,
                        'priority' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? 11 : 60,
                        'sort_inv' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? preg_replace('/[^0-9]/', '', $rv->remarks) : preg_replace('/[^0-9]/', '', $rv->rvid ?? '')
                    ];
                }
                
                if ($rowDiscount > 0) {
                    $transactions[] = [
                        'created_at' => $rv->created_at,
                        'id' => $rv->id . '_disc_' . $idx,
                        'date' => $rv->entry_date ?: $rv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => "Discount",
                        'qty' => 0, 'debit' => 0, 'credit' => $rowDiscount,
                        'priority' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? 12 : 61,
                        'sort_inv' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? preg_replace('/[^0-9]/', '', $rv->remarks) : preg_replace('/[^0-9]/', '', $rv->rvid ?? '')
                    ];
                }
            }
        }

        // 7.1 Incomes (IV)
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $incomes = \App\Models\IncomeVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();
            
        foreach ($incomes as $iv) {
            $types = json_decode($iv->party_type, true) ?? [];
            $pIds = json_decode($iv->party_id, true) ?? [];
            $amounts = json_decode($iv->amount, true) ?? [];
            $narrIds = json_decode($iv->narration_id, true) ?? [];

            foreach ($pIds as $idx => $pid) {
                if ($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $rowAmount = (float)($amounts[$idx] ?? 0);
                    if ($rowAmount <= 0) continue;

                    $narrText = '';
                    if (isset($narrIds[$idx])) {
                        if (is_numeric($narrIds[$idx])) {
                            $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                        } else {
                            $narrText = $narrIds[$idx];
                        }
                    }
                    $depositAccName = DB::table('accounts')->where('id', $iv->account_id)->value('title');
                    
                    $descParts = [];
                    if ($narrText) $descParts[] = $narrText;
                    if (!empty($iv->remarks)) $descParts[] = $iv->remarks;
                    
                    $baseDesc = !empty($descParts) ? implode(' ; ', $descParts) : 'Income Voucher';
                    $desc = $depositAccName ? $baseDesc . ' (Deposit To: ' . $depositAccName . ')' : $baseDesc;

                    $transactions[] = [
                        'created_at' => $iv->created_at,
                        'id' => $iv->id . '_' . $idx,
                        'date' => $iv->entry_date ?: $iv->created_at,
                        'ref' => 'IV',
                        'inv' => $iv->ivid,
                        'desc' => $desc,
                        'qty' => 0, 
                        'debit' => $rowAmount,
                        'credit' => 0,
                        'priority' => 60
                    ];
                }
            }
        }

        usort($transactions, function ($a, $b) {
            $timeA = (string)($a['created_at'] ?? '');
            $timeB = (string)($b['created_at'] ?? '');
            
            if ($timeA !== '' && $timeB !== '' && $timeA !== $timeB) {
                return $timeA <=> $timeB;
            }

            // Fallback to Date if created_at is missing entirely
            $dateA = (string)($a['date'] ?? '');
            $dateB = (string)($b['date'] ?? '');
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            // Fallback to priority (for vouchers created at the exact same identical second)
            $prioA = (int)($a['priority'] ?? 60);
            $prioB = (int)($b['priority'] ?? 60);
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            // Final fallback to ID
            $idA = (string)($a['id'] ?? '');
            $idB = (string)($b['id'] ?? '');
            return $idA <=> $idB;
        });

        return $transactions;
    }

    public function calculateOpeningBalance($type, $id, $date)
    {
        $typeArray = ($type === 'customer') ? ['customer', 'walking', 'walkin'] : [$type];
        $balance = 0;
        
        if ($type == 'account') {
            $account = Account::find($id);
            if (!$account) return 0;
            $balance = (float)($account->opening_balance ?? 0);
            
            // For accounts, opening_balance in table is the CURRENT balance.
            // To get balance at START date, we subtract all transactions from START date to NOW.
            
            // RVs (Debit increases balance)
            $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
            $rvs = ReceiptsVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->where(DB::raw($rvDateCol), '>=', $date)->get();
            $rvSum = 0;
            foreach ($rvs as $rv) {
                $accIds = json_decode($rv->row_account_id, true) ?? [];
                $amounts = json_decode($rv->amount, true) ?? [];
                foreach ($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $rvSum += (float)($amounts[$idx] ?? 0);
                    }
                }
            }
            
            // PVs (Credit decreases balance)
            $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
            $pvs = PaymentVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->where(DB::raw($pvDateCol), '>=', $date)->get();
            $pvSum = 0;
            foreach ($pvs as $pv) {
                $accIds = json_decode($pv->row_account_id, true) ?? [];
                $amounts = json_decode($pv->amount, true) ?? [];
                foreach ($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $pvSum += (float)($amounts[$idx] ?? 0);
                    }
                }
            }
                
            // IVs
            $ivDateCol = $this->getDateColumn('income_vouchers');
            $ivs = \App\Models\IncomeVoucher::where(function($q) use ($id) {
                    $q->where('account_id', $id)
                      ->orWhereJsonContains('party_id', (string)$id)
                      ->orWhereJsonContains('party_id', (int)$id);
                })
                ->where(DB::raw($ivDateCol), '>=', $date)->get();
            $ivImpact = 0;
            foreach($ivs as $iv) {
                if ($iv->account_id == $id) {
                    $ivImpact -= (float)$iv->total_amount;
                }
                $pIds = json_decode($iv->party_id, true) ?? [];
                $amounts = json_decode($iv->amount, true) ?? [];
                $types = json_decode($iv->party_type, true) ?? [];
                foreach($pIds as $idx => $pid) {
                    if ($pid == $id && is_numeric($types[$idx] ?? '')) {
                        $ivImpact += (float)($amounts[$idx] ?? 0);
                    }
                }
            }

            // EVs
            $evDateCol = $this->getDateColumn('expense_vouchers');
            $evs = \App\Models\ExpenseVoucher::where(function($q) use ($id) {
                    $q->where('party_id', $id)
                      ->orWhereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->where(DB::raw($evDateCol), '>=', $date)->get();
            $evImpact = 0;
            foreach($evs as $ev) {
                if ($ev->party_id == $id && is_numeric($ev->type ?? '')) {
                    $evImpact += (float)$ev->total_amount;
                }
                $accIds = json_decode($ev->row_account_id, true) ?? [];
                $amounts = json_decode($ev->amount, true) ?? [];
                foreach($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $evImpact -= (float)($amounts[$idx] ?? 0);
                    }
                }
            }

            // JVs
            $jvDateCol = $this->getDateColumn('journal_vouchers');
            $jvs = JournalVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('party_id', (string)$id)
                      ->orWhereJsonContains('party_id', (int)$id);
                })
                ->where(DB::raw($jvDateCol), '>=', $date)->get();
            $jvImpact = 0;
            foreach($jvs as $jv) {
                $pIds = json_decode($jv->party_id, true) ?? [];
                $debits = json_decode($jv->debit, true) ?? [];
                $credits = json_decode($jv->credit, true) ?? [];
                foreach($pIds as $idx => $pid) {
                    if($pid == $id) {
                        $jvImpact += (float)($debits[$idx] ?? 0) - (float)($credits[$idx] ?? 0);
                    }
                }
            }
            
            return $balance - ($rvSum - $pvSum + $jvImpact + $ivImpact + $evImpact);
        }

        // For Party (Customer/Vendor)
        $party = ($type == 'customer') ? Customer::find($id) : Vendor::find($id);
        if (!$party) return 0;
        
        $balance = (float)($party->opening_balance ?? 0);
        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (Debit) - Use sub_total2 (Gross net of line discounts)
        $salesDateCol = $this->getDateColumn('sales');
        $sales = (float)Sale::where('customer_id', $id)->whereIn('partyType', $typeArray)
            ->where(DB::raw($salesDateCol), '<', $date)->sum('sub_total2');
        
        // 2. Purchase Returns (Debit)
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = (float)PurchaseReturn::where(function($q) use ($id, $type, $class) {
                if ($type == 'vendor') {
                    $q->where(function($q3) use ($id) {
                        $q3->where('vendor_id', $id)->where(function($q4) {
                            $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                        });
                    })->orWhere(function($q2) use ($id, $class) {
                        $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                    });
                } else {
                    $q->where('purchasable_id', $id)->where('purchasable_type', $class);
                }
            })->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($prDateCol), '<', $date)->sum('net_amount');

        // 3. Payments (Debit)
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = (float)PaymentVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($pvDateCol), '<', $date)->sum('total_amount');

        // 3.1 Expenses (was Debit, now moved to Credit calculation below)
        // Kept empty here to maintain numbering

        // 3.2 Generic Vouchers (Debit)
        $vDebits = (float)DB::table('vouchers')->where('person', $id)->where('type', 'Debit')
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw("COALESCE(date, DATE(created_at))"), '<', $date)->sum('amount');

        // 4. JV Debits
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->where(function($q) use ($typeArray) {
                foreach($typeArray as $t) { $q->orWhereJsonContains('party_type', $t); }
            })
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($jvDateCol), '<', $date)->get();
        $jvDebits = 0;
        foreach($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $types = json_decode($jv->party_type, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $jvDebits += (float)($debits[$idx] ?? 0);
                }
            }
        }

        // 4.5 AV Debits
        $avDateCol = $this->getDateColumn('adjustment_vouchers');
        $avsDebitList = \App\Models\AdjustmentVoucher::where('party_id', $id)
            ->whereIn('party_type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($avDateCol), '<', $date)->get();
        $avDebits = 0;
        foreach($avsDebitList as $av) {
            $avDebits += (float)$av->total_amount;
        }

        // 5. Purchases (Credit)
        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = (float)Purchase::where(function($q) use ($id, $type, $class) {
                if ($type == 'vendor') {
                    $q->where(function($q3) use ($id) {
                        $q3->where('vendor_id', $id)->where(function($q4) {
                            $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                        });
                    })->orWhere(function($q2) use ($id, $class) {
                        $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                    });
                } else {
                    $q->where('purchasable_id', $id)->where('purchasable_type', $class);
                }
            })->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($pjDateCol), '<', $date)->sum('net_amount');

        // 6. Sale Returns (Credit)
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $sReturns = (float)SaleReturn::where('customer_id', $id)->whereIn('party_type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($srDateCol), '<', $date)->sum('total_balance');

        // 7. Receipts (Credit)
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $rvsList = ReceiptsVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($rvDateCol), '<', $date)->get();
        $receipts = 0;
        foreach ($rvsList as $rv) {
            $receipts += (float)$rv->total_amount;
            $dArr = json_decode($rv->discount_value, true);
            if (is_array($dArr)) {
                foreach ($dArr as $d) {
                    $receipts += (float)$d;
                }
            }
        }
        
        // 7.1 Income (Debit)
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $ivList = \App\Models\IncomeVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($ivDateCol), '<', $date)->get();
        $incomes = 0;
        foreach($ivList as $iv) {
            $pIds = json_decode($iv->party_id, true) ?? [];
            $amounts = json_decode($iv->amount, true) ?? [];
            $types = json_decode($iv->party_type, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $incomes += (float)($amounts[$idx] ?? 0);
                }
            }
        }

        // 7.2 Generic Vouchers (Credit)
        $vCredits = (float)DB::table('vouchers')->where('person', $id)->where('type', 'Credit')
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw("COALESCE(date, DATE(created_at))"), '<', $date)->sum('amount');

        // 8. JV Credits
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->where(function($q) use ($typeArray) {
                foreach($typeArray as $t) { $q->orWhereJsonContains('party_type', $t); }
            })
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($jvDateCol), '<', $date)->get();
        $jvCredits = 0;
        foreach ($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $types = json_decode($jv->party_type, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            foreach ($pIds as $idx => $pid) {
                if ($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $jvCredits += (float)($credits[$idx] ?? 0);
                }
            }
        }

        // 8.5 AV Credits
        $avDateCol = $this->getDateColumn('adjustment_vouchers');
        $avsCreditList = \App\Models\AdjustmentVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('account_id', (string)$id)
                  ->orWhereJsonContains('account_id', (int)$id);
            })->whereIn('status', ['posted', 'Posted'])->where(DB::raw($avDateCol), '<', $date)->get();
        $avCredits = 0;
        foreach($avsCreditList as $av) {
            $accIds = json_decode($av->account_id, true) ?? [];
            $accHeads = json_decode($av->account_head, true) ?? [];
            $amounts = json_decode($av->amount, true) ?? [];
            foreach ($accIds as $idx => $aid) {
                if ($aid == $id && in_array($accHeads[$idx] ?? '', $typeArray)) {
                    $avCredits += (float)($amounts[$idx] ?? 0);
                }
            }
        }

        // 9. Expenses (Credit)
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = (float)\App\Models\ExpenseVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])
            ->where(DB::raw($evDateCol), '<', $date)->sum('total_amount');

        // 10. Customer Claims
        $claimDateCol = $this->getDateColumn('customer_claims');
        $claims = \App\Models\CustomerClaim::where('party_id', $id)
            ->whereIn('party_type', $typeArray)
            ->where('status', 'Posted')
            ->where(DB::raw($claimDateCol), '<', $date)->get();
            
        $claimCredits = 0;
        $claimDebits = 0;
        foreach ($claims as $claim) {
            $claimCredits += (float)$claim->sales_price;
            if ($claim->claim_type === 'credit_note') {
                $claimDebits += (float)$claim->replacement_sales_price;
            }
        }

        // 11. Claim Credit Notes (CIR)
        $crnDateCol = $this->getDateColumn('claim_credit_notes');
        $crNotes = (float)\App\Models\ClaimCreditNote::where('party_id', $id)
            ->where('party_type', $type == 'customer' ? 'customer' : 'vendor')
            ->where('status', 'Posted')
            ->where(DB::raw($crnDateCol), '<', $date)->sum('net_total');

        $balance += ($sales + $pReturns + $payments + $vDebits + $jvDebits + $avDebits + $incomes + $claimDebits + $crNotes) - ($purchases + $sReturns + $receipts + $vCredits + $jvCredits + $avCredits + $expenses + $claimCredits);
        
        return $balance;
    }

    public function fetchTransactions($type, $id, $start, $end) {
        $typeArray = ($type === 'customer') ? ['customer', 'walking', 'walkin'] : [$type];
        $transactions = [];

        if ($type == 'account') {
            // Receipts
            $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
            $rvs = ReceiptsVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
            
            foreach($rvs as $rv) {
                $accIds = json_decode($rv->row_account_id, true) ?? [];
                $amounts = json_decode($rv->amount, true) ?? [];
                $narrIds = json_decode($rv->narration_id, true) ?? [];
                
                // Get party name
                $partyName = '';
                if ($rv->type == 'customer') {
                    $partyName = DB::table('customers')->where('id', $rv->party_id)->value('customer_name');
                } elseif ($rv->type == 'vendor') {
                    $partyName = DB::table('vendors')->where('id', $rv->party_id)->value('name');
                }

                $ref = 'RV';
                $inv = $rv->rvid;

                if (str_starts_with($rv->remarks ?? '', 'Auto-generated from Sale:')) {
                    $ref = 'SJ';
                    $inv = trim(str_replace('Auto-generated from Sale:', '', $rv->remarks));
                }

                foreach ($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $rowAmount = (float)($amounts[$idx] ?? 0);
                        $rowNarr = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $rowNarr = $narrIds[$idx];
                            }
                        }

                        $accName = DB::table('accounts')->where('id', $aid)->value('title');

                        $descParts = [];
                        if ($rowNarr) $descParts[] = $rowNarr;
                        if (!empty($rv->remarks) && !str_starts_with($rv->remarks, 'Auto-generated from Sale:')) {
                            $descParts[] = $rv->remarks;
                        }
                        $desc = !empty($descParts) ? implode(' : ', $descParts) : 'Receipt Voucher';
                        
                        $desc = $partyName ? $desc . ' : ' . $partyName : $desc;

                        $transactions[] = [
                            'created_at' => $rv->created_at,
                            'id' => $rv->id . '_' . $idx,
                            'date' => $rv->entry_date ?: $rv->created_at,
                            'ref' => $ref,
                            'inv' => $inv,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 'debit' => $rowAmount, 'credit' => 0,
                            'priority' => 60
                        ];
                    }
                }
            }
            // Payments
            $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
            $pvs = PaymentVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
            foreach($pvs as $pv) {
                $accIds = json_decode($pv->row_account_id, true) ?? [];
                $amounts = json_decode($pv->amount, true) ?? [];
                $narrIds = json_decode($pv->narration_id, true) ?? [];
                
                // Get party name
                $partyName = '';
                if ($pv->type == 'customer') {
                    $partyName = DB::table('customers')->where('id', $pv->party_id)->value('customer_name');
                } elseif ($pv->type == 'vendor') {
                    $partyName = DB::table('vendors')->where('id', $pv->party_id)->value('name');
                }

                foreach ($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $rowAmount = (float)($amounts[$idx] ?? 0);
                        $rowNarr = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $rowNarr = $narrIds[$idx];
                            }
                        }

                        $descParts = [];
                        if ($rowNarr) $descParts[] = $rowNarr;
                        if (!empty($pv->remarks)) $descParts[] = $pv->remarks;
                        $baseDesc = !empty($descParts) ? implode(' ; ', $descParts) : 'Payment';
                        
                        $desc = $partyName ? $baseDesc . ' : ' . $partyName : $baseDesc;

                        $transactions[] = [
                            'created_at' => $pv->created_at,
                            'id' => $pv->id . '_' . $idx,
                            'date' => $pv->entry_date ?: $pv->created_at,
                            'ref' => 'PV',
                            'inv' => $pv->pvid,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => $rowAmount,
                            'priority' => 60
                        ];
                    }
                }
            }
            // Incomes
            $ivDateCol = $this->getDateColumn('income_vouchers');
            $ivs = \App\Models\IncomeVoucher::where(function($q) use ($id) {
                    $q->where('account_id', $id)
                      ->orWhereJsonContains('party_id', (string)$id)
                      ->orWhereJsonContains('party_id', (int)$id);
                })
                ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();
            foreach($ivs as $iv) {
                $pIds = json_decode($iv->party_id, true) ?? [];
                $amounts = json_decode($iv->amount, true) ?? [];
                $types = json_decode($iv->party_type, true) ?? [];
                $narrIds = json_decode($iv->narration_id, true) ?? [];

                if ($iv->account_id == $id) {
                    foreach($pIds as $idx => $pid) {
                        $rowAmount = (float)($amounts[$idx] ?? 0);
                        if ($rowAmount <= 0) continue;

                        $rowNarr = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $rowNarr = $narrIds[$idx];
                            }
                        }

                        $partyName = '';
                        $pType = strtolower($types[$idx] ?? '');
                        if (is_numeric($pType) || $pType === 'expense') {
                            $partyName = DB::table('accounts')->where('id', $pid)->value('title');
                        } elseif ($pType === 'vendor') {
                            $partyName = DB::table('vendors')->where('id', $pid)->value('name');
                        } elseif (in_array($pType, ['customer', 'walkin', 'subcustomer'])) {
                            $partyName = DB::table('customers')->where('id', $pid)->value('customer_name');
                        }

                        $baseDesc = $rowNarr ?: ($iv->remarks ?? 'Income Voucher (Deposit)');
                        $desc = $partyName ? $baseDesc . ' ; ' . $partyName : $baseDesc;

                        $transactions[] = [
                            'created_at' => $iv->created_at,
                            'id' => $iv->id . '_h_' . $idx,
                            'date' => $iv->entry_date ?: $iv->created_at,
                            'ref' => 'IV',
                            'inv' => $iv->ivid,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 'debit' => $rowAmount, 'credit' => 0,
                            'priority' => 60
                        ];
                    }
                }
                
                foreach($pIds as $idx => $pid) {
                    $pType = strtolower($types[$idx] ?? '');
                    if ($pid == $id && (is_numeric($pType) || $pType === 'expense')) {
                        $rowNarr = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $rowNarr = $narrIds[$idx];
                            }
                        }
                        
                        $hType = strtolower($iv->account_head ?? '');
                        $depositAccName = '';
                        if ($hType === 'vendor') {
                            $depositAccName = DB::table('vendors')->where('id', $iv->account_id)->value('name');
                        } elseif (in_array($hType, ['customer', 'walkin', 'subcustomer'])) {
                            $depositAccName = DB::table('customers')->where('id', $iv->account_id)->value('customer_name');
                        } else {
                            $depositAccName = DB::table('accounts')->where('id', $iv->account_id)->value('title');
                        }
                        $baseDesc = $rowNarr ?: ($iv->remarks ?? 'Income Voucher (Source)');
                        $desc = $depositAccName ? $baseDesc . ' ; ' . $depositAccName : $baseDesc;
                        
                        $transactions[] = [
                            'created_at' => $iv->created_at,
                            'id' => $iv->id . '_' . $idx,
                            'date' => $iv->entry_date ?: $iv->created_at,
                            'ref' => 'IV',
                            'inv' => $iv->ivid,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)($amounts[$idx] ?? 0),
                            'priority' => 60
                        ];
                    }
                }
            }

            // Expenses
            $evDateCol = $this->getDateColumn('expense_vouchers');
            $evs = \App\Models\ExpenseVoucher::where(function($q) use ($id) {
                    $q->where('party_id', $id)
                      ->orWhereJsonContains('row_account_id', (string)$id)
                      ->orWhereJsonContains('row_account_id', (int)$id);
                })
                ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($evDateCol), [$start, $end])->get();
            foreach($evs as $ev) {
                if ($ev->party_id == $id && is_numeric($ev->type ?? '')) {
                    $transactions[] = [
                        'created_at' => $ev->created_at,
                        'id' => $ev->id . '_h',
                        'date' => $ev->entry_date ?: $ev->created_at,
                        'ref' => 'EV',
                        'inv' => $ev->evid,
                        'desc' => $ev->remarks ?? 'Expense Voucher (Expense Head)',
                        'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$ev->total_amount,
                        'priority' => 60
                    ];
                }
                $accIds = json_decode($ev->row_account_id, true) ?? [];
                $amounts = json_decode($ev->amount, true) ?? [];
                $narrIds = json_decode($ev->narration_id, true) ?? [];
                foreach($accIds as $idx => $aid) {
                    if ($aid == $id) {
                        $rowNarr = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $rowNarr = $narrIds[$idx];
                            }
                        }
                        
                        $partyName = '';
                        if (is_numeric($ev->type ?? '')) {
                            $partyName = DB::table('accounts')->where('id', $ev->party_id)->value('title');
                        } elseif ($ev->type === 'vendor') {
                            $partyName = DB::table('vendors')->where('id', $ev->party_id)->value('name');
                        } elseif ($ev->type === 'customer' || $ev->type === 'walkin' || $ev->type === 'subcustomer') {
                            $partyName = DB::table('customers')->where('id', $ev->party_id)->value('customer_name');
                        }

                        $baseDesc = $rowNarr ?: ($ev->remarks ?? 'Expense Voucher (Source)');
                        $desc = $partyName ? $baseDesc . ' ; ' . $partyName : $baseDesc;

                        $transactions[] = [
                            'created_at' => $ev->created_at,
                            'id' => $ev->id . '_' . $idx,
                            'date' => $ev->entry_date ?: $ev->created_at,
                            'ref' => 'EV',
                            'inv' => $ev->evid,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 'debit' => (float)($amounts[$idx] ?? 0), 'credit' => 0,
                            'priority' => 60
                        ];
                    }
                }
            }
            // JVs
            $jvDateCol = $this->getDateColumn('journal_vouchers');
            $jvs = JournalVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('party_id', (string)$id)
                      ->orWhereJsonContains('party_id', (int)$id);
                })
                ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
            foreach($jvs as $jv) {
                $pIds = json_decode($jv->party_id, true) ?? [];
                $debits = json_decode($jv->debit, true) ?? [];
                $credits = json_decode($jv->credit, true) ?? [];
                foreach($pIds as $idx => $pid) {
                    if ($pid == $id) {
                        $ref = 'JV';
                        $inv = $jv->jvid;
                        $desc = $jv->remarks ?? 'Journal Voucher';
                        
                        if (str_starts_with($inv, 'PJ-') || str_starts_with($inv, 'PRJ-')) {
                            $isPrj = str_starts_with($inv, 'PRJ-');
                            $inv_num = preg_replace('/^(PRJ|PJ)-[A-Z]+-/', '', $inv);
                            $ref = $isPrj ? 'PRJ' : 'PJ';
                            $inv = $inv_num;

                            $pTypes = json_decode($jv->party_type, true) ?? [];
                            $oppType = $pTypes[0] ?? null;
                            $oppId = $pIds[0] ?? null;

                            if ($oppType && $oppId) {
                                $oppType = strtolower($oppType);
                                if ($oppType === 'vendor') {
                                    $partyName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $oppId)->value('name');
                                    $desc = 'Vendor: ' . ($partyName ?: 'Unknown');
                                } elseif (in_array($oppType, ['customer', 'walkin', 'walking', 'subcustomer'])) {
                                    $partyName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $oppId)->value('customer_name');
                                    $desc = ucfirst($oppType) . ': ' . ($partyName ?: 'Unknown');
                                }
                            }
                        }

                        $transactions[] = [
                            'created_at' => $jv->created_at,
                            'id' => $jv->id,
                            'date' => $jv->entry_date ?: $jv->created_at,
                            'ref' => $ref,
                            'inv' => $inv,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 
                            'debit' => (float)($debits[$idx] ?? 0), 
                            'credit' => (float)($credits[$idx] ?? 0),
                            'priority' => str_starts_with($jv->jvid, 'PJ-') ? 2 : 50
                        ];
                    }
                }
            }

            // AVs
            $avDateCol = $this->getDateColumn('adjustment_vouchers');
            $avs = \App\Models\AdjustmentVoucher::whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($avDateCol), [$start, $end])
                ->where(function($q) use ($id) {
                    $q->where('party_id', $id)
                      ->orWhereJsonContains('account_id', (string)$id)
                      ->orWhereJsonContains('account_id', (int)$id);
                })->get();
            
            foreach($avs as $av) {
                $accIds = json_decode($av->account_id, true) ?? [];
                $amounts = json_decode($av->amount, true) ?? [];
                $narrIds = json_decode($av->narration_id, true) ?? [];
                $accHeads = json_decode($av->account_head, true) ?? [];

                // Header check
                if ($av->party_id == $id && is_numeric($av->party_type)) {
                    foreach ($accIds as $i => $a_id) {
                        $rType = $accHeads[$i] ?? '';
                        $destName = '';
                        if ($rType === 'vendor') {
                            $destName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $a_id)->value('name');
                        } elseif ($rType === 'customer' || $rType === 'walkin') {
                            $destName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $a_id)->value('customer_name');
                        } else {
                            $destName = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $a_id)->value('title');
                        }
                        
                        $headerNarr = '';
                        if (isset($narrIds[$i])) {
                            if (is_numeric($narrIds[$i])) {
                                $headerNarr = \Illuminate\Support\Facades\DB::table('narrations')->where('id', $narrIds[$i])->value('narration');
                            } else {
                                $headerNarr = $narrIds[$i];
                            }
                        }
                        
                        $headerDescParts = [];
                        if ($headerNarr) $headerDescParts[] = $headerNarr;
                        if (!empty($av->remarks)) $headerDescParts[] = $av->remarks;
                        if ($destName) $headerDescParts[] = $destName;
                        $headerDesc = !empty($headerDescParts) ? implode(' ; ', $headerDescParts) : 'Adjustment Voucher';

                        $rowAmt = (float)($amounts[$i] ?? 0);
                        if ($rowAmt > 0) {
                            $transactions[] = [
                                'created_at' => $av->created_at,
                                'id' => $av->id . '_h_' . $i,
                                'date' => $av->entry_date ?: $av->created_at,
                                'ref' => 'AV',
                                'inv' => $av->avid,
                                'desc' => $headerDesc,
                                'price' => 0, 'qty' => 0, 
                                'debit' => $rowAmt, 
                                'credit' => 0,
                                'priority' => 61
                            ];
                        }
                    }
                }

                // Row check
                foreach($accIds as $idx => $aid) {
                    $rowType = $accHeads[$idx] ?? '';
                    if ($aid == $id && is_numeric($rowType)) {
                        $narrText = '';
                        if (isset($narrIds[$idx])) {
                            if (is_numeric($narrIds[$idx])) {
                                $narrText = \Illuminate\Support\Facades\DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                            } else {
                                $narrText = $narrIds[$idx];
                            }
                        }

                        $descParts = [];
                        if ($narrText) $descParts[] = $narrText;
                        if (!empty($av->remarks)) $descParts[] = $av->remarks;

                        $sourceName = '';
                        $pType = $av->party_type;
                        $pId = $av->party_id;
                        if ($pType === 'vendor') {
                            $sourceName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $pId)->value('name');
                        } elseif ($pType === 'customer' || $pType === 'walkin') {
                            $sourceName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $pId)->value('customer_name');
                        } else {
                            $sourceName = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $pId)->value('title');
                        }
                        if ($sourceName) $descParts[] = $sourceName;

                        $desc = !empty($descParts) ? implode(' ; ', $descParts) : 'Adjustment Voucher';

                        $transactions[] = [
                            'created_at' => $av->created_at,
                            'id' => $av->id . '_' . $idx,
                            'date' => $av->entry_date ?: $av->created_at,
                            'ref' => 'AV',
                            'inv' => $av->avid,
                            'desc' => $desc,
                            'price' => 0, 'qty' => 0, 
                            'debit' => 0, 
                            'credit' => (float)($amounts[$idx] ?? 0),
                            'priority' => 61
                        ];
                    }
                }
            }

            // Sort by Date, Priority, then created_at for chronological order
            usort($transactions, function ($a, $b) { 
                $dateA = strtotime(substr($a['date'], 0, 10));
                $dateB = strtotime(substr($b['date'], 0, 10));
                if ($dateA != $dateB) {
                    return $dateA - $dateB;
                }

                $prioA = (int)($a['priority'] ?? 60);
                $prioB = (int)($b['priority'] ?? 60);
                if ($prioA !== $prioB) {
                    return $prioA - $prioB;
                }

                $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
                if ($timeA != $timeB && $timeA !== 0 && $timeB !== 0) {
                    return $timeA - $timeB;
                }

                $idA = $a['id'] ?? 0;
                $idB = $b['id'] ?? 0;
                if (is_numeric($idA) && is_numeric($idB)) {
                    return $idA - $idB;
                }
                return strcmp((string)$idA, (string)$idB);
            });
            return $transactions;
        }

        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (SJ) - Debit
        $salesDateCol = $this->getDateColumn('sales');
        $sales = Sale::where('customer_id', $id)->whereIn('partyType', $typeArray)
            ->whereBetween(DB::raw($salesDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $qty = (float)$item->sales_qty;
                $price = (float)$item->sales_price;
                $disc = (float)$item->discount_amount;
                $rate = (float)$item->sales_rate;
                
                $finalPrice = $rate ?: (($qty > 0) ? ($price - ($disc / $qty)) : $price);

                $transactions[] = [
                    'created_at' => $sale->created_at,
                    'id' => $item->id,
                    'date' => $sale->entry_date ?: $sale->created_at,
                    'ref' => 'SJ',
                    'inv' => $sale->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product') . ' : ' . ($item->items ?? ''),
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => (float)$item->amount,
                    'credit' => 0,
                    'priority' => 10, // SJ first
                    'sort_inv' => preg_replace('/[^0-9]/', '', $sale->invoice_no)
                ];
            }
        }

        // 2. Purchase Returns (PRJ) - Debit
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                if ($type == 'vendor') {
                    $q->where(function($q3) use ($id) {
                        $q3->where('vendor_id', $id)->where(function($q4) {
                            $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                        });
                    })->orWhere(function($q2) use ($id, $class) {
                        $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                    });
                } else {
                    $q->where('purchasable_id', $id)->where('purchasable_type', $class);
                }
            })->where('status', 'Posted')
            ->whereBetween(DB::raw($prDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($pReturns as $pr) {
            foreach ($pr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $qty = (float)$item->qty;
                $price = (float)($item->purchase_rate ?: $item->price);
                $totalDisc = (float)$item->item_discount;
                $netPrice = ($qty > 0) ? ($price - ($totalDisc / $qty)) : $price;

                $transactions[] = [
                    'created_at' => $pr->created_at,
                    'id' => $item->id,
                    'date' => $pr->entry_date ?: $pr->created_at,
                    'ref' => 'PRJ',
                    'inv' => $pr->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => $netPrice,
                    'qty' => $qty,
                    'debit' => (float)$item->line_total,
                    'credit' => 0,
                    'priority' => 40 // PRJ after PJ
                ];
            }

            if ($pr->wht > 0) {
                $whtHeadName = 'WHT Deduction (Tax)';
                if ($pr->wht_account_id) {
                    $whtAcc = \App\Models\Account::find($pr->wht_account_id);
                    if ($whtAcc) $whtHeadName = $whtAcc->title;
                } elseif ($pr->purchase_id) {
                    $purchase = \App\Models\Purchase::find($pr->purchase_id);
                    if ($purchase && $purchase->wht_account_id) {
                        $whtAcc = \App\Models\Account::find($purchase->wht_account_id);
                        if ($whtAcc) $whtHeadName = $whtAcc->title;
                    }
                }
                $transactions[] = [
                    'created_at' => $pr->created_at,
                    'id' => $pr->id . '_wht',
                    'date' => $pr->entry_date ?: $pr->created_at,
                    'ref' => 'PRJ',
                    'inv' => $pr->invoice_no,
                    'desc' => $whtHeadName,
                    'price' => 0,
                    'qty' => 0,
                    'debit' => (float)$pr->wht,
                    'credit' => 0,
                    'priority' => 41
                ];
            }

            if ($pr->discount > 0) {
                $transactions[] = [
                    'created_at' => $pr->created_at,
                    'id' => $pr->id . '_disc',
                    'date' => $pr->entry_date ?: $pr->created_at,
                    'ref' => 'PRJ',
                    'inv' => $pr->invoice_no,
                    'desc' => 'Purchase Return Discount',
                    'price' => 0,
                    'qty' => 0,
                    'debit' => 0,
                    'credit' => (float)$pr->discount,
                    'priority' => 42
                ];
            }
        }

        // 3. Payments (PV) - Debit
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = PaymentVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
        foreach ($payments as $pv) {
            $accIds = json_decode($pv->row_account_id, true) ?? [];
            $amounts = json_decode($pv->amount, true) ?? [];
            $narrIds = json_decode($pv->narration_id, true) ?? [];
            $discounts = json_decode($pv->discount_value, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                $rowDiscount = (float)($discounts[$idx] ?? 0);
                if ($rowAmount <= 0 && $rowDiscount <= 0) continue;

                $accName = DB::table('accounts')->where('id', $aid)->value('title');
                $narrText = '';
                if (isset($narrIds[$idx])) {
                    $narrText = is_numeric($narrIds[$idx]) ? DB::table('narrations')->where('id', $narrIds[$idx])->value('narration') : $narrIds[$idx];
                }
                
                $descParts = [];
                if ($narrText) $descParts[] = $narrText;
                if (!empty($pv->remarks)) $descParts[] = $pv->remarks;
                $desc = !empty($descParts) ? implode(' : ', $descParts) : 'Payment Voucher';

                if ($rowAmount > 0) {
                    $transactions[] = [
                        'created_at' => $pv->created_at,
                        'id' => $pv->id . '_' . $idx,
                        'date' => $pv->entry_date ?: $pv->created_at,
                        'ref' => 'PV',
                        'inv' => $pv->pvid,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0, 'debit' => $rowAmount, 'credit' => 0,
                        'priority' => 50
                    ];
                }

                if ($rowDiscount > 0) {
                    $transactions[] = [
                        'created_at' => $pv->created_at,
                        'id' => $pv->id . '_disc_' . $idx,
                        'date' => $pv->entry_date ?: $pv->created_at,
                        'ref' => 'PV',
                        'inv' => $pv->pvid,
                        'desc' => 'Discount',
                        'price' => 0, 'qty' => 0, 'debit' => $rowDiscount, 'credit' => 0,
                        'priority' => 51
                    ];
                }
            }
        }

        // 3.1 Expenses (EV) - Credit
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = \App\Models\ExpenseVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($evDateCol), [$start, $end])->get();
        foreach ($expenses as $ev) {
            $accIds = json_decode($ev->row_account_id, true) ?? [];
            $amounts = json_decode($ev->amount, true) ?? [];
            $narrIds = json_decode($ev->narration_id, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                if ($rowAmount <= 0) continue;

                $accName = DB::table('accounts')->where('id', $aid)->value('title');
                
                $narrText = '';
                if (isset($narrIds[$idx])) {
                    if (is_numeric($narrIds[$idx])) {
                        $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                    } else {
                        $narrText = $narrIds[$idx];
                    }
                }
                
                $descParts = [];
                if ($narrText) $descParts[] = $narrText;
                if (!empty($ev->remarks)) $descParts[] = $ev->remarks;
                
                $baseDesc = !empty($descParts) ? implode(' ; ', $descParts) : 'Expense Voucher';
                $desc = $accName ? $baseDesc . ' ; ' . $accName : $baseDesc;

                $transactions[] = [
                    'created_at' => $ev->created_at,
                    'id' => $ev->id . '_' . $idx,
                    'date' => $ev->entry_date ?: $ev->created_at,
                    'ref' => 'EV',
                    'inv' => $ev->evid,
                    'desc' => $desc,
                    'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => $rowAmount,
                    'priority' => 51
                ];
            }
        }

        // 3.2 Generic Vouchers (Debit/Credit)
        $vouchers = DB::table('vouchers')->where('person', $id)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw("COALESCE(date, DATE(created_at))"), [$start, $end])->get();
        foreach ($vouchers as $v) {
            if (str_contains($v->narration ?? '', 'Discount on Sale Return Posted:')) {
                continue;
            }

            $ref = 'VO';
            $inv = $v->voucher_type;
            $desc = $v->narration ?? $v->voucher_type;
            if (str_contains($v->narration ?? '', 'Discount on Sale:')) {
                $ref = 'SJ';
                $inv = trim(str_replace('Discount on Sale:', '', $v->narration));
                
                $sale = \App\Models\Sale::where('invoice_no', $inv)->first();
                if ($sale && $sale->discount_account_id) {
                    $acc = \App\Models\Account::with('head')->find($sale->discount_account_id);
                    if ($acc) {
                        $desc = 'Discount ; ' . ($acc->head->name ?? '') . ' ; ' . $acc->title;
                    }
                } else {
                    $desc = 'Discount ; Sale';
                }
            }

                $transactions[] = [
                    'created_at' => $v->created_at,
                    'id' => $v->id,
                    'date' => $v->date ?: $v->created_at,
                    'ref' => $ref,
                    'inv' => $inv,
                    'desc' => $desc,
                    'price' => 0, 'qty' => 0,
                    'debit' => ($v->type == 'Debit') ? (float)$v->amount : 0,
                    'credit' => ($v->type == 'Credit') ? (float)$v->amount : 0,
                    'priority' => str_contains($v->narration ?? '', 'Discount on Sale:') ? 12 : 52,
                    'sort_inv' => str_contains($v->narration ?? '', 'Discount on Sale:') ? preg_replace('/[^0-9]/', '', $v->narration) : preg_replace('/[^0-9]/', '', $v->voucher_type ?? '')
                ];
        }

        // 4. JV (JV) - Debit/Credit
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->where(function($q) use ($typeArray) {
                foreach($typeArray as $t) { $q->orWhereJsonContains('party_type', $t); }
            })
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $types = json_decode($jv->party_type, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            $narrIds = json_decode($jv->narration_id, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if ($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $ref = 'JV';
                    $inv = $jv->jvid;
                    // Skip PRJ-WHT JVs in detailed mode to avoid duplication since PRJ block handles it natively
                    if (str_starts_with($inv, 'PRJ-WHT')) {
                        continue;
                    }

                    // Cleanup for Purchase-related JVs (e.g. PJ-ALLOC-50 -> PJ 50)
                    if (str_starts_with($inv, 'PJ-')) {
                        $ref = 'PJ';
                        $inv = preg_replace('/^PJ-[A-Z]+-/', '', $inv);
                    }

                    $priority = 60; // General JV
                    if (str_contains($jv->jvid, 'WHT')) $priority = 31;
                    if (str_contains($jv->jvid, 'ALLOC')) $priority = 32;

                    $narrText = '';
                    if (isset($narrIds[$idx])) {
                        if (is_numeric($narrIds[$idx])) {
                            $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                        } else {
                            $narrText = $narrIds[$idx];
                        }
                    }

                    $descParts = [];
                    if ($narrText) $descParts[] = $narrText;
                    if (!empty($jv->remarks)) $descParts[] = $jv->remarks;

                    $desc = !empty($descParts) ? implode(' ; ', $descParts) : 'Journal Voucher';

                    if (str_starts_with($jv->jvid, 'SJ-DISC-')) {
                        $otherIdx = $idx == 0 ? 1 : 0;
                        $accId = $pIds[$otherIdx] ?? null;
                        if ($accId) {
                            $acc = \App\Models\Account::with('head')->find($accId);
                            if ($acc) {
                                $desc = 'Discount ; ' . ($acc->head->name ?? '') . ' ; ' . $acc->title;
                            }
                        } else {
                            $desc = 'Discount ; Sale';
                        }
                    }

                    $transactions[] = [
                        'created_at' => $jv->created_at,
                        'id' => $jv->id,
                        'date' => $jv->entry_date ?: $jv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0, 
                        'debit' => (float)($debits[$idx] ?? 0), 
                        'credit' => (float)($credits[$idx] ?? 0),
                        'priority' => $priority
                    ];
                }
            }
        }

        // 4.5 AV (Adjustment Voucher)
        $avDateCol = $this->getDateColumn('adjustment_vouchers');
        $avs = \App\Models\AdjustmentVoucher::whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($avDateCol), [$start, $end])
            ->where(function($q) use ($id, $typeArray) {
                $q->where(function($q1) use ($id, $typeArray) {
                    $q1->where('party_id', $id)->whereIn('party_type', $typeArray);
                });
                $q->orWhere(function($q2) use ($id) {
                    $q2->whereJsonContains('account_id', (string)$id)
                       ->orWhereJsonContains('account_id', (int)$id);
                });
            })->get();

        foreach ($avs as $av) {
            $narrIds = json_decode($av->narration_id, true) ?? [];
            
            // Header match check
            if ($av->party_id == $id && in_array($av->party_type, $typeArray)) {
                $accIds = json_decode($av->account_id, true) ?? [];
                $accHeads = json_decode($av->account_head, true) ?? [];
                $amounts = json_decode($av->amount, true) ?? [];
                
                foreach ($accIds as $i => $a_id) {
                    $rType = $accHeads[$i] ?? '';
                    $destName = '';
                    if ($rType === 'vendor') {
                        $destName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $a_id)->value('name');
                    } elseif ($rType === 'customer' || $rType === 'walkin') {
                        $destName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $a_id)->value('customer_name');
                    } else {
                        $destName = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $a_id)->value('title');
                    }
                    
                    $headerNarr = '';
                    if (isset($narrIds[$i])) {
                        if (is_numeric($narrIds[$i])) {
                            $headerNarr = \Illuminate\Support\Facades\DB::table('narrations')->where('id', $narrIds[$i])->value('narration');
                        } else {
                            $headerNarr = $narrIds[$i];
                        }
                    }
                    
                    $headerDescParts = [];
                    if ($headerNarr) $headerDescParts[] = $headerNarr;
                    if (!empty($av->remarks)) $headerDescParts[] = $av->remarks;
                    if ($destName) $headerDescParts[] = $destName;
                    $headerDesc = !empty($headerDescParts) ? implode(' ; ', $headerDescParts) : 'Adjustment Voucher';

                    $rowAmt = (float)($amounts[$i] ?? 0);
                    if ($rowAmt > 0) {
                        $transactions[] = [
                            'created_at' => $av->created_at,
                            'id' => $av->id . '_h_' . $i,
                            'date' => $av->entry_date ?: $av->created_at,
                            'ref' => 'AV',
                            'inv' => $av->avid,
                            'desc' => $headerDesc,
                            'price' => 0, 'qty' => 0,
                            'debit' => $rowAmt,
                            'credit' => 0,
                            'priority' => 61
                        ];
                    }
                }
            }

            // Row match check
            $accIds = json_decode($av->account_id, true) ?? [];
            $accHeads = json_decode($av->account_head, true) ?? [];
            $amounts = json_decode($av->amount, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowType = $accHeads[$idx] ?? '';
                if ($aid == $id && in_array($rowType, $typeArray)) {
                    $narrText = '';
                    if (isset($narrIds[$idx])) {
                        if (is_numeric($narrIds[$idx])) {
                            $narrText = \Illuminate\Support\Facades\DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                        } else {
                            $narrText = $narrIds[$idx];
                        }
                    }

                    $descParts = [];
                    if ($narrText) $descParts[] = $narrText;
                    if (!empty($av->remarks)) $descParts[] = $av->remarks;

                    $sourceName = '';
                    $pType = $av->party_type;
                    $pId = $av->party_id;
                    if ($pType === 'vendor') {
                        $sourceName = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $pId)->value('name');
                    } elseif ($pType === 'customer' || $pType === 'walkin') {
                        $sourceName = \Illuminate\Support\Facades\DB::table('customers')->where('id', $pId)->value('customer_name');
                    } else {
                        $sourceName = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $pId)->value('title');
                    }
                    if ($sourceName) $descParts[] = $sourceName;

                    $desc = !empty($descParts) ? implode(' ; ', $descParts) : 'Adjustment Voucher';

                    $transactions[] = [
                        'created_at' => $av->created_at,
                        'id' => $av->id,
                        'date' => $av->entry_date ?: $av->created_at,
                        'ref' => 'AV',
                        'inv' => $av->avid,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0,
                        'debit' => 0,
                        'credit' => (float)($amounts[$idx] ?? 0),
                        'priority' => 61
                    ];
                }
            }
        }

        // 5. Purchases (PJ) - Credit
        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = Purchase::where(function($q) use ($id, $type, $class) {
                if ($type == 'vendor') {
                    $q->where(function($q3) use ($id) {
                        $q3->where('vendor_id', $id)->where(function($q4) {
                            $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                        });
                    })->orWhere(function($q2) use ($id, $class) {
                        $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                    });
                } else {
                    $q->where('purchasable_id', $id)->where('purchasable_type', $class);
                }
            })->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($pjDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
            
        foreach ($purchases as $p) {
            $sumLines = 0;
            foreach ($p->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $qty = (float)$item->qty;
                $price = (float)$item->price;
                $disc = (float)$item->item_discount;
                $rate = (float)$item->purchase_rate;
                
                $finalPrice = $rate ?: (($qty > 0) ? ($price - (($disc > 100) ? ($disc / $qty) : ($price * $disc / 100))) : $price);

                $sumLines += (float)$item->line_total;

                $transactions[] = [
                    'created_at' => $p->created_at,
                    'id' => $item->id,
                    'date' => $p->entry_date ?: $p->current_date ?: $p->created_at,
                    'ref' => 'PJ',
                    'inv' => preg_replace('/[^0-9]/', '', substr($p->invoice_no, strlen('PUR-'))) ?: $p->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => 0,
                    'credit' => (float)$item->line_total,
                    'priority' => 30 // PJ after SJ/SRJ
                ];
            }
        }

        // 6. Sale Returns (SRJ) - Details
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $sReturns = SaleReturn::with(['items.product.brandRelation', 'sale'])->where('customer_id', $id)->whereIn('party_type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($srDateCol), [$start, $end])
            ->get();
        foreach ($sReturns as $sr) {
            $originalInv = $sr->sale ? $sr->sale->invoice_no : '';
            foreach ($sr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $desc = ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product');
                if ($originalInv) {
                    $desc .= ' (SR ' . $originalInv . ')';
                }
                $qty = (float)$item->sales_qty;
                $price = (float)$item->sales_price;
                $disc = (float)$item->discount_amount;
                $finalPrice = ($qty > 0) ? ($price - ($disc / $qty)) : $price;

                $transactions[] = [
                    'created_at' => $sr->created_at,
                    'id' => $item->id,
                    'date' => $sr->entry_date ?: $sr->current_date,
                    'ref' => 'SRJ',
                    'inv' => preg_replace('/[^0-9]/', '', substr($sr->invoice_no, strlen('SR-'))) ?: $sr->invoice_no,
                    'desc' => $desc,
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => 0,
                    'credit' => (float)$item->amount,
                    'priority' => 20 // SRJ after SJ
                ];
            }
            if ((float)$sr->discount_amount > 0) {
                $descDisc = 'Discount';
                if ($originalInv) {
                    $descDisc .= ' (SR ' . $originalInv . ')';
                }
                $transactions[] = [
                    'created_at' => $sr->created_at,
                    'id' => $sr->id . '_disc',
                    'date' => $sr->entry_date ?: $sr->current_date,
                    'ref' => 'SRJ',
                    'inv' => preg_replace('/[^0-9]/', '', substr($sr->invoice_no, strlen('SR-'))) ?: $sr->invoice_no,
                    'desc' => $descDisc,
                    'price' => 0,
                    'qty' => 0,
                    'debit' => (float)$sr->discount_amount,
                    'credit' => 0,
                    'priority' => 21
                ];
            }
        }

        // 7. Receipts (RV) - Credit
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $receipts = ReceiptsVoucher::where('party_id', $id)->whereIn('type', $typeArray)
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $accIds = json_decode($rv->row_account_id, true) ?? [];
            $amounts = json_decode($rv->amount, true) ?? [];
            $narrIds = json_decode($rv->narration_id, true) ?? [];
            $discounts = json_decode($rv->discount_value, true) ?? [];

            foreach ($accIds as $idx => $aid) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                $rowDiscount = (float)($discounts[$idx] ?? 0);
                if ($rowAmount <= 0 && $rowDiscount <= 0) continue;

                $accName = DB::table('accounts')->where('id', $aid)->value('title');
                $narrText = '';
                if (isset($narrIds[$idx])) {
                    if (is_numeric($narrIds[$idx])) {
                        $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                    } else {
                        $narrText = $narrIds[$idx];
                    }
                }
                
                $descParts = [];
                if ($narrText) $descParts[] = $narrText;
                if (!empty($rv->remarks) && !str_starts_with($rv->remarks, 'Auto-generated from Sale:')) {
                    $descParts[] = $rv->remarks;
                }
                $desc = !empty($descParts) ? implode(' : ', $descParts) : 'Receipt Voucher';

                $ref = 'RV';
                $inv = $rv->rvid;
                if (str_contains($rv->remarks ?? '', 'Auto-generated from Sale:')) {
                    $ref = 'SJ';
                    $inv = trim(str_replace('Auto-generated from Sale:', '', $rv->remarks));
                }

                if ($rowAmount > 0) {
                    $transactions[] = [
                        'created_at' => $rv->created_at,
                        'id' => $rv->id . '_' . $idx, // Unique ID for items
                        'date' => $rv->entry_date ?: $rv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => $rowAmount,
                        'priority' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? 11 : 60,
                        'sort_inv' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? preg_replace('/[^0-9]/', '', $rv->remarks) : preg_replace('/[^0-9]/', '', $rv->rvid ?? '')
                    ];
                }

                if ($rowDiscount > 0) {
                    $transactions[] = [
                        'created_at' => $rv->created_at,
                        'id' => $rv->id . '_disc_' . $idx,
                        'date' => $rv->entry_date ?: $rv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => "Discount",
                        'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => $rowDiscount,
                        'priority' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? 12 : 61,
                        'sort_inv' => str_contains($rv->remarks ?? '', 'Auto-generated from Sale:') ? preg_replace('/[^0-9]/', '', $rv->remarks) : preg_replace('/[^0-9]/', '', $rv->rvid ?? '')
                    ];
                }
            }
        }

        // 7.1 Incomes (IV) - Debit
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $incomes = \App\Models\IncomeVoucher::where(function($q) use ($id, $typeArray) {
                $q->where(function($q2) use ($id, $typeArray) {
                    $q2->where('account_id', $id)
                       ->whereIn('account_head', $typeArray);
                })
                ->orWhereJsonContains('party_id', (string)$id)
                ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereIn('status', ['posted', 'Posted'])->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();
            
        foreach ($incomes as $iv) {
            $types = json_decode($iv->party_type, true) ?? [];
            $pIds = json_decode($iv->party_id, true) ?? [];
            $amounts = json_decode($iv->amount, true) ?? [];
            $narrIds = json_decode($iv->narration_id, true) ?? [];

            $hType = strtolower($iv->account_head ?? '');
            if ($iv->account_id == $id && in_array($hType, $typeArray)) {
                foreach($pIds as $idx => $pid) {
                    $rowAmount = (float)($amounts[$idx] ?? 0);
                    if ($rowAmount <= 0) continue;

                    $rowNarr = '';
                    if (isset($narrIds[$idx])) {
                        if (is_numeric($narrIds[$idx])) {
                            $rowNarr = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                        } else {
                            $rowNarr = $narrIds[$idx];
                        }
                    }

                    $partyName = '';
                    $pType = strtolower($types[$idx] ?? '');
                    if (is_numeric($pType) || $pType === 'expense') {
                        $partyName = DB::table('accounts')->where('id', $pid)->value('title');
                    } elseif ($pType === 'vendor') {
                        $partyName = DB::table('vendors')->where('id', $pid)->value('name');
                    } elseif (in_array($pType, ['customer', 'walkin', 'subcustomer'])) {
                        $partyName = DB::table('customers')->where('id', $pid)->value('customer_name');
                    }

                    $baseDesc = $rowNarr ?: ($iv->remarks ?? 'Income Voucher (Deposit)');
                    $desc = $partyName ? $baseDesc . ' ; ' . $partyName : $baseDesc;

                    $transactions[] = [
                        'created_at' => $iv->created_at,
                        'id' => $iv->id . '_h_' . $idx,
                        'date' => $iv->entry_date ?: $iv->created_at,
                        'ref' => 'IV',
                        'inv' => $iv->ivid,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0, 'debit' => $rowAmount, 'credit' => 0,
                        'priority' => 60
                    ];
                }
            }

            foreach ($pIds as $idx => $pid) {
                if ($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
                    $rowAmount = (float)($amounts[$idx] ?? 0);
                    if ($rowAmount <= 0) continue;

                    $narrText = '';
                    if (isset($narrIds[$idx])) {
                        if (is_numeric($narrIds[$idx])) {
                            $narrText = DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
                        } else {
                            $narrText = $narrIds[$idx];
                        }
                    }
                    
                    $hType = strtolower($iv->account_head ?? '');
                    $depositAccName = '';
                    if ($hType === 'vendor') {
                        $depositAccName = DB::table('vendors')->where('id', $iv->account_id)->value('name');
                    } elseif (in_array($hType, ['customer', 'walkin', 'subcustomer'])) {
                        $depositAccName = DB::table('customers')->where('id', $iv->account_id)->value('customer_name');
                    } else {
                        $depositAccName = DB::table('accounts')->where('id', $iv->account_id)->value('title');
                    }
                    
                    $descParts = [];
                    if ($narrText) $descParts[] = $narrText;
                    if (!empty($iv->remarks)) $descParts[] = $iv->remarks;
                    
                    $baseDesc = !empty($descParts) ? implode(' ; ', $descParts) : 'Income Voucher';
                    $desc = $depositAccName ? $baseDesc . ' ; ' . $depositAccName : $baseDesc;

                    $transactions[] = [
                        'created_at' => $iv->created_at,
                        'id' => $iv->id . '_' . $idx,
                        'date' => $iv->entry_date ?: $iv->created_at,
                        'ref' => 'IV',
                        'inv' => $iv->ivid,
                        'desc' => $desc,
                        'price' => 0, 'qty' => 0, 
                        'debit' => 0,
                        'credit' => $rowAmount,
                        'priority' => 60
                    ];
                }
            }
        }

        // 8. Customer Claims
        $claimDateCol = $this->getDateColumn('customer_claims');
        $claims = \App\Models\CustomerClaim::with(['product', 'replacementProduct'])
            ->where('party_id', $id)
            ->whereIn('party_type', $typeArray)
            ->where('status', 'Posted')
            ->whereBetween(DB::raw($claimDateCol), [$start, $end])
            ->get();
            
        foreach ($claims as $claim) {
            if ((float)$claim->sales_price > 0) {
                $transactions[] = [
                    'created_at' => $claim->created_at,
                    'id' => 'clm_' . $claim->id . '_f',
                    'date' => $claim->entry_date ?: substr((string)$claim->created_at, 0, 10),
                    'ref' => 'CLM',
                    'inv' => preg_replace('/[^0-9]/', '', $claim->claim_no ?? '0'),
                    'desc' => 'Claim Received: ' . ($claim->product->name ?? 'Battery') . ' (' . $claim->claim_no . ')',
                    'price' => 0, 'qty' => 1, 'debit' => 0, 'credit' => (float)$claim->sales_price,
                    'priority' => 30
                ];
            }
            if ($claim->claim_type === 'credit_note' && (float)$claim->replacement_sales_price > 0) {
                $transactions[] = [
                    'created_at' => $claim->created_at,
                    'id' => 'clm_' . $claim->id . '_r',
                    'date' => $claim->entry_date ?: substr((string)$claim->created_at, 0, 10),
                    'ref' => 'CLM',
                    'inv' => preg_replace('/[^0-9]/', '', $claim->claim_no ?? '0'),
                    'desc' => 'Claim Replacement: ' . ($claim->replacementProduct->name ?? 'Battery') . ' (' . $claim->claim_no . ')',
                    'price' => 0, 'qty' => 1, 'debit' => (float)$claim->replacement_sales_price, 'credit' => 0,
                    'priority' => 31
                ];
            }
        }
        // 12. Claim Credit Notes (CIR)
        $crnDateCol = $this->getDateColumn('claim_credit_notes');
        $crNotes = \App\Models\ClaimCreditNote::where('party_id', $id)
            ->where('party_type', $type == 'customer' ? 'customer' : 'vendor')
            ->where('status', 'Posted')
            ->whereBetween(DB::raw($crnDateCol), [$start, $end])
            ->get();
        foreach ($crNotes as $crn) {
            $transactions[] = [
                'created_at' => $crn->created_at,
                'id' => 'crn_' . $crn->id,
                'date' => $crn->entry_date ?: substr((string)$crn->created_at, 0, 10),
                'ref' => 'CIR',
                'inv' => preg_replace('/[^0-9]/', '', $crn->voucher_no ?? '0'),
                'desc' => 'CIR: ' . $crn->voucher_no,
                'price' => 0, 'qty' => 0, 
                'debit' => (float)$crn->net_total, 
                'credit' => 0,
                'priority' => 32
            ];
        }

        // Strictly chronologically (Time Wise)
        usort($transactions, function ($a, $b) {
            $timeA = isset($a['created_at']) && $a['created_at'] ? strtotime($a['created_at']) : 0;
            $timeB = isset($b['created_at']) && $b['created_at'] ? strtotime($b['created_at']) : 0;
            
            // If both have explicit timestamps and they differ, sort by timestamp
            if ($timeA !== 0 && $timeB !== 0 && $timeA !== $timeB) {
                return $timeA - $timeB;
            }

            // Fallback: Date
            $dateA = strtotime(substr($a['date'] ?? '', 0, 10));
            $dateB = strtotime(substr($b['date'] ?? '', 0, 10));
            if ($dateA != $dateB) {
                return $dateA - $dateB;
            }

            // Same exact time and date: Compare Priority as last resort
            $prioA = (int)($a['priority'] ?? 60);
            $prioB = (int)($b['priority'] ?? 60);
            if ($prioA !== $prioB) {
                return $prioA - $prioB;
            }

            $idA = $a['id'] ?? 0;
            $idB = $b['id'] ?? 0;
            if (is_numeric($idA) && is_numeric($idB)) {
                return $idA - $idB;
            }
            return strcmp((string)$idA, (string)$idB);
        });

        return $transactions;
    }

}
