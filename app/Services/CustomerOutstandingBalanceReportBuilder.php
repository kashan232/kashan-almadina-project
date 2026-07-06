<?php

namespace App\Services;

use App\Http\Controllers\GeneralLedgerController;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PaymentVoucher;
use App\Models\ReceiptsVoucher;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\UserGroup;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerOutstandingBalanceReportBuilder
{
    private array $filters = [];

    private const CUSTOMER_PARTY_TYPES = ['customer', 'walking', 'walkin'];

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

        foreach ($this->filteredParties() as $party) {
            $partyId = (int) $party['id'];
            $ledgerType = $party['ledger_type'];
            $opening = (float) $ledger->calculateOpeningBalance($ledgerType, $partyId, $fromDate);
            $sales = $this->periodSalesForParty($ledgerType, $partyId, $fromDate, $toDate);
            $srPj = $this->periodSrPjForParty($ledgerType, $partyId, $fromDate, $toDate);
            $receipts = $this->periodReceiptsForParty($ledgerType, $partyId, $fromDate, $toDate);
            $balance = (float) $ledger->calculateOpeningBalance($ledgerType, $partyId, $closingDate);

            if ($this->isZeroShortRow($opening, $sales, $srPj, $receipts, $balance)) {
                continue;
            }

            $rows[] = [
                'party_id' => $partyId,
                'party_type' => $party['party_kind'],
                'party_type_label' => $party['type_label'],
                'customer_id' => $partyId,
                'customer_name' => strtoupper($party['name']),
                'party_name' => strtoupper($party['name']),
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

        foreach ($this->filteredParties() as $party) {
            $partyId = (int) $party['id'];
            $ledgerType = $party['ledger_type'];
            $opening = (float) $ledger->calculateOpeningBalance($ledgerType, $partyId, $fromDate);
            $period = $this->aggregateDetailedPeriod($ledgerType, $partyId, $fromDate, $toDate);
            $balance = (float) $ledger->calculateOpeningBalance($ledgerType, $partyId, $closingDate);

            if ($this->isZeroDetailedRow($opening, $period, $balance)) {
                continue;
            }

            $row = array_merge([
                'party_id' => $partyId,
                'party_type' => $party['party_kind'],
                'party_type_label' => $party['type_label'],
                'customer_id' => $partyId,
                'customer_name' => strtoupper($party['name']),
                'party_name' => strtoupper($party['name']),
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

    private function aggregateDetailedPeriod(string $ledgerType, int $partyId, string $from, string $to): array
    {
        /** @var GeneralLedgerController $ledger */
        $ledger = app(GeneralLedgerController::class);
        $txns = $ledger->fetchTransactions($ledgerType, $partyId, $from, $to);

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
        $partyTypes = array_map('strtolower', array_filter((array) ($request->party_type ?? [])));

        return [
            'user_groups' => $request->user_group ?? [],
            'party_types' => $partyTypes,
            'selected_parties' => $this->parsePartySelections((array) ($request->party ?? [])),
            'totalGroups' => UserGroup::count(),
            'totalMainCustomers' => Customer::where('customer_type', 'Main Customer')->count(),
            'totalWalkinCustomers' => Customer::where('customer_type', 'Walking Customer')->count(),
            'totalVendors' => Vendor::count(),
        ];
    }

    /** @return list<array{kind: string, id: int}> */
    private function parsePartySelections(array $raw): array
    {
        $out = [];
        foreach ($raw as $token) {
            if (preg_match('/^(customer|walkin|vendor):(\d+)$/', (string) $token, $m)) {
                $out[] = ['kind' => $m[1], 'id' => (int) $m[2]];
            }
        }

        return $out;
    }

    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    /** @return list<array{id: int, name: string, ledger_type: string, party_kind: string, type_label: string}> */
    private function filteredParties(): array
    {
        $allowedTypes = $this->filters['party_types'];
        if (empty($allowedTypes)) {
            $allowedTypes = ['customer', 'walkin', 'vendor'];
        }

        $parties = [];

        if (in_array('customer', $allowedTypes, true)) {
            foreach ($this->filteredCustomersByType('Main Customer', 'customer') as $customer) {
                $parties[] = $this->partyRow($customer, 'customer', 'Customer');
            }
        }

        if (in_array('walkin', $allowedTypes, true)) {
            foreach ($this->filteredCustomersByType('Walking Customer', 'walkin') as $customer) {
                $parties[] = $this->partyRow($customer, 'walkin', 'Walking');
            }
        }

        if (in_array('vendor', $allowedTypes, true)) {
            foreach ($this->filteredVendors() as $vendor) {
                $parties[] = [
                    'id' => (int) $vendor->id,
                    'name' => $vendor->name ?? ('Vendor #' . $vendor->id),
                    'ledger_type' => 'vendor',
                    'party_kind' => 'vendor',
                    'type_label' => 'Vendor',
                ];
            }
        }

        $selected = $this->filters['selected_parties'];
        if (!empty($selected)) {
            $parties = array_values(array_filter($parties, function (array $party) use ($selected) {
                foreach ($selected as $sel) {
                    if ($sel['kind'] === $party['party_kind'] && $sel['id'] === $party['id']) {
                        return true;
                    }
                }

                return false;
            }));
        }

        usort($parties, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $parties;
    }

    private function partyRow(Customer $customer, string $kind, string $label): array
    {
        return [
            'id' => (int) $customer->id,
            'name' => $customer->customer_name ?? ('Customer #' . $customer->id),
            'ledger_type' => 'customer',
            'party_kind' => $kind,
            'type_label' => $label,
        ];
    }

    private function filteredCustomersByType(string $customerType, string $partyKind)
    {
        $query = Customer::query()
            ->where('customer_type', $customerType)
            ->orderBy('customer_name');

        if ($this->shouldApplyFilter($this->filters['user_groups'], $this->filters['totalGroups'])) {
            $groups = $this->filters['user_groups'];
            $query->where(function ($sub) use ($groups) {
                foreach ($groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        $selectedForKind = array_values(array_filter(
            $this->filters['selected_parties'],
            fn ($p) => $p['kind'] === $partyKind
        ));
        $totalForKind = $customerType === 'Main Customer'
            ? $this->filters['totalMainCustomers']
            : $this->filters['totalWalkinCustomers'];

        if ($this->shouldApplyFilter(
            array_column($selectedForKind, 'id'),
            $totalForKind
        )) {
            $query->whereIn('id', array_column($selectedForKind, 'id'));
        }

        return $query->get();
    }

    private function filteredVendors()
    {
        $query = Vendor::query()->orderBy('name');

        if ($this->shouldApplyFilter($this->filters['user_groups'], $this->filters['totalGroups'])) {
            $groups = $this->filters['user_groups'];
            $query->where(function ($sub) use ($groups) {
                foreach ($groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        $selectedVendorIds = array_column(
            array_filter($this->filters['selected_parties'], fn ($p) => $p['kind'] === 'vendor'),
            'id'
        );

        if ($this->shouldApplyFilter($selectedVendorIds, $this->filters['totalVendors'])) {
            $query->whereIn('id', $selectedVendorIds);
        }

        return $query->get();
    }

    private function periodSalesForParty(string $ledgerType, int $partyId, string $from, string $to): float
    {
        return $ledgerType === 'vendor'
            ? $this->periodSalesVendor($partyId, $from, $to)
            : $this->periodSales($partyId, $from, $to);
    }

    private function periodSrPjForParty(string $ledgerType, int $partyId, string $from, string $to): float
    {
        return $ledgerType === 'vendor'
            ? $this->periodSrPjVendor($partyId, $from, $to)
            : $this->periodSrPj($partyId, $from, $to);
    }

    private function periodReceiptsForParty(string $ledgerType, int $partyId, string $from, string $to): float
    {
        return $ledgerType === 'vendor'
            ? $this->periodPaymentsVendor($partyId, $from, $to)
            : $this->periodReceipts($partyId, $from, $to);
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
            ->whereIn('partyType', self::CUSTOMER_PARTY_TYPES)
            ->whereBetween(DB::raw($dateCol), [$from, $to])
            ->sum('sub_total2');
    }

    private function periodSalesVendor(int $vendorId, string $from, string $to): float
    {
        $dateCol = $this->getDateColumn('sales');

        return (float) Sale::where('customer_id', $vendorId)
            ->where('partyType', 'vendor')
            ->whereBetween(DB::raw($dateCol), [$from, $to])
            ->sum('sub_total2');
    }

    private function periodSrPj(int $customerId, string $from, string $to): float
    {
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $saleReturns = (float) SaleReturn::where('customer_id', $customerId)
            ->whereIn('party_type', self::CUSTOMER_PARTY_TYPES)
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

    private function periodSrPjVendor(int $vendorId, string $from, string $to): float
    {
        $srDateCol = $this->getDateColumn('sale_returns', 'current_date');
        $saleReturns = (float) SaleReturn::where('customer_id', $vendorId)
            ->where('party_type', 'vendor')
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($srDateCol), [$from, $to])
            ->sum('total_balance');

        $prDateCol = $this->getDateColumn('purchase_returns', 'current_date');
        $purchaseReturns = (float) PurchaseReturn::where(function ($q) use ($vendorId) {
                $q->where(function ($q3) use ($vendorId) {
                    $q3->where('vendor_id', $vendorId)->where(function ($q4) {
                        $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                    });
                })->orWhere(function ($q2) use ($vendorId) {
                    $q2->where('purchasable_id', $vendorId)
                        ->where('purchasable_type', Vendor::class);
                });
            })
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($prDateCol), [$from, $to])
            ->sum('net_amount');

        $pjDateCol = $this->getDateColumn('purchases', 'current_date');
        $purchases = (float) Purchase::where(function ($q) use ($vendorId) {
                $q->where(function ($q3) use ($vendorId) {
                    $q3->where('vendor_id', $vendorId)->where(function ($q4) {
                        $q4->whereNull('purchasable_type')->orWhere('purchasable_type', '');
                    });
                })->orWhere(function ($q2) use ($vendorId) {
                    $q2->where('purchasable_id', $vendorId)
                        ->where('purchasable_type', Vendor::class);
                });
            })
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($pjDateCol), [$from, $to])
            ->sum('net_amount');

        return $saleReturns + $purchaseReturns + $purchases;
    }

    private function periodReceipts(int $customerId, string $from, string $to): float
    {
        $rvDateCol = $this->getDateColumn('receipts_vouchers', 'receipt_date');
        $vouchers = ReceiptsVoucher::where('party_id', $customerId)
            ->whereIn('type', self::CUSTOMER_PARTY_TYPES)
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

    private function periodPaymentsVendor(int $vendorId, string $from, string $to): float
    {
        $pvDateCol = $this->getDateColumn('payment_vouchers', 'receipt_date');
        $vouchers = PaymentVoucher::where('party_id', $vendorId)
            ->where('type', 'vendor')
            ->whereIn('status', ['posted', 'Posted'])
            ->whereBetween(DB::raw($pvDateCol), [$from, $to])
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
