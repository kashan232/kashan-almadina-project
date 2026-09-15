<?php

namespace App\Services;

use App\Http\Controllers\GeneralLedgerController;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyReportBuilder
{
    /**
     * Build daily activity report grouped by accounts/heads.
     */
    public function build(Request $request): array
    {
        $fromDate = $request->input('from_date', date('Y-m-d'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        $closingDate = Carbon::parse($toDate)->addDay()->toDateString();

        /** @var GeneralLedgerController $ledger */
        $ledger = app(GeneralLedgerController::class);

        $selectedAccounts = $request->input('accounts', []);
        $selectedPartyTypes = $request->input('party_types', []);

        // Determine which accounts to include
        $accountsToProcess = $this->getAccountsToProcess($selectedAccounts, $selectedPartyTypes);

        $sections = [];
        $grandTotal = [
            'opening' => 0.0,
            'debit_qty' => 0.0,
            'debit_amt' => 0.0,
            'credit_qty' => 0.0,
            'credit_amt' => 0.0,
            'closing' => 0.0,
        ];

        foreach ($accountsToProcess as $accInfo) {
            $ledgerType = $accInfo['type'];
            $entityId = $accInfo['id'];
            $title = $accInfo['title'];
            $code = $accInfo['code'];
            $headName = $accInfo['head_name'];

            // Fetch opening balance
            $opening = (float) $ledger->calculateOpeningBalance($ledgerType, $entityId, $fromDate);

            // Fetch period transactions
            $txns = $ledger->fetchTransactions($ledgerType, $entityId, $fromDate, $toDate);

            if (empty($txns) && abs($opening) < 0.001) {
                continue; // Skip accounts with no activity and zero opening
            }

            $runningBalance = $opening;
            $secDebitQty = 0.0;
            $secDebitAmt = 0.0;
            $secCreditQty = 0.0;
            $secCreditAmt = 0.0;

            $formattedTxns = [];
            foreach ($txns as $t) {
                $debit = (float)($t['debit'] ?? 0);
                $credit = (float)($t['credit'] ?? 0);
                $debitQty = (float)($t['debit_qty'] ?? $t['qty'] ?? 0);
                $creditQty = (float)($t['credit_qty'] ?? 0);

                // Update running balance based on nature
                if (in_array($ledgerType, ['customer', 'walkin', 'asset', 'bank', 'cash', 'expense'])) {
                    $runningBalance += ($debit - $credit);
                } else {
                    $runningBalance += ($credit - $debit);
                }

                $secDebitAmt += $debit;
                $secCreditAmt += $credit;
                $secDebitQty += $debitQty;
                $secCreditQty += $creditQty;

                $formattedTxns[] = [
                    'date' => !empty($t['date']) ? Carbon::parse($t['date'])->format('d-m-y') : '',
                    'ref' => $t['ref'] ?? '',
                    'inv_no' => $t['inv_no'] ?? $t['voucher_no'] ?? '',
                    'desc' => $t['desc'] ?? '',
                    'price' => $t['price'] ?? null,
                    'debit_qty' => $debitQty > 0 ? $debitQty : null,
                    'debit_amt' => $debit > 0 ? $debit : null,
                    'credit_qty' => $creditQty > 0 ? $creditQty : null,
                    'credit_amt' => $credit > 0 ? $credit : null,
                    'balance' => $runningBalance,
                    'balance_type' => $runningBalance >= 0 ? 'DR.' : 'CR.',
                ];
            }

            $closing = (float) $ledger->calculateOpeningBalance($ledgerType, $entityId, $closingDate);

            $sections[] = [
                'code' => $code,
                'title' => $title,
                'head_name' => $headName,
                'type' => $ledgerType,
                'opening' => $opening,
                'opening_type' => $opening >= 0 ? 'DR.' : 'CR.',
                'transactions' => $formattedTxns,
                'subtotal' => [
                    'debit_qty' => $secDebitQty,
                    'debit_amt' => $secDebitAmt,
                    'credit_qty' => $secCreditQty,
                    'credit_amt' => $secCreditAmt,
                    'closing' => $closing,
                    'closing_type' => $closing >= 0 ? 'DR.' : 'CR.',
                ]
            ];

            $grandTotal['opening'] += $opening;
            $grandTotal['debit_qty'] += $secDebitQty;
            $grandTotal['debit_amt'] += $secDebitAmt;
            $grandTotal['credit_qty'] += $secCreditQty;
            $grandTotal['credit_amt'] += $secCreditAmt;
            $grandTotal['closing'] += $closing;
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'sections' => $sections,
            'grand_total' => $grandTotal,
            'generated_at' => now(),
        ];
    }

    private function getAccountsToProcess(array $selectedAccounts, array $selectedPartyTypes): array
    {
        $list = [];

        if (!empty($selectedAccounts)) {
            foreach ($selectedAccounts as $item) {
                $parts = explode(':', $item);
                if (count($parts) === 2) {
                    $type = $parts[0];
                    $id = (int)$parts[1];
                    $info = $this->resolveEntityInfo($type, $id);
                    if ($info) $list[] = $info;
                }
            }
            return $list;
        }

        if (empty($selectedPartyTypes) || in_array('customer', $selectedPartyTypes) || in_array('walkin', $selectedPartyTypes)) {
            $customers = Customer::orderBy('customer_name')->get();
            foreach ($customers as $c) {
                $type = $c->customer_type === 'Walking Customer' ? 'walkin' : 'customer';
                $list[] = [
                    'type' => $type,
                    'id' => $c->id,
                    'code' => $c->id,
                    'title' => strtoupper($c->customer_name),
                    'head_name' => 'CUSTOMER / WALKING ACCOUNTS',
                ];
            }
        }

        if (empty($selectedPartyTypes) || in_array('vendor', $selectedPartyTypes)) {
            $vendors = Vendor::orderBy('name')->get();
            foreach ($vendors as $v) {
                $list[] = [
                    'type' => 'vendor',
                    'id' => $v->id,
                    'code' => $v->id,
                    'title' => strtoupper($v->name),
                    'head_name' => 'VENDOR ACCOUNTS',
                ];
            }
        }

        if (empty($selectedPartyTypes) || in_array('account', $selectedPartyTypes) || in_array('bank', $selectedPartyTypes) || in_array('expense', $selectedPartyTypes)) {
            $accounts = Account::with('accountHead')->orderBy('account_code')->get();
            foreach ($accounts as $acc) {
                $headName = strtoupper($acc->accountHead->name ?? 'GENERAL ACCOUNTS');
                $list[] = [
                    'type' => 'account',
                    'id' => $acc->id,
                    'code' => $acc->account_code ?: $acc->id,
                    'title' => strtoupper($acc->title),
                    'head_name' => $headName,
                ];
            }
        }

        return $list;
    }

    private function resolveEntityInfo(string $type, int $id): ?array
    {
        if (in_array($type, ['customer', 'walkin'])) {
            $c = Customer::find($id);
            if (!$c) return null;
            return [
                'type' => $type,
                'id' => $c->id,
                'code' => $c->id,
                'title' => strtoupper($c->customer_name),
                'head_name' => 'CUSTOMER ACCOUNTS',
            ];
        }

        if ($type === 'vendor') {
            $v = Vendor::find($id);
            if (!$v) return null;
            return [
                'type' => 'vendor',
                'id' => $v->id,
                'code' => $v->id,
                'title' => strtoupper($v->name),
                'head_name' => 'VENDOR ACCOUNTS',
            ];
        }

        if ($type === 'account') {
            $acc = Account::with('accountHead')->find($id);
            if (!$acc) return null;
            return [
                'type' => 'account',
                'id' => $acc->id,
                'code' => $acc->account_code ?: $acc->id,
                'title' => strtoupper($acc->title),
                'head_name' => strtoupper($acc->accountHead->name ?? 'GENERAL ACCOUNTS'),
            ];
        }

        return null;
    }
}
