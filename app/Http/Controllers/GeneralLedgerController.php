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

        if (!$id || !$type) {
            return back()->with('error', 'Please select an account/party first.');
        }

        $account_info = null;
        if ($type == 'customer') {
            $account_info = Customer::find($id);
        } elseif ($type == 'vendor') {
            $account_info = Vendor::find($id);
        } else {
            $account_info = Account::find($id);
        }

        if (!$account_info) {
            return back()->with('error', 'Account not found.');
        }

        // 1. Calculate Opening Balance
        $openingBalance = $this->calculateOpeningBalance($type, $id, $startDate);

        // 2. Fetch Transactions
        $transactions = $this->fetchTransactions($type, $id, $startDate, $endDate);

        $orientation = $request->get('orientation', 'portrait');

        if ($report_mode == 'details') {
            return view('admin_panel.reports.general_ledger.preview_details', compact('account_info', 'type', 'openingBalance', 'transactions', 'startDate', 'endDate', 'orientation'));
        } else {
            return view('admin_panel.reports.general_ledger.preview_summary', compact('account_info', 'type', 'openingBalance', 'transactions', 'startDate', 'endDate', 'orientation'));
        }
    }

    private function calculateOpeningBalance($type, $id, $date)
    {
        $balance = 0;
        $account = null;

        if ($type == 'customer') {
            $account = Customer::find($id);
            $balance = $account->opening_balance ?? 0;
            
            // Sales before date
            $debits = Sale::where('customer_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('total_balance');
            // Receipts before date
            $credits = ReceiptsVoucher::where('party_id', $id)->where('type', 'customer')->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            // Sale Returns before date
            $returns = SaleReturn::where('customer_id', $id)->where(DB::raw('COALESCE(entry_date, current_date)'), '<', $date)->sum('total_balance');
            
            $balance += $debits - $credits - $returns;
        } elseif ($type == 'vendor') {
            $account = Vendor::find($id);
            $balance = $account->opening_balance ?? 0;
            
            // Purchases before date (Credit)
            $credits = Purchase::where('vendor_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('total_balance');
            // Payments before date (Debit)
            $debits = PaymentVoucher::where('party_id', $id)->where('type', 'vendor')->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            // Purchase Returns before date (Debit)
            $returns = PurchaseReturn::where('vendor_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('net_amount');
            
            $balance += $credits - $debits - $returns; // Vendor balance is usually credit
        } else {
            $account = Account::find($id);
            $balance = $account->opening_balance ?? 0;
            
            // Vouchers before date
            $voucherDebits = DB::table('receipts_vouchers')->where('row_account_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            $voucherCredits = DB::table('payment_vouchers')->where('row_account_id', $id)->where(DB::raw('COALESCE(entry_date, DATE(created_at))'), '<', $date)->sum('amount');
            
            $balance += $voucherDebits - $voucherCredits;
        }

        return $balance;
    }

    private function fetchTransactions($type, $id, $start, $end)
    {
        $transactions = [];

        if ($type == 'customer') {
            // Sales (SJ)
            $sales = Sale::where('customer_id', $id)->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->with('items.product.brandRelation')->get();
            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    $brand = $item->product->brandRelation->name ?? '';
                    $transactions[] = [
                        'date' => $sale->entry_date ?: $sale->created_at,
                        'ref' => 'SJ',
                        'inv' => $sale->invoice_no,
                        'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product') . ' : ' . ($item->items ?? ''),
                        'price' => $item->sales_rate > 0 ? $item->sales_rate : (($item->sales_qty > 0) ? ($item->sales_price - ($item->discount_amount / $item->sales_qty)) : $item->sales_price),
                        'qty' => $item->sales_qty,
                        'debit' => $item->amount,
                        'credit' => 0
                    ];
                }
            }

            // Receipts (RV)
            $receipts = ReceiptsVoucher::where('party_id', $id)->where('type', 'customer')->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
            foreach ($receipts as $rv) {
                $transactions[] = [
                    'date' => $rv->entry_date ?: $rv->created_at,
                    'ref' => 'RV',
                    'inv' => $rv->rvid,
                    'desc' => $rv->remarks ?? 'Receipt Voucher',
                    'price' => 0,
                    'qty' => 0,
                    'debit' => 0,
                    'credit' => $rv->amount
                ];
            }

            // Sale Returns (SRJ)
            $returns = SaleReturn::where('customer_id', $id)->whereBetween(DB::raw('COALESCE(entry_date, current_date)'), [$start, $end])->get();
            foreach ($returns as $sr) {
                $transactions[] = [
                    'date' => $sr->entry_date ?: $sr->current_date,
                    'ref' => 'SRJ',
                    'inv' => $sr->invoice_no,
                    'desc' => 'Sale Return',
                    'price' => 0,
                    'qty' => 0,
                    'debit' => 0,
                    'credit' => $sr->total_balance
                ];
            }
        } elseif ($type == 'vendor') {
             // Purchases (PJ)
             $purchases = Purchase::where('vendor_id', $id)->with('items.product.brandRelation')
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
             foreach ($purchases as $p) {
                 foreach ($p->items as $item) {
                     $brand = $item->product->brandRelation->name ?? '';
                     $transactions[] = [
                         'date' => $p->entry_date ?: $p->created_at,
                         'ref' => 'PJ',
                         'inv' => $p->invoice_no,
                         'desc' => ($brand ? $brand . ' - ' : '') . ($item->product->name ?? 'Product'),
                         'price' => $item->purchase_rate ?: (($item->qty > 0) ? ($item->price - (($item->item_discount > 100) ? ($item->item_discount / $item->qty) : ($item->price * $item->item_discount / 100))) : $item->price),
                         'qty' => $item->qty,
                         'debit' => 0,
                         'credit' => $item->line_total
                     ];
                 }
             }

             // Payments (PV)
             $payments = PaymentVoucher::where('party_id', $id)->where('type', 'vendor')
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
             foreach ($payments as $pv) {
                 $transactions[] = [
                     'date' => $pv->entry_date ?: $pv->created_at,
                     'ref' => 'PV',
                     'inv' => $pv->pvid,
                     'desc' => $pv->remarks ?? 'Payment Voucher',
                     'price' => 0,
                     'qty' => 0,
                     'debit' => $pv->amount,
                     'credit' => 0
                 ];
             }
        } else {
            // General Account
            $rvs = ReceiptsVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
            foreach($rvs as $rv) {
                $transactions[] = [
                    'date' => $rv->entry_date ?: $rv->created_at,
                    'ref' => 'RV',
                    'inv' => $rv->rvid,
                    'desc' => $rv->remarks ?? 'Receipt',
                    'price' => 0,
                    'qty' => 0,
                    'debit' => $rv->amount,
                    'credit' => 0
                ];
            }
            $pvs = PaymentVoucher::where('row_account_id', $id)
                ->whereBetween(DB::raw('COALESCE(entry_date, DATE(created_at))'), [$start, $end])->get();
            foreach($pvs as $pv) {
                $transactions[] = [
                    'date' => $pv->entry_date ?: $pv->created_at,
                    'ref' => 'PV',
                    'inv' => $pv->pvid,
                    'desc' => $pv->remarks ?? 'Payment',
                    'price' => 0,
                    'qty' => 0,
                    'debit' => 0,
                    'credit' => $pv->amount
                ];
            }
        }

        // Sort by Date
        usort($transactions, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return $transactions;
    }
}
