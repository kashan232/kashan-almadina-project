<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ReceiptsVoucher;
use App\Models\PaymentVoucher;
use App\Models\ExpenseVoucher;
use App\Models\IncomeVoucher;
use App\Models\JournalVoucher;
use App\Models\CustomerClaim;
use App\Models\ClaimAcceptance;
use App\Models\ClaimItemReceipt;
use App\Models\ClaimCreditNote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyReportBuilder
{
    /**
     * Build daily activity report grouped by Form / Module Types (Sales, Sale Return, Purchase, Purchase Return, Vouchers, etc.)
     */
    public function build(Request $request): array
    {
        $fromDate = $request->input('from_date', date('Y-m-d'));
        $toDate = $request->input('to_date', date('Y-m-d'));

        $sections = [];
        $grandTotal = [
            'debit_qty' => 0.0,
            'debit_amt' => 0.0,
            'credit_qty' => 0.0,
            'credit_amt' => 0.0,
        ];

        // Defined Modules order:
        // 1. Sales
        // 2. Sale Return
        // 3. Purchase
        // 4. Purchase Return
        // 5. Receipts Voucher
        // 6. Payment Voucher
        // 7. Expense Voucher
        // 8. Income Voucher
        // 9. Journal Voucher
        // 10. Customer Claims & Acceptance

        $modules = [
            [
                'key' => 'sales',
                'title' => 'Sales Invoices',
                'fetch' => function() use ($fromDate, $toDate) {
                    $sales = Sale::withoutGlobalScopes()
                        ->with(['customer', 'vendor', 'items.product'])
                        ->whereDate('created_at', '>=', $fromDate)
                        ->whereDate('created_at', '<=', $toDate)
                        ->latest()
                        ->get();

                    $txns = [];
                    foreach ($sales as $s) {
                        $partyName = strtoupper($s->customer?->customer_name ?? $s->vendor?->name ?? 'WALK IN');
                        $dateStr = Carbon::parse($s->created_at)->format('d-m-Y');
                        
                        foreach ($s->items as $it) {
                            $qty = (float)$it->sales_qty;
                            $price = (float)$it->sales_price;
                            $amt = (float)$it->amount;

                            $txns[] = [
                                'date' => $dateStr,
                                'ref' => 'SJ',
                                'inv_no' => $s->invoice_no,
                                'desc' => ($it->product->name ?? 'Item') . ' — ' . $partyName,
                                'price' => $price,
                                'debit_qty' => null,
                                'debit_amt' => null,
                                'credit_qty' => $qty,
                                'credit_amt' => $amt,
                            ];
                        }
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'sale_returns',
                'title' => 'Sale Returns',
                'fetch' => function() use ($fromDate, $toDate) {
                    $returns = SaleReturn::withoutGlobalScopes()
                        ->with(['customer', 'items.product'])
                        ->whereDate('date', '>=', $fromDate)
                        ->whereDate('date', '<=', $toDate)
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($returns as $sr) {
                        $partyName = strtoupper($sr->party_name ?? 'CUSTOMER');
                        $dateStr = !empty($sr->date) ? Carbon::parse($sr->date)->format('d-m-Y') : '';

                        foreach ($sr->items as $it) {
                            $qty = (float)$it->quantity;
                            $price = (float)$it->price;
                            $amt = $qty * $price;

                            $txns[] = [
                                'date' => $dateStr,
                                'ref' => 'SRJ',
                                'inv_no' => $sr->invoice_no,
                                'desc' => ($it->product->name ?? 'Item') . ' — ' . $partyName,
                                'price' => $price,
                                'debit_qty' => $qty,
                                'debit_amt' => $amt,
                                'credit_qty' => null,
                                'credit_amt' => null,
                            ];
                        }
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'purchases',
                'title' => 'Purchase Invoices',
                'fetch' => function() use ($fromDate, $toDate) {
                    $purchases = Purchase::withoutGlobalScopes()
                        ->with(['vendor', 'items.product'])
                        ->whereDate('entry_date', '>=', $fromDate)
                        ->whereDate('entry_date', '<=', $toDate)
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($purchases as $p) {
                        $partyName = strtoupper($p->vendor?->name ?? 'VENDOR');
                        $dateStr = !empty($p->entry_date) ? Carbon::parse($p->entry_date)->format('d-m-Y') : '';

                        foreach ($p->items as $it) {
                            $qty = (float)$it->qty;
                            $price = (float)$it->price;
                            $amt = (float)$it->subtotal;

                            $txns[] = [
                                'date' => $dateStr,
                                'ref' => 'PJ',
                                'inv_no' => $p->invoice_no,
                                'desc' => ($it->product->name ?? 'Item') . ' — ' . $partyName,
                                'price' => $price,
                                'debit_qty' => $qty,
                                'debit_amt' => $amt,
                                'credit_qty' => null,
                                'credit_amt' => null,
                            ];
                        }
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'purchase_returns',
                'title' => 'Purchase Returns',
                'fetch' => function() use ($fromDate, $toDate) {
                    $returns = PurchaseReturn::withoutGlobalScopes()
                        ->with(['items.product'])
                        ->whereDate('entry_date', '>=', $fromDate)
                        ->whereDate('entry_date', '<=', $toDate)
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($returns as $pr) {
                        $partyName = 'VENDOR';
                        $dateStr = !empty($pr->entry_date) ? Carbon::parse($pr->entry_date)->format('d-m-Y') : '';

                        foreach ($pr->items as $it) {
                            $qty = (float)$it->qty;
                            $price = (float)$it->price;
                            $amt = (float)$it->subtotal;

                            $txns[] = [
                                'date' => $dateStr,
                                'ref' => 'PRJ',
                                'inv_no' => $pr->invoice_no,
                                'desc' => ($it->product->name ?? 'Item') . ' — ' . $partyName,
                                'price' => $price,
                                'debit_qty' => null,
                                'debit_amt' => null,
                                'credit_qty' => $qty,
                                'credit_amt' => $amt,
                            ];
                        }
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'receipts',
                'title' => 'Receipt Vouchers',
                'fetch' => function() use ($fromDate, $toDate) {
                    $vouchers = ReceiptsVoucher::withoutGlobalScopes()
                        ->with(['Account'])
                        ->where(function($q) use ($fromDate, $toDate) {
                            $q->whereBetween('receipt_date', [$fromDate, $toDate])
                              ->orWhereBetween('entry_date', [$fromDate, $toDate]);
                        })
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($vouchers as $rv) {
                        $vDate = $rv->receipt_date ?? $rv->entry_date ?? $rv->created_at;
                        $dateStr = !empty($vDate) ? Carbon::parse($vDate)->format('d-m-Y') : '';
                        $amt = (float)($rv->total_amount ?? $rv->receipt_amount ?? 0);
                        $accName = $rv->Account->title ?? '';

                        $txns[] = [
                            'date' => $dateStr,
                            'ref' => 'RV',
                            'inv_no' => $rv->rvid ?? $rv->id,
                            'desc' => trim(($rv->remarks ? $rv->remarks . ' ' : '') . $accName),
                            'price' => null,
                            'debit_qty' => null,
                            'debit_amt' => null,
                            'credit_qty' => null,
                            'credit_amt' => $amt,
                        ];
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'payments',
                'title' => 'Payment Vouchers',
                'fetch' => function() use ($fromDate, $toDate) {
                    $vouchers = PaymentVoucher::withoutGlobalScopes()
                        ->with(['Account'])
                        ->where(function($q) use ($fromDate, $toDate) {
                            $q->whereBetween('receipt_date', [$fromDate, $toDate])
                              ->orWhereBetween('entry_date', [$fromDate, $toDate]);
                        })
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($vouchers as $pv) {
                        $vDate = $pv->receipt_date ?? $pv->entry_date ?? $pv->created_at;
                        $dateStr = !empty($vDate) ? Carbon::parse($vDate)->format('d-m-Y') : '';
                        $amt = (float)($pv->total_amount ?? $pv->payment_amount ?? 0);
                        $accName = $pv->Account->title ?? '';

                        $txns[] = [
                            'date' => $dateStr,
                            'ref' => 'PV',
                            'inv_no' => $pv->pvid ?? $pv->id,
                            'desc' => trim(($pv->remarks ? $pv->remarks . ' ' : '') . $accName),
                            'price' => null,
                            'debit_qty' => null,
                            'debit_amt' => $amt,
                            'credit_qty' => null,
                            'credit_amt' => null,
                        ];
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'expenses',
                'title' => 'Expense Vouchers',
                'fetch' => function() use ($fromDate, $toDate) {
                    $vouchers = ExpenseVoucher::withoutGlobalScopes()
                        ->where(function($q) use ($fromDate, $toDate) {
                            $q->whereBetween('entry_date', [$fromDate, $toDate])
                              ->orWhereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
                        })
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($vouchers as $ev) {
                        $vDate = $ev->entry_date ?? $ev->created_at;
                        $dateStr = !empty($vDate) ? Carbon::parse($vDate)->format('d-m-Y') : '';
                        $amt = (float)($ev->total_amount ?? $ev->amount ?? 0);

                        $txns[] = [
                            'date' => $dateStr,
                            'ref' => 'EV',
                            'inv_no' => $ev->evid ?? $ev->voucher_no ?? $ev->id,
                            'desc' => $ev->remarks ?? 'Expense Voucher',
                            'price' => null,
                            'debit_qty' => null,
                            'debit_amt' => $amt,
                            'credit_qty' => null,
                            'credit_amt' => null,
                        ];
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'incomes',
                'title' => 'Income Vouchers',
                'fetch' => function() use ($fromDate, $toDate) {
                    $vouchers = IncomeVoucher::withoutGlobalScopes()
                        ->where(function($q) use ($fromDate, $toDate) {
                            $q->whereBetween('entry_date', [$fromDate, $toDate])
                              ->orWhereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
                        })
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($vouchers as $iv) {
                        $vDate = $iv->entry_date ?? $iv->created_at;
                        $dateStr = !empty($vDate) ? Carbon::parse($vDate)->format('d-m-Y') : '';
                        $amt = (float)($iv->total_amount ?? $iv->amount ?? 0);

                        $txns[] = [
                            'date' => $dateStr,
                            'ref' => 'IV',
                            'inv_no' => $iv->ivid ?? $iv->voucher_no ?? $iv->id,
                            'desc' => $iv->remarks ?? 'Income Voucher',
                            'price' => null,
                            'debit_qty' => null,
                            'debit_amt' => null,
                            'credit_qty' => null,
                            'credit_amt' => $amt,
                        ];
                    }
                    return $txns;
                }
            ],
            [
                'key' => 'journals',
                'title' => 'Journal Vouchers',
                'fetch' => function() use ($fromDate, $toDate) {
                    $vouchers = JournalVoucher::withoutGlobalScopes()
                        ->where(function($q) use ($fromDate, $toDate) {
                            $q->whereBetween('entry_date', [$fromDate, $toDate])
                              ->orWhereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
                        })
                        ->latest('id')
                        ->get();

                    $txns = [];
                    foreach ($vouchers as $jv) {
                        $vDate = $jv->entry_date ?? $jv->created_at;
                        $dateStr = !empty($vDate) ? Carbon::parse($vDate)->format('d-m-Y') : '';
                        $amt = (float)($jv->total_debit ?? $jv->amount ?? 0);

                        $txns[] = [
                            'date' => $dateStr,
                            'ref' => 'JV',
                            'inv_no' => $jv->jvid ?? $jv->voucher_no ?? $jv->id,
                            'desc' => $jv->remarks ?? 'Journal Voucher',
                            'price' => null,
                            'debit_qty' => null,
                            'debit_amt' => $amt,
                            'credit_qty' => null,
                            'credit_amt' => null,
                        ];
                    }
                    return $txns;
                }
            ]
        ];

        foreach ($modules as $mod) {
            $fetcher = $mod['fetch'];
            $formattedTxns = $fetcher();

            if (empty($formattedTxns)) {
                continue; // Skip form types with zero activity
            }

            $secDebitQty = 0.0;
            $secDebitAmt = 0.0;
            $secCreditQty = 0.0;
            $secCreditAmt = 0.0;

            foreach ($formattedTxns as $t) {
                $secDebitQty += (float)($t['debit_qty'] ?? 0);
                $secDebitAmt += (float)($t['debit_amt'] ?? 0);
                $secCreditQty += (float)($t['credit_qty'] ?? 0);
                $secCreditAmt += (float)($t['credit_amt'] ?? 0);
            }

            $sections[] = [
                'title' => $mod['title'],
                'transactions' => $formattedTxns,
                'subtotal' => [
                    'debit_qty' => $secDebitQty,
                    'debit_amt' => $secDebitAmt,
                    'credit_qty' => $secCreditQty,
                    'credit_amt' => $secCreditAmt,
                ]
            ];

            $grandTotal['debit_qty'] += $secDebitQty;
            $grandTotal['debit_amt'] += $secDebitAmt;
            $grandTotal['credit_qty'] += $secCreditQty;
            $grandTotal['credit_amt'] += $secCreditAmt;
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'sections' => $sections,
            'grand_total' => $grandTotal,
            'generated_at' => now(),
        ];
    }
}
