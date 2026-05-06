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
        $sales = Sale::where('customer_id', $id)->where('partyType', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
            ->get();
        foreach ($sales as $sale) {
            $transactions[] = [
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
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
            ->get();
        foreach ($pReturns as $pr) {
            $transactions[] = [
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
        $payments = PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($payments as $pv) {
            $transactions[] = [
                'date' => $pv->entry_date ?: $pv->created_at,
                'ref' => 'PV',
                'inv' => $pv->pvid,
                'desc' => $pv->remarks ?? 'Payment Voucher',
                'qty' => 0, 'debit' => (float)$pv->amount, 'credit' => 0
            ];
        }

        // 3.1 Expenses (EV)
        $expenses = DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
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
        $jvs = JournalVoucher::where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $transactions[] = [
                'date' => $jv->entry_date ?: $jv->created_at,
                'ref' => 'JV',
                'inv' => $jv->jvid,
                'desc' => $jv->remarks ?? 'Journal Voucher',
                'qty' => 0, 'debit' => (float)$jv->debit, 'credit' => (float)$jv->credit
            ];
        }

        // 5. Purchases (PJ) - Aggregate
        $purchases = Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
            ->get();
        foreach ($purchases as $p) {
            $transactions[] = [
                'date' => $p->entry_date ?: $p->created_at,
                'ref' => 'PJ',
                'inv' => $p->invoice_no,
                'desc' => 'Purchase',
                'qty' => (float)DB::table('purchase_items')->where('purchase_id', $p->id)->sum('qty'),
                'debit' => 0,
                'credit' => (float)$p->net_amount
            ];
        }

        // 6. Sale Returns (SRJ) - Aggregate
        $sReturns = SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, current_date)'), [$start, $end])
            ->get();
        foreach ($sReturns as $sr) {
            $transactions[] = [
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
        $receipts = ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $transactions[] = [
                'date' => $rv->entry_date ?: $rv->created_at,
                'ref' => 'RV',
                'inv' => $rv->rvid,
                'desc' => $rv->remarks ?? 'Receipt Voucher',
                'qty' => 0, 'debit' => 0, 'credit' => (float)$rv->amount
            ];
        }

        // 7.1 Incomes (IV)
        $incomes = DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
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
            $balance = (float)($account->opening_balance ?? 0);
            
            // Receipts (Debit)
            $voucherDebits = (float)ReceiptsVoucher::where('row_account_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            // Payments (Credit)
            $voucherCredits = (float)PaymentVoucher::where('row_account_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            
            $balance += ($voucherDebits - $voucherCredits);
            return $balance;
        }

        // For Party (Customer/Vendor)
        $party = ($type == 'customer') ? Customer::find($id) : Vendor::find($id);
        $balance = (float)($party->opening_balance ?? 0);
        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (Debit)
        $sales = (float)Sale::where('customer_id', $id)->where('partyType', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('total_balance');
        
        // 2. Purchase Returns (Debit)
        $pReturns = (float)PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('net_amount');

        // 3. Payments (Debit)
        $payments = (float)PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');

        // 3.1 Expenses (Debit)
        $expenses = (float)DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');

        // 4. JV Debits
        $jvDebits = (float)JournalVoucher::where('party_id', $id)->where('party_type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('debit');

        // 5. Purchases (Credit)
        $purchases = (float)Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('net_amount');

        // 6. Sale Returns (Credit)
        $sReturns = (float)SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->where(DB::raw('COALESCE(entry_date, current_date)'), '<', $date)->sum('total_balance');

        // 7. Receipts (Credit)
        $receipts = (float)ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
        
        // 7.1 Income (Credit)
        $incomes = (float)DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');

        // 8. JV Credits
        $jvCredits = (float)JournalVoucher::where('party_id', $id)->where('party_type', $type)
            ->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('credit');

        $balance += ($sales + $pReturns + $payments + $expenses + $jvDebits) - ($purchases + $sReturns + $receipts + $incomes + $jvCredits);
        
        return $balance;
    }

    private function fetchTransactions($type, $id, $start, $end)
    {
        $transactions = [];

        if ($type == 'account') {
            // Receipts
            $rvs = ReceiptsVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
            foreach($rvs as $rv) {
                $transactions[] = [
                    'date' => $rv->entry_date ?: $rv->created_at,
                    'ref' => 'RV',
                    'inv' => $rv->rvid,
                    'desc' => $rv->remarks ?? 'Receipt',
                    'price' => 0, 'qty' => 0, 'debit' => (float)$rv->amount, 'credit' => 0
                ];
            }
            // Payments
            $pvs = PaymentVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
            foreach($pvs as $pv) {
                $transactions[] = [
                    'date' => $pv->entry_date ?: $pv->created_at,
                    'ref' => 'PV',
                    'inv' => $pv->pvid,
                    'desc' => $pv->remarks ?? 'Payment',
                    'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$pv->amount
                ];
            }
            // Sort by Date
            usort($transactions, function ($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });
            return $transactions;
        }

        $class = ($type == 'customer') ? 'App\Models\Customer' : 'App\Models\Vendor';

        // 1. Sales (SJ) - Debit
        $sales = Sale::where('customer_id', $id)->where('partyType', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
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
        $pReturns = PurchaseReturn::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($pReturns as $pr) {
            foreach ($pr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $transactions[] = [
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
        $payments = PaymentVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($payments as $pv) {
            $transactions[] = [
                'date' => $pv->entry_date ?: $pv->created_at,
                'ref' => 'PV',
                'inv' => $pv->pvid,
                'desc' => $pv->remarks ?? 'Payment Voucher',
                'price' => 0, 'qty' => 0, 'debit' => (float)$pv->amount, 'credit' => 0
            ];
        }

        // 3.1 Expenses (EV) - Debit
        $expenses = DB::table('expense_vouchers')->where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($expenses as $ev) {
            $transactions[] = [
                'date' => $ev->entry_date ?: $ev->created_at,
                'ref' => 'EV',
                'inv' => $ev->evid,
                'desc' => $ev->remarks ?? 'Expense Voucher',
                'price' => 0, 'qty' => 0, 'debit' => (float)$ev->amount, 'credit' => 0
            ];
        }

        // 4. JV (JV) - Debit/Credit
        $jvs = JournalVoucher::where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($jvs as $jv) {
            $transactions[] = [
                'date' => $jv->entry_date ?: $jv->created_at,
                'ref' => 'JV',
                'inv' => $jv->jvid,
                'desc' => $jv->remarks ?? 'Journal Voucher',
                'price' => 0, 'qty' => 0, 'debit' => (float)$jv->debit, 'credit' => (float)$jv->credit
            ];
        }

        // 5. Purchases (PJ) - Credit
        $purchases = Purchase::where(function($q) use ($id, $type, $class) {
                $q->where('vendor_id', $id)->orWhere(function($q2) use ($id, $class) {
                    $q2->where('purchasable_id', $id)->where('purchasable_type', $class);
                });
            })->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])
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
                    'date' => $p->entry_date ?: $p->created_at,
                    'ref' => 'PJ',
                    'inv' => $p->invoice_no,
                    'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                    'price' => $finalPrice,
                    'qty' => $qty,
                    'debit' => 0,
                    'credit' => (float)$item->line_total
                ];
            }
        }

        // 6. Sale Returns (SRJ) - Credit
        $sReturns = SaleReturn::where('customer_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, current_date)'), [$start, $end])
            ->with('items.product.brandRelation')->get();
        foreach ($sReturns as $sr) {
            foreach ($sr->items as $item) {
                $brand = $item->product->brandRelation->name ?? '';
                $transactions[] = [
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
        $receipts = ReceiptsVoucher::where('party_id', $id)->where('type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($receipts as $rv) {
            $transactions[] = [
                'date' => $rv->entry_date ?: $rv->created_at,
                'ref' => 'RV',
                'inv' => $rv->rvid,
                'desc' => $rv->remarks ?? 'Receipt Voucher',
                'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$rv->amount
            ];
        }

        // 7.1 Incomes (IV) - Credit
        $incomes = DB::table('income_vouchers')->where('party_id', $id)->where('party_type', $type)
            ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
        foreach ($incomes as $iv) {
            $transactions[] = [
                'date' => $iv->entry_date ?: $iv->created_at,
                'ref' => 'IV',
                'inv' => $iv->ivid,
                'desc' => $iv->remarks ?? 'Income Voucher',
                'price' => 0, 'qty' => 0, 'debit' => 0, 'credit' => (float)$iv->amount
            ];
        }

        // Sort by Date
        usort($transactions, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return $transactions;
    }

}
