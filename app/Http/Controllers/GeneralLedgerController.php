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

    private function fetchSummaryTransactions($type, $id, $start, $end)
    {
        $transactions = [];

        if ($type == 'account') {
            return $this->fetchTransactions($type, $id, $start, $end);
        }

        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (SJ) - Aggregate
        $salesDateCol = $this->getDateColumn('sales');
        $sales = Sale::where('customer_id', $id)->where('partyType', $type)
            ->whereBetween(DB::raw($salesDateCol), [$start, $end])
            ->get();
        foreach ($sales as $sale) {
            $transactions[] = [
                'id' => $sale->id,
                'date' => $sale->entry_date ?: $sale->created_at,
                'ref' => 'SJ',
                'inv' => $sale->invoice_no,
                'desc' => 'Sales',
                'qty' => (float)$sale->quantity,
                'debit' => (float)$sale->total_balance,
                'credit' => 0
            ];
        }

        // 2. Purchase Returns (PRJ) - Aggregate
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw($prDateCol), [$start, $end])
            ->get();
        foreach ($pReturns as $pr) {
            $transactions[] = [
                'id' => $pr->id,
                'date' => $pr->entry_date ?: $pr->created_at,
                'ref' => 'PRJ',
                'inv' => $pr->invoice_no,
                'desc' => 'Purchase Return',
                'qty' => (float)DB::table('purchase_return_items')->where('purchase_return_id', $pr->id)->sum('qty'),
                'debit' => (float)$pr->net_amount,
                'credit' => 0
            ];
        }

        // 3. Payments (PV)
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
        foreach ($payments as $pv) {
            $transactions[] = [
                'id' => $pv->id,
                'date' => $pv->entry_date ?: $pv->created_at,
                'ref' => 'PV',
                'inv' => $pv->pvid,
                'desc' => $pv->remarks ?? 'Payment Voucher',
                'qty' => 0, 'debit' => (float)$pv->amount, 'credit' => 0
            ];
        }

        // 3.1 Expenses (EV)
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($evDateCol), [$start, $end])->get();
        foreach ($expenses as $ev) {
            $transactions[] = [
                'date' => $ev->entry_date ?: $ev->created_at,
                'ref' => 'EV',
                'inv' => $ev->evid,
                'desc' => $ev->remarks ?? 'Expense Voucher',
                'qty' => 0, 'debit' => (float)$ev->amount, 'credit' => 0
            ];
        }

        // 4. JV
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereJsonContains('party_type', $type)
            ->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if ($pid == $id) {
                    $ref = 'JV';
                    $inv = $jv->jvid;
                    
                    // Cleanup for Purchase-related JVs (e.g. PJ-ALLOC-50 -> PJ 50)
                    if (str_starts_with($inv, 'PJ-')) {
                        $ref = 'PJ';
                        $inv = preg_replace('/^PJ-[A-Z]+-/', '', $inv);
                    }

                    $transactions[] = [
                        'id' => $jv->id,
                        'date' => $jv->entry_date ?: $jv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $jv->remarks ?? 'Journal Voucher',
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
            })->whereBetween(DB::raw($pjDateCol), [$start, $end])
            ->get();
        foreach ($purchases as $p) {
            $transactions[] = [
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
        $sReturns = SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw($srDateCol), [$start, $end])
            ->get();
        foreach ($sReturns as $sr) {
            $transactions[] = [
                'id' => $sr->id,
                'date' => $sr->entry_date ?: $sr->current_date,
                'ref' => 'SRJ',
                'inv' => $sr->invoice_no,
                'desc' => 'Sale Return',
                'qty' => (float)$sr->quantity,
                'debit' => 0,
                'credit' => (float)$sr->total_balance
            ];
        }

        // 7. Receipts (RV)
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $receipts = ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $transactions[] = [
                'id' => $rv->id,
                'date' => $rv->entry_date ?: $rv->created_at,
                'ref' => 'RV',
                'inv' => $rv->rvid,
                'desc' => $rv->remarks ?? 'Receipt Voucher',
                'qty' => 0, 'debit' => 0, 'credit' => (float)$rv->amount
            ];
        }

        // 7.1 Incomes (IV)
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $incomes = DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();
        foreach ($incomes as $iv) {
            $transactions[] = [
                'date' => $iv->entry_date ?: $iv->created_at,
                'ref' => 'IV',
                'inv' => $iv->ivid,
                'desc' => $iv->remarks ?? 'Income Voucher',
                'qty' => 0, 'debit' => 0, 'credit' => (float)$iv->amount
            ];
        }

        usort($transactions, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return $transactions;
    }

    private function calculateOpeningBalance($type, $id, $date)
    {
        $balance = 0;
        
        if ($type == 'account') {
            $account = Account::find($id);
            if (!$account) return 0;
            $balance = (float)($account->opening_balance ?? 0);
            
            // For accounts, opening_balance in table is the CURRENT balance.
            // To get balance at START date, we subtract all transactions from START date to NOW.
            
            // RVs (Debit increases balance)
            $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
            $rvSum = (float)ReceiptsVoucher::where('row_account_id', $id)
                ->where(DB::raw($rvDateCol), '>=', $date)->sum('total_amount');
            
            // PVs (Credit decreases balance)
            $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
            $pvSum = (float)PaymentVoucher::where('row_account_id', $id)
                ->where(DB::raw($pvDateCol), '>=', $date)->sum('total_amount');
                
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
            
            return $balance - ($rvSum - $pvSum + $jvImpact);
        }

        // For Party (Customer/Vendor)
        $party = ($type == 'customer') ? Customer::find($id) : Vendor::find($id);
        if (!$party) return 0;
        
        $balance = (float)($party->opening_balance ?? 0);
        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (Debit) - Use sub_total2 (Gross net of line discounts)
        $salesDateCol = $this->getDateColumn('sales');
        $sales = (float)Sale::where('customer_id', $id)->where('partyType', $type)
            ->where(DB::raw($salesDateCol), '<', $date)->sum('sub_total2');
        
        // 2. Purchase Returns (Debit)
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = (float)PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where(DB::raw($prDateCol), '<', $date)->sum('net_amount');

        // 3. Payments (Debit)
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = (float)PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->where(DB::raw($pvDateCol), '<', $date)->sum('amount');

        // 3.1 Expenses (Debit)
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = (float)DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->where(DB::raw($evDateCol), '<', $date)->sum('amount');

        // 3.2 Generic Vouchers (Debit)
        $vDebits = (float)DB::table('vouchers')->where('person', $id)->where('type', 'Debit')
            ->where(DB::raw("COALESCE(date, DATE(created_at))"), '<', $date)->sum('amount');

        // 4. JV Debits
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereJsonContains('party_type', $type)
            ->where(DB::raw($jvDateCol), '<', $date)->get();
        $jvDebits = 0;
        foreach($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if($pid == $id) $jvDebits += (float)($debits[$idx] ?? 0);
            }
        }

        // 5. Purchases (Credit)
        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = (float)Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where(DB::raw($pjDateCol), '<', $date)->sum('net_amount');

        // 6. Sale Returns (Credit)
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $sReturns = (float)SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->where(DB::raw($srDateCol), '<', $date)->sum('total_balance');

        // 7. Receipts (Credit)
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $receipts = (float)ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->where(DB::raw($rvDateCol), '<', $date)->sum('amount');
        
        // 7.1 Income (Credit)
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $incomes = (float)DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->where(DB::raw($ivDateCol), '<', $date)->sum('amount');

        // 7.2 Generic Vouchers (Credit)
        $vCredits = (float)DB::table('vouchers')->where('person', $id)->where('type', 'Credit')
            ->where(DB::raw("COALESCE(date, DATE(created_at))"), '<', $date)->sum('amount');

        // 8. JV Credits
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereJsonContains('party_type', $type)
            ->where(DB::raw($jvDateCol), '<', $date)->get();
        $jvCredits = 0;
        foreach($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if($pid == $id) $jvCredits += (float)($credits[$idx] ?? 0);
            }
        }

        $balance += ($sales + $pReturns + $payments + $expenses + $vDebits + $jvDebits) - ($purchases + $sReturns + $receipts + $incomes + $vCredits + $jvCredits);
        
        return $balance;
    }

    private function fetchTransactions($type, $id, $start, $end)
    {
        $transactions = [];

        if ($type == 'account') {
            // Receipts
            $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
            $rvs = ReceiptsVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
            foreach($rvs as $rv) {
                $transactions[] = [
                    'id' => $rv->id,
                    'date' => $rv->entry_date ?: $rv->created_at,
                    'ref' => 'RV',
                    'inv' => $rv->rvid,
                    'desc' => $rv->remarks ?? 'Receipt',
                    'price' => 0, 'qty' => 0, 'debit' => (float)$rv->amount, 'credit' => 0
                ];
            }
            // Payments
            $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
            $pvs = PaymentVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
            foreach($pvs as $pv) {
                $transactions[] = [
                    'id' => $pv->id,
                    'date' => $pv->entry_date ?: $pv->created_at,
                    'ref' => 'PV',
                    'inv' => $pv->pvid,
                    'desc' => $pv->remarks ?? 'Payment',
                    'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$pv->amount
                ];
            }
            // JVs
            $jvDateCol = $this->getDateColumn('journal_vouchers');
            $jvs = JournalVoucher::where(function($q) use ($id) {
                    $q->whereJsonContains('party_id', (string)$id)
                      ->orWhereJsonContains('party_id', (int)$id);
                })
                ->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
            foreach($jvs as $jv) {
                $pIds = json_decode($jv->party_id, true) ?? [];
                $debits = json_decode($jv->debit, true) ?? [];
                $credits = json_decode($jv->credit, true) ?? [];
                foreach($pIds as $idx => $pid) {
                    if ($pid == $id) {
                        $transactions[] = [
                            'id' => $jv->id,
                            'date' => $jv->entry_date ?: $jv->created_at,
                            'ref' => 'JV',
                            'inv' => $jv->jvid,
                            'desc' => $jv->remarks ?? 'Journal Voucher',
                            'price' => 0, 'qty' => 0, 
                            'debit' => (float)($debits[$idx] ?? 0), 
                            'credit' => (float)($credits[$idx] ?? 0)
                        ];
                    }
                }
            }

            // Sort by Date, then ID for chronological order
            usort($transactions, function ($a, $b) { 
                $dateA = strtotime($a['date']);
                $dateB = strtotime($b['date']);
                if ($dateA == $dateB) {
                    return ($a['id'] ?? 0) - ($b['id'] ?? 0);
                }
                return $dateA - $dateB;
            });
            return $transactions;
        }

        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (SJ) - Debit
        $salesDateCol = $this->getDateColumn('sales');
        $sales = Sale::where('customer_id', $id)->where('partyType', $type)
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
                    'id' => $item->id,
                    'date' => $sale->entry_date ?: $sale->created_at,
                    'ref' => 'SJ',
                    'inv' => $sale->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product') . ' : ' . ($item->items ?? ''),
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => (float)$item->amount,
                    'credit' => 0
                ];
            }
        }

        // 2. Purchase Returns (PRJ) - Debit
        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw($prDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($pReturns as $pr) {
            foreach ($pr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $transactions[] = [
                    'id' => $item->id,
                    'date' => $pr->entry_date ?: $pr->created_at,
                    'ref' => 'PRJ',
                    'inv' => $pr->invoice_no,
                    'desc' => 'Purchase Return: ' . ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => (float)($item->purchase_rate ?: $item->price),
                    'qty' => (float)$item->qty,
                    'debit' => (float)$item->line_total,
                    'credit' => 0
                ];
            }
        }

        // 3. Payments (PV) - Debit
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $payments = PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($pvDateCol), [$start, $end])->get();
        foreach ($payments as $pv) {
            $transactions[] = [
                'id' => $pv->id,
                'date' => $pv->entry_date ?: $pv->created_at,
                'ref' => 'PV',
                'inv' => $pv->pvid,
                'desc' => $pv->remarks ?? 'Payment Voucher',
                'price' => 0, 'qty' => 0, 'debit' => (float)$pv->amount, 'credit' => 0
            ];
        }

        // 3.1 Expenses (EV) - Debit
        $evDateCol = $this->getDateColumn('expense_vouchers');
        $expenses = DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($evDateCol), [$start, $end])->get();
        foreach ($expenses as $ev) {
            $transactions[] = [
                'id' => $ev->id,
                'date' => $ev->entry_date ?: $ev->created_at,
                'ref' => 'EV',
                'inv' => $ev->evid,
                'desc' => $ev->remarks ?? 'Expense Voucher',
                'price' => 0, 'qty' => 0, 'debit' => (float)$ev->amount, 'credit' => 0
            ];
        }

        // 3.2 Generic Vouchers (Debit/Credit)
        $vouchers = DB::table('vouchers')->where('person', $id)
            ->whereBetween(DB::raw("COALESCE(date, DATE(created_at))"), [$start, $end])->get();
        foreach ($vouchers as $v) {
            $transactions[] = [
                'id' => $v->id,
                'date' => $v->date ?: $v->created_at,
                'ref' => 'VO',
                'inv' => $v->voucher_type,
                'desc' => $v->narration ?? $v->voucher_type,
                'price' => 0, 'qty' => 0,
                'debit' => ($v->type == 'Debit') ? (float)$v->amount : 0,
                'credit' => ($v->type == 'Credit') ? (float)$v->amount : 0
            ];
        }

        // 4. JV (JV) - Debit/Credit
        $jvDateCol = $this->getDateColumn('journal_vouchers');
        $jvs = JournalVoucher::where(function($q) use ($id) {
                $q->whereJsonContains('party_id', (string)$id)
                  ->orWhereJsonContains('party_id', (int)$id);
            })
            ->whereJsonContains('party_type', $type)
            ->whereBetween(DB::raw($jvDateCol), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $pIds = json_decode($jv->party_id, true) ?? [];
            $debits = json_decode($jv->debit, true) ?? [];
            $credits = json_decode($jv->credit, true) ?? [];
            foreach($pIds as $idx => $pid) {
                if ($pid == $id) {
                    $ref = 'JV';
                    $inv = $jv->jvid;
                    
                    // Cleanup for Purchase-related JVs (e.g. PJ-ALLOC-50 -> PJ 50)
                    if (str_starts_with($inv, 'PJ-')) {
                        $ref = 'PJ';
                        $inv = preg_replace('/^PJ-[A-Z]+-/', '', $inv);
                    }

                    $transactions[] = [
                        'id' => $jv->id,
                        'date' => $jv->entry_date ?: $jv->created_at,
                        'ref' => $ref,
                        'inv' => $inv,
                        'desc' => $jv->remarks ?? 'Journal Voucher',
                        'price' => 0, 'qty' => 0, 
                        'debit' => (float)($debits[$idx] ?? 0), 
                        'credit' => (float)($credits[$idx] ?? 0),
                        'priority' => 2 // Posting impacts come second
                    ];
                }
            }
        }

        // 5. Purchases (PJ) - Credit
        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw($pjDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($purchases as $p) {
            foreach ($p->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $qty = (float)$item->qty;
                $price = (float)$item->price;
                $disc = (float)$item->item_discount;
                $rate = (float)$item->purchase_rate;
                
                $finalPrice = $rate ?: (($qty > 0) ? ($price - (($disc > 100) ? ($disc / $qty) : ($price * $disc / 100))) : $price);

                $transactions[] = [
                    'id' => $item->id,
                    'date' => $p->entry_date ?: $p->created_at,
                    'ref' => 'PJ',
                    'inv' => $p->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => 0,
                    'credit' => (float)$item->line_total,
                    'priority' => 1 // Items come first
                ];
            }
        }

        // 6. Sale Returns (SRJ) - Credit
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $sReturns = SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw($srDateCol), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($sReturns as $sr) {
            foreach ($sr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $transactions[] = [
                    'id' => $item->id,
                    'date' => $sr->entry_date ?: $sr->current_date,
                    'ref' => 'SRJ',
                    'inv' => $sr->invoice_no,
                    'desc' => 'Sale Return: ' . ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => (float)($item->sales_rate ?: $item->sales_price),
                    'qty' => (float)$item->sales_qty,
                    'debit' => 0,
                    'credit' => (float)$item->amount
                ];
            }
        }

        // 7. Receipts (RV) - Credit
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $receipts = ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw($rvDateCol), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $transactions[] = [
                'id' => $rv->id,
                'date' => $rv->entry_date ?: $rv->created_at,
                'ref' => 'RV',
                'inv' => $rv->rvid,
                'desc' => $rv->remarks ?? 'Receipt Voucher',
                'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$rv->amount
            ];
        }

        // 7.1 Incomes (IV) - Credit
        $ivDateCol = $this->getDateColumn('income_vouchers');
        $incomes = DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();
        foreach ($incomes as $iv) {
            $transactions[] = [
                'id' => $iv->id,
                'date' => $iv->entry_date ?: $iv->created_at,
                'ref' => 'IV',
                'inv' => $iv->ivid,
                'desc' => $iv->remarks ?? 'Income Voucher',
                'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$iv->amount
            ];
        }

        // Sort by Date, then logical grouping (Invoice No), then Priority (PJ before JV)
        usort($transactions, function ($a, $b) {
            $dateA = strtotime($a['date']);
            $dateB = strtotime($b['date']);
            if ($dateA != $dateB) {
                return $dateA - $dateB;
            }

            // Same date: Extract numeric invoice number for grouping
            $invA = preg_replace('/[^0-9]/', '', $a['inv'] ?? '');
            $invB = preg_replace('/[^0-9]/', '', $b['inv'] ?? '');

            if ($invA !== '' && $invB !== '') {
                $diff = (int)$invA - (int)$invB;
                if ($diff !== 0) {
                    return $diff;
                }
            }

            // Same invoice group: Sort by Priority (PJ items before JV impacts)
            $prioA = $a['priority'] ?? 5;
            $prioB = $b['priority'] ?? 5;
            if ($prioA !== $prioB) {
                return $prioA - $prioB;
            }

            // Fallback to record ID
            return ($a['id'] ?? 0) - ($b['id'] ?? 0);
        });

        return $transactions;
    }

}
