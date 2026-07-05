<?php

namespace App\Services;

use App\Http\Controllers\GeneralLedgerController;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\ReceiptsVoucher;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerOutstandingBalanceReportBuilder
{
    private array $filters = [];

    private const PARTY_TYPES = ['customer', 'walking', 'walkin'];

    private const DETAIL_COLS = [
        'sales', 'payment', 'oth_inc', 'jv_dr',
        'purchase', 's_ret', 'claim_cn', 'receipts', 'exp_dis', 'jv_cr',
    ];

    public function build(Request $request): array
    {
        if ($request->input('report_type') === 'detailed') {
            return $this->buildDetailed($request);
        }

        return $this->buildShort($request);
    }

    public function buildShort(Request $request): array
    {
        $this->filters = $this->extractFilters($request);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $closingDate = Carbon::parse($toDate)->addDay()->toDateString();

        /** @var GeneralLedgerController $ledger */
        $ledger = app(GeneralLedgerController::class);

        $rows = [];
        $grand = [
            'opening' => 0.0,
            'sales' => 0.0,
            'sr_pj' => 0.0,
            'receipts' => 0.0,
            'balance' => 0.0,
        ];

        foreach ($this->filteredCustomers() as $customer) {
            $customerId = (int) $customer->id;
            $opening = (float) $ledger->calculateOpeningBalance('customer', $customerId, $fromDate);
            $sales = $this->periodSales($customerId, $fromDate, $toDate);
            $srPj = $this->periodSrPj($customerId, $fromDate, $toDate);
            $receipts = $this->periodReceipts($customerId, $fromDate, $toDate);
            $balance = (float) $ledger->calculateOpeningBalance('customer', $customerId, $closingDate);

            if ($this->isZeroShortRow($opening, $sales, $srPj, $receipts, $balance)) {
                continue;
            }

            $rows[] = [
                'customer_id' => $customerId,
                'customer_name' => strtoupper($customer->customer_name ?? ('Customer #' . $customerId)),
                'opening' => $opening,
                'sales' => $sales,
                'sr_pj' => $srPj,
                'receipts' => $receipts,
                'balance' => $balance,
            ];

            foreach (array_keys($grand) as $key) {
                $grand[$key] += $rows[array_key_last($rows)][$key];
            }
        }

        return [
            'report_type' => 'short',
            'rows' => $rows,
            'grand' => $grand,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'generated_at' => now(),
        ];
    }

    public function buildDetailed(Request $request): array
    {
        $this->filters = $this->extractFilters($request);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $closingDate = Carbon::parse($toDate)->addDay()->toDateString();

        /** @var GeneralLedgerController $ledger */
        $ledger = app(GeneralLedgerController::class);

        $rows = [];
        $grand = array_merge(
            ['opening' => 0.0, 'balance' => 0.0],
            array_fill_keys(self::DETAIL_COLS, 0.0)
        );

        foreach ($this->filteredCustomers() as $customer) {
            $customerId = (int) $customer->id;
            $opening = (float) $ledger->calculateOpeningBalance('customer', $customerId, $fromDate);
            $period = $this->aggregateDetailedPeriod($customerId, $fromDate, $toDate);
            $balance = (float) $ledger->calculateOpeningBalance('customer', $customerId, $closingDate);

            if ($this->isZeroDetailedRow($opening, $period, $balance)) {
                continue;
            }

            $row = array_merge([
                'customer_id' => $customerId,
                'customer_name' => strtoupper($customer->customer_name ?? ('Customer #' . $customerId)),
                'opening' => $opening,
                'balance' => $balance,
            ], $period);

            $rows[] = $row;

            $grand['opening'] += $opening;
            $grand['balance'] += $balance;
            foreach (self::DETAIL_COLS as $col) {
                $grand[$col] += $period[$col];
            }
        }

        return [
            'report_type' => 'detailed',
            'rows' => $rows,
            'grand' => $grand,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'generated_at' => now(),
        ];
    }

    private function aggregateDetailedPeriod(int $customerId, string $from, string $to): array
    {
        /** @var GeneralLedgerController $ledger */
        $ledger = app(GeneralLedgerController::class);
        $txns = $ledger->fetchTransactions('customer', $customerId, $from, $to);

        $cols = array_fill_keys(self::DETAIL_COLS, 0.0);

        foreach ($txns as $txn) {
            $this->bucketDetailedTxn($txn, $cols);
        }

        return $cols;
    }

    private function bucketDetailedTxn(array $txn, array &$cols): void
    {
        $ref = strtoupper((string) ($txn['ref'] ?? ''));
        $debit = (float) ($txn['debit'] ?? 0);
        $credit = (float) ($txn['credit'] ?? 0);
        $desc = strtolower((string) ($txn['desc'] ?? ''));

        if ($ref === 'SJ' && $debit > 0) {
            $cols['sales'] += $debit;

            return;
        }

        if ($ref === 'PV' && $debit > 0) {
            $cols['payment'] += $debit;

            return;
        }

        if ($ref === 'PRJ' && $debit > 0) {
            $cols['payment'] += $debit;

            return;
        }

        if ($ref === 'IV' && $debit > 0) {
            $cols['oth_inc'] += $debit;

            return;
        }

        if ($ref === 'JV' && $debit > 0) {
            $cols['jv_dr'] += $debit;

            return;
        }

        if ($ref === 'PJ' && $credit > 0) {
            $cols['purchase'] += $credit;

            return;
        }

        if (in_array($ref, ['SRJ', 'SR'], true) && $credit > 0) {
            $cols['s_ret'] += $credit;

            return;
        }

        if ($ref === 'SRJ' && $debit > 0) {
            $cols['exp_dis'] += $debit;

            return;
        }

        if ($ref === 'CIR' && $debit > 0) {
            $cols['claim_cn'] += $debit;

            return;
        }

        if ($ref === 'CLM' && $credit > 0) {
            $cols['s_ret'] += $credit;

            return;
        }

        if ($ref === 'RV' && $credit > 0) {
            if (str_contains($desc, 'discount')) {
                $cols['exp_dis'] += $credit;
            } else {
                $cols['receipts'] += $credit;
            }

            return;
        }

        if ($ref === 'EV' && $credit > 0) {
            $cols['exp_dis'] += $credit;

            return;
        }

        if ($ref === 'JV' && $credit > 0) {
            $cols['jv_cr'] += $credit;

            return;
        }

        if ($ref === 'AV') {
            if ($debit > 0) {
                $cols['jv_dr'] += $debit;
            }
            if ($credit > 0) {
                $cols['jv_cr'] += $credit;
            }

            return;
        }

        if ($ref === 'VO') {
            if ($credit > 0) {
                $cols['exp_dis'] += $credit;
            } elseif ($debit > 0 && str_contains($desc, 'discount')) {
                $cols['exp_dis'] += $debit;
            }
        }
    }

    private function extractFilters(Request $request): array
    {
        return [
            'user_groups' => $request->user_group ?? [],
            'customers' => $request->customer ?? [],
            'totalGroups' => UserGroup::count(),
            'totalCustomers' => Customer::count(),
        ];
    }

    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function filteredCustomers()
    {
        $query = Customer::query()->orderBy('customer_name');

        if ($this->shouldApplyFilter($this->filters['user_groups'], $this->filters['totalGroups'])) {
            $groups = $this->filters['user_groups'];
            $query->where(function ($sub) use ($groups) {
                foreach ($groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($this->filters['customers'], $this->filters['totalCustomers'])) {
            $query->whereIn('id', $this->filters['customers']);
        }

        return $query->get();
    }

    private function getDateColumn(string $table, string $fallback = 'DATE(created_at)'): string
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            if (Schema::hasColumn($table, 'entry_date')) {
                $cache[$table] = "COALESCE(entry_date, $fallback)";
            } else {
                $cache[$table] = $fallback;
            }
        }

        return $cache[$table];
    }

    private function periodSales(int $customerId, string $from, string $to): float
    {
        $dateCol = $this->getDateColumn('sales');

        return (float) Sale::where('customer_id', $customerId)
            ->whereIn('partyType', self::PARTY_TYPES)
            ->whereBetween(DB::raw($dateCol), [$from, $to])
            ->sum('sub_total2');
    }

    private function periodSrPj(int $customerId, string $from, string $to): float
    {
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $saleReturns = (float) SaleReturn::where('customer_id', $customerId)
            ->whereIn('party_type', self::PARTY_TYPES)
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($srDateCol), [$from, $to])
            ->sum('total_balance');

        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = (float) Purchase::where('purchasable_id', $customerId)
            ->where('purchasable_type', Customer::class)
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($pjDateCol), [$from, $to])
            ->sum('net_amount');

        return $saleReturns + $purchases;
    }

    private function periodReceipts(int $customerId, string $from, string $to): float
    {
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $vouchers = ReceiptsVoucher::where('party_id', $customerId)
            ->whereIn('type', self::PARTY_TYPES)
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($rvDateCol), [$from, $to])
            ->get();

        $total = 0.0;
        foreach ($vouchers as $voucher) {
            $total += (float) $voucher->total_amount;
            $discounts = json_decode($voucher->discount_value, true);
            if (is_array($discounts)) {
                foreach ($discounts as $discount) {
                    $total += (float) $discount;
                }
            }
        }

        return $total;
    }

    private function isZeroShortRow(float $opening, float $sales, float $srPj, float $receipts, float $balance): bool
    {
        foreach ([$opening, $sales, $srPj, $receipts, $balance] as $value) {
            if (abs($value) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    private function isZeroDetailedRow(float $opening, array $period, float $balance): bool
    {
        if (abs($opening) > 0.0001 || abs($balance) > 0.0001) {
            return false;
        }

        foreach (self::DETAIL_COLS as $col) {
            if (abs($period[$col] ?? 0) > 0.0001) {
                return false;
            }
        }

        return true;
    }
}
