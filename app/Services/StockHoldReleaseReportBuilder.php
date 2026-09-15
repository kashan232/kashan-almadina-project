<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\StockHold;
use App\Models\StockRelease;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockHoldReleaseReportBuilder
{
    private array $filters = [];

    public function build(Request $request): array
    {
        $this->filters = $this->extractFilters($request);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $reportType = $this->filters['report_type'];

        if ($reportType === 'detailed') {
            return $this->buildDetailedStatement($fromDate, $toDate);
        }

        if ($reportType === 'hold_only') {
            return $this->buildHoldOnlyReport($fromDate, $toDate);
        }

        if ($reportType === 'release_only') {
            return $this->buildReleaseOnlyReport($fromDate, $toDate);
        }

        if ($reportType === 'hold_balance_only') {
            return $this->buildHoldBalanceOnlyReport($fromDate, $toDate);
        }

        $buckets = [];

        $this->collectHoldMovements($buckets, $fromDate, $toDate);
        $this->collectReleaseMovements($buckets, $fromDate, $toDate);
        $this->seedCustomerItemBuckets($buckets);

        if ($reportType === 'item') {
            $groups = $this->compileCustomerItemGroups($buckets);
        } else {
            $groups = $this->compilePartyGroups($buckets);
        }

        $grand = $this->sumTotals(collect($groups)->pluck('totals')->all());

        return [
            'groups' => $groups,
            'grand' => $grand,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_type' => $reportType,
            'generated_at' => now(),
        ];
    }

    private function extractFilters(Request $request): array
    {
        return [
            'report_type' => in_array($request->report_type, ['party', 'item', 'detailed', 'hold_only', 'release_only', 'hold_balance_only'], true) ? $request->report_type : 'party',
            'user_groups' => $request->user_group ?? [],
            'warehouses' => $request->warehouse ?? [],
            'parties' => $request->party ?? [],
            'items' => $request->item ?? [],
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'totalGroups' => UserGroup::count(),
            'totalWarehouses' => Warehouse::withoutGlobalScopes()->count() + 1,
            'totalParties' => Vendor::count() + Customer::count(),
            'totalProducts' => Product::count(),
        ];
    }

    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyUserGroupFilter($query): void
    {
        if (!$this->shouldApplyFilter($this->filters['user_groups'], $this->filters['totalGroups'])) {
            return;
        }

        $groups = $this->filters['user_groups'];
        $query->where(function ($sub) use ($groups) {
            foreach ($groups as $gid) {
                $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                    ->orWhereJsonContains('user_group_ids', (int) $gid);
            }
        });
    }

    private function warehouseMatches(?int $warehouseId): bool
    {
        if (!$this->shouldApplyFilter($this->filters['warehouses'], $this->filters['totalWarehouses'])) {
            return true;
        }

        return in_array((string) ($warehouseId ?? 0), array_map('strval', $this->filters['warehouses']), true);
    }

    private function productMatches(int $productId): bool
    {
        if (!$this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts'])) {
            return true;
        }

        return in_array($productId, array_map('intval', $this->filters['items']));
    }

    private function partyMatches(string $partyType, ?int $partyId): bool
    {
        if (!$this->shouldApplyFilter($this->filters['parties'], $this->filters['totalParties'])) {
            return true;
        }

        if (!$partyId) {
            return false;
        }

        $token = strtolower($partyType) . ':' . (int) $partyId;

        return in_array($token, array_map('strtolower', $this->filters['parties']), true);
    }

    private function bucketKey(string $partyType, ?int $partyId, int $productId): string
    {
        return strtolower($partyType) . ':' . (int) $partyId . '|' . $productId;
    }

    private function ensureBucket(array &$buckets, string $partyKey, string $partyName, int $productId, string $productName): void
    {
        $key = $partyKey . '|' . $productId;
        if (!isset($buckets[$key])) {
            $buckets[$key] = [
                'party_key' => $partyKey,
                'party_name' => $partyName,
                'product_id' => $productId,
                'product_name' => $productName,
                'opening' => 0.0,
                'hold' => 0.0,
                'rel' => 0.0,
                'sources' => [],
                'item_details' => [],
            ];
        }
    }

    private function resolvePartyFromHold(StockHold $hold): array
    {
        $voucher = $hold->voucher;
        $partyType = strtolower((string) ($hold->party_type ?: $voucher?->party_type ?: 'unknown'));
        $partyId = (int) ($hold->party_id ?: $voucher?->party_id ?: 0);

        return [$partyType, $partyId, $this->resolvePartyName($partyType, $partyId, $hold, $voucher)];
    }

    private function resolvePartyFromRelease(StockRelease $release): array
    {
        $voucher = $release->voucher;
        $hold = $release->hold;
        $holdVoucher = $hold?->voucher;

        $partyType = strtolower((string) (
            $release->party_type
            ?: $voucher?->party_type
            ?: $hold?->party_type
            ?: $holdVoucher?->party_type
            ?: 'unknown'
        ));
        $partyId = (int) (
            $release->party_id
            ?: $voucher?->party_id
            ?: $hold?->party_id
            ?: $holdVoucher?->party_id
            ?: 0
        );

        return [$partyType, $partyId, $this->resolvePartyName($partyType, $partyId, $hold, $voucher ?: $holdVoucher)];
    }

    private function resolvePartyName(string $partyType, int $partyId, ?StockHold $hold = null, $voucher = null): string
    {
        if ($partyType === 'vendor' && $partyId) {
            if ($hold?->relationLoaded('partyVendor') && $hold->partyVendor) {
                return strtoupper($hold->partyVendor->name ?? 'VENDOR');
            }
            if ($voucher?->relationLoaded('partyVendor') && $voucher->partyVendor) {
                return strtoupper($voucher->partyVendor->name ?? 'VENDOR');
            }

            return strtoupper(Vendor::find($partyId)?->name ?? 'VENDOR #' . $partyId);
        }

        if ($partyType === 'customer' && $partyId) {
            if ($hold?->relationLoaded('partyCustomer') && $hold->partyCustomer) {
                return strtoupper($hold->partyCustomer->customer_name ?? 'CUSTOMER');
            }
            if ($voucher?->relationLoaded('partyCustomer') && $voucher->partyCustomer) {
                return strtoupper($voucher->partyCustomer->customer_name ?? 'CUSTOMER');
            }

            return strtoupper(Customer::find($partyId)?->customer_name ?? 'CUSTOMER #' . $partyId);
        }

        if (in_array($partyType, ['walkin', 'walking', 'walk-in'], true)) {
            return 'WALK IN CUSTOMER';
        }

        return strtoupper($partyType ?: 'UNKNOWN PARTY');
    }

    private function pickDate($model, array $columns): string
    {
        foreach ($columns as $col) {
            $val = data_get($model, $col);
            if (!empty($val)) {
                return Carbon::parse($val)->toDateString();
            }
        }

        return Carbon::parse($model->created_at ?? now())->toDateString();
    }

    private function resolveHoldSource(StockHold $hold): string
    {
        if (!empty(data_get($hold->meta, 'claim_no'))) {
            return 'Claim #' . data_get($hold->meta, 'claim_no');
        }
        if (!empty(data_get($hold->meta, 'claim_id'))) {
            return 'Claim Hold';
        }
        if (!empty($hold->remarks) && str_contains($hold->remarks, 'Customer Claim Hold')) {
            return 'Claim Hold';
        }
        if ($hold->sale || $hold->sale_id) {
            $invoiceNo = $hold->sale?->invoice_no ?? $hold->sale_id;
            return 'Sales Inv #' . $invoiceNo;
        }
        if ($hold->voucher) {
            return 'Stock Hold Vouc #' . $hold->voucher->voucher_no;
        }
        if (!empty($hold->remarks)) {
            return $hold->remarks;
        }

        return 'Stock Hold';
    }

    private function applyQtyToBucket(array &$buckets, array $meta, float $qty, ?string $fromDate, ?string $toDate, string $kind): void
    {
        if ($qty <= 0) {
            return;
        }

        [$partyType, $partyId, $partyName] = $meta['party'];
        if (!$this->partyMatches($partyType, $partyId)) {
            return;
        }

        $productId = (int) $meta['product_id'];
        if (!$this->productMatches($productId)) {
            return;
        }

        if (!$this->warehouseMatches($meta['warehouse_id'] ?? 0)) {
            return;
        }

        $partyKey = $partyType . ':' . $partyId;
        $this->ensureBucket($buckets, $partyKey, $partyName, $productId, $meta['product_name']);

        $key = $partyKey . '|' . $productId;
        $date = $meta['date'];

        if ($fromDate && $date < $fromDate) {
            // Movements prior to fromDate accumulate into Opening balance (Hold adds, Release subtracts)
            $buckets[$key]['opening'] += ($kind === 'hold' ? $qty : -$qty);
            return;
        }

        if ($this->dateInPeriod($date, $fromDate, $toDate)) {
            $buckets[$key][$kind === 'hold' ? 'hold' : 'rel'] += $qty;
            if (!empty($meta['entry_detail'])) {
                $buckets[$key]['item_details'][] = $meta['entry_detail'];
            }
            if ($kind === 'hold' && !empty($meta['source'])) {
                if (!in_array($meta['source'], $buckets[$key]['sources'], true)) {
                    $buckets[$key]['sources'][] = $meta['source'];
                }
            }
        }
    }

    private function resolveProductOpening(Product $product): float
    {
        if ($this->shouldApplyFilter($this->filters['warehouses'], $this->filters['totalWarehouses'])) {
            $total = 0.0;
            $warehouseStocks = is_array($product->opening_warehouse_stocks)
                ? $product->opening_warehouse_stocks
                : [];

            foreach ($this->filters['warehouses'] as $warehouseId) {
                $warehouseId = (string) $warehouseId;
                if ($warehouseId === '0') {
                    $total += (float) ($product->opening_shop_stock ?? 0);

                    continue;
                }

                $total += (float) (
                    $warehouseStocks[$warehouseId]
                    ?? $warehouseStocks[(int) $warehouseId]
                    ?? 0
                );
            }

            return $total;
        }

        if ($product->opening_total_stock !== null && $product->opening_total_stock !== '') {
            return (float) $product->opening_total_stock;
        }

        $warehouseStocks = is_array($product->opening_warehouse_stocks)
            ? $product->opening_warehouse_stocks
            : [];
        $warehouseTotal = (float) collect($warehouseStocks)->sum();

        if ($product->opening_shop_stock !== null && $product->opening_shop_stock !== '') {
            return (float) $product->opening_shop_stock + $warehouseTotal;
        }

        if ($warehouseTotal > 0) {
            return $warehouseTotal + (float) ($product->opening_shop_stock ?? $product->stock ?? 0);
        }

        return (float) ($product->stock ?? 0);
    }

    private function dateInPeriod(string $date, ?string $fromDate, ?string $toDate): bool
    {
        if ($fromDate && $date < $fromDate) {
            return false;
        }
        if ($toDate && $date > $toDate) {
            return false;
        }

        return true;
    }

    private function collectHoldMovements(array &$buckets, ?string $fromDate, ?string $toDate): void
    {
        StockHold::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'partyVendor:id,name',
                'partyCustomer:id,customer_name',
                'product:id,name',
                'sale:id,invoice_no',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_hold_voucher_id')
                        ->where(function ($s) {
                            $s->whereIn('status', ['Posted', 'posted', '0', 0])
                              ->orWhereNotNull('meta->claim_id')
                              ->orWhereNotNull('sale_id');
                        });
                });
            })
            ->chunkById(300, function ($items) use (&$buckets, $fromDate, $toDate) {
                foreach ($items as $hold) {
                    $voucher = $hold->voucher;
                    if (!$hold->product_id) {
                        continue;
                    }

                    $qty = $hold->grossHoldQty();
                    if ($qty <= 0) {
                        continue;
                    }

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromHold($hold);

                    [$refType, $refNo] = $this->resolveDetailedRefCode($hold);
                    $dateStr = $this->pickDate($voucher ?: $hold, ['entry_date', 'date']);

                    $this->applyQtyToBucket($buckets, [
                        'party' => [$partyType, $partyId, $partyName],
                        'product_id' => (int) $hold->product_id,
                        'product_name' => $hold->product->name ?? ('Item #' . $hold->product_id),
                        'warehouse_id' => (int) ($hold->warehouse_id ?? $voucher?->warehouse_id ?? 0),
                        'date' => $dateStr,
                        'source' => $this->resolveHoldSource($hold),
                        'entry_detail' => [
                            'date' => Carbon::parse($dateStr)->format('d-m-Y'),
                            'ref_type' => $refType,
                            'ref_no' => (string) $refNo,
                            'kind' => 'hold',
                            'qty' => $qty,
                        ],
                    ], $qty, $fromDate, $toDate, 'hold');
                }
            });
    }

    private function collectReleaseMovements(array &$buckets, ?string $fromDate, ?string $toDate): void
    {
        StockRelease::withoutGlobalScopes()
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'hold.voucher.partyVendor:id,name',
                'hold.voucher.partyCustomer:id,customer_name',
                'hold.partyVendor:id,name',
                'hold.partyCustomer:id,customer_name',
                'product:id,name',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_release_voucher_id')
                        ->whereIn('status', ['Posted', 'posted']);
                });
            })
            ->chunkById(300, function ($items) use (&$buckets, $fromDate, $toDate) {
                foreach ($items as $release) {
                    $qty = (float) $release->release_qty;
                    if ($qty <= 0 || !$release->product_id) {
                        continue;
                    }

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromRelease($release);
                    $voucher = $release->voucher;
                    [$refType, $refNo] = $this->resolveReleaseRefCode($release);
                    $dateStr = $this->pickDate($voucher ?: $release, ['date', 'entry_date']);

                    $this->applyQtyToBucket($buckets, [
                        'party' => [$partyType, $partyId, $partyName],
                        'product_id' => (int) $release->product_id,
                        'product_name' => $release->product->name ?? ('Item #' . $release->product_id),
                        'warehouse_id' => (int) ($release->warehouse_id ?? $voucher?->warehouse_id ?? $release->hold?->warehouse_id ?? 0),
                        'date' => $dateStr,
                        'entry_detail' => [
                            'date' => Carbon::parse($dateStr)->format('d-m-Y'),
                            'ref_type' => $refType,
                            'ref_no' => (string) $refNo,
                            'kind' => 'release',
                            'qty' => $qty,
                        ],
                    ], $qty, $fromDate, $toDate, 'rel');
                }
            });
    }

    private function selectedCustomerIds(): array
    {
        if (!$this->shouldApplyFilter($this->filters['parties'], $this->filters['totalParties'])) {
            return [];
        }

        return collect($this->filters['parties'])
            ->filter(fn ($token) => str_starts_with(strtolower((string) $token), 'customer:'))
            ->map(fn ($token) => (int) substr((string) $token, strrpos((string) $token, ':') + 1))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function productIdsForCustomer(int $customerId): array
    {
        if ($this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts'])) {
            return array_map('intval', $this->filters['items']);
        }

        return Product::query()
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function seedCustomerItemBuckets(array &$buckets): void
    {
        // Only seed empty 0 rows if NO specific items are filtered (or all items selected)
        if ($this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts'])) {
            return;
        }

        $customerIds = $this->selectedCustomerIds();
        if (empty($customerIds)) {
            return;
        }

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->get(['id', 'customer_name'])
            ->keyBy('id');

        foreach ($customerIds as $customerId) {
            $partyKey = 'customer:' . $customerId;
            $partyName = strtoupper($customers->get($customerId)?->customer_name ?? ('CUSTOMER #' . $customerId));
            $productIds = $this->productIdsForCustomer($customerId);

            if (empty($productIds)) {
                continue;
            }

            $products = $this->loadProductsByIds($productIds);

            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                if (!$product || !$this->productMatches($productId)) {
                    continue;
                }

                $key = $partyKey . '|' . $productId;
                if (isset($buckets[$key])) {
                    continue;
                }

                $buckets[$key] = [
                    'party_key' => $partyKey,
                    'party_name' => $partyName,
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'opening' => 0.0,
                    'hold' => 0.0,
                    'rel' => 0.0,
                ];
            }
        }
    }

    private function shouldIncludeRow(array $row, float $payable): bool
    {
        if (!$this->productMatches((int) $row['product_id'])) {
            return false;
        }

        return !$this->isZeroRow($row['opening'], $row['hold'], $row['rel'], $payable);
    }

    private function appendPartyRow(array &$partyGroups, array $row, float $payable): void
    {
        $partyKey = $row['party_key'];
        if (!isset($partyGroups[$partyKey])) {
            $partyGroups[$partyKey] = [
                'party_name' => $row['party_name'],
                'rows' => [],
                'totals' => ['opening' => 0.0, 'hold' => 0.0, 'rel' => 0.0, 'payable' => 0.0],
            ];
        }

        $partyGroups[$partyKey]['rows'][] = [
            'product_name' => $row['product_name'],
            'opening' => $row['opening'],
            'hold' => $row['hold'],
            'rel' => $row['rel'],
            'payable' => $payable,
            'sources' => $row['sources'] ?? [],
            'item_details' => $row['item_details'] ?? [],
        ];

        $partyGroups[$partyKey]['totals']['opening'] += $row['opening'];
        $partyGroups[$partyKey]['totals']['hold'] += $row['hold'];
        $partyGroups[$partyKey]['totals']['rel'] += $row['rel'];
        $partyGroups[$partyKey]['totals']['payable'] += $payable;
    }

    private function finalizePartyGroups(array $partyGroups): array
    {
        return collect($partyGroups)
            ->map(function ($group) {
                usort($group['rows'], fn ($a, $b) => strnatcasecmp($a['product_name'], $b['product_name']));

                return $group;
            })
            ->sortBy('party_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
    private function compilePartyGroups(array $buckets): array
    {
        $partyGroups = [];

        foreach ($buckets as $row) {
            $payable = $row['opening'] + $row['hold'] - $row['rel'];
            if (!$this->shouldIncludeRow($row, $payable)) {
                continue;
            }

            $this->appendPartyRow($partyGroups, $row, $payable);
        }

        return $this->finalizePartyGroups($partyGroups);
    }

    private function compileCustomerItemGroups(array $buckets): array
    {
        $customerBuckets = array_filter(
            $buckets,
            fn ($row) => str_starts_with($row['party_key'], 'customer:')
        );

        if (empty($customerBuckets)) {
            return [];
        }

        $partyGroups = [];

        foreach ($customerBuckets as $row) {
            $payable = $row['opening'] + $row['hold'] - $row['rel'];
            if (!$this->shouldIncludeRow($row, $payable)) {
                continue;
            }

            $this->appendPartyRow($partyGroups, $row, $payable);
        }

        return $this->finalizePartyGroups($partyGroups);
    }

    private function loadProductsByIds($productIds)
    {
        $ids = collect($productIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'opening_total_stock', 'opening_shop_stock', 'opening_warehouse_stocks', 'stock'])
            ->keyBy('id');
    }

    private function isZeroRow(float $opening, float $hold, float $rel, float $payable): bool
    {
        foreach ([$opening, $hold, $rel, $payable] as $val) {
            if (abs($val) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    private function sumTotals(array $totalsList): array
    {
        $grand = ['opening' => 0.0, 'hold' => 0.0, 'rel' => 0.0, 'payable' => 0.0];
        foreach ($totalsList as $totals) {
            foreach ($grand as $key => $_) {
                $grand[$key] += (float) ($totals[$key] ?? 0);
            }
        }

        return $grand;
    }

    private function resolveDetailedRefCode(StockHold $hold): array
    {
        if (!empty(data_get($hold->meta, 'claim_no')) || !empty(data_get($hold->meta, 'claim_id')) || str_contains((string) $hold->remarks, 'Customer Claim Hold')) {
            $claimNo = data_get($hold->meta, 'claim_no') ?? ltrim((string) $hold->meta['claim_id'] ?? '', '0');
            return ['CH', $claimNo ?: ($hold->id)];
        }
        if ($hold->sale || $hold->sale_id) {
            $inv = $hold->sale?->invoice_no ?? $hold->sale_id;
            return ['SJ', $inv];
        }
        if ($hold->voucher) {
            return ['SH', $hold->voucher->voucher_no];
        }

        return ['HLD', $hold->id];
    }

    private function resolveReleaseRefCode(StockRelease $release): array
    {
        $vouc = $release->voucher;
        if ($vouc) {
            return ['REL', $vouc->voucher_no];
        }
        if (!empty(data_get($release->meta, 'claim_no'))) {
            return ['CR', data_get($release->meta, 'claim_no')];
        }

        return ['REL', $release->id];
    }

    private function buildDetailedStatement(?string $fromDate, ?string $toDate): array
    {
        $movements = collect();

        // Collect Holds
        StockHold::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'partyVendor:id,name',
                'partyCustomer:id,customer_name',
                'product:id,name',
                'sale:id,invoice_no',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_hold_voucher_id')
                        ->where(function ($s) {
                            $s->whereIn('status', ['Posted', 'posted', '0', 0])
                              ->orWhereNotNull('meta->claim_id')
                              ->orWhereNotNull('sale_id');
                        });
                });
            })
            ->chunkById(300, function ($items) use (&$movements) {
                foreach ($items as $hold) {
                    if (!$hold->product_id) continue;
                    $qty = $hold->grossHoldQty();
                    if ($qty <= 0) continue;

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromHold($hold);
                    if (!$this->partyMatches($partyType, $partyId) || !$this->productMatches((int) $hold->product_id) || !$this->warehouseMatches((int) ($hold->warehouse_id ?? $hold->voucher?->warehouse_id ?? 0))) {
                        continue;
                    }

                    [$refType, $refNo] = $this->resolveDetailedRefCode($hold);
                    $voucher = $hold->voucher;
                    $dateStr = $this->pickDate($voucher ?: $hold, ['entry_date', 'date']);

                    $movements->push([
                        'party_key' => $partyType . ':' . $partyId,
                        'party_name' => $partyName,
                        'product_id' => (int) $hold->product_id,
                        'product_name' => $hold->product->name ?? ('Item #' . $hold->product_id),
                        'date' => $dateStr,
                        'type' => 'hold',
                        'ref_type' => $refType,
                        'ref_no' => (string) $refNo,
                        'qty' => $qty,
                    ]);
                }
            });

        // Collect Releases
        StockRelease::withoutGlobalScopes()
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'hold.voucher.partyVendor:id,name',
                'hold.voucher.partyCustomer:id,customer_name',
                'hold.partyVendor:id,name',
                'hold.partyCustomer:id,customer_name',
                'product:id,name',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_release_voucher_id')
                        ->whereIn('status', ['Posted', 'posted']);
                });
            })
            ->chunkById(300, function ($items) use (&$movements) {
                foreach ($items as $release) {
                    $qty = (float) $release->release_qty;
                    if ($qty <= 0 || !$release->product_id) continue;

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromRelease($release);
                    if (!$this->partyMatches($partyType, $partyId) || !$this->productMatches((int) $release->product_id) || !$this->warehouseMatches((int) ($release->warehouse_id ?? $release->voucher?->warehouse_id ?? $release->hold?->warehouse_id ?? 0))) {
                        continue;
                    }

                    [$refType, $refNo] = $this->resolveReleaseRefCode($release);
                    $voucher = $release->voucher;
                    $dateStr = $this->pickDate($voucher ?: $release, ['date', 'entry_date']);

                    $movements->push([
                        'party_key' => $partyType . ':' . $partyId,
                        'party_name' => $partyName,
                        'product_id' => (int) $release->product_id,
                        'product_name' => $release->product->name ?? ('Item #' . $release->product_id),
                        'date' => $dateStr,
                        'type' => 'release',
                        'ref_type' => $refType,
                        'ref_no' => (string) $refNo,
                        'qty' => $qty,
                    ]);
                }
            });

        // Group by Party -> Product
        $groupedParties = $movements->groupBy('party_key');

        $resultParties = [];
        $grandTotalHold = 0.0;
        $grandTotalRel = 0.0;
        $grandTotalBal = 0.0;

        foreach ($groupedParties as $partyKey => $pMovements) {
            $partyName = $pMovements->first()['party_name'];
            $groupedProducts = $pMovements->groupBy('product_id');

            $partyProducts = [];
            $partySubTotalHold = 0.0;
            $partySubTotalRel = 0.0;

            foreach ($groupedProducts as $productId => $prodMovements) {
                $productName = $prodMovements->first()['product_name'];

                // Separate prior movements (opening) vs in-period entries
                $priorMovements = $prodMovements->filter(fn ($m) => $fromDate && $m['date'] < $fromDate);
                $periodMovements = $prodMovements->filter(fn ($m) => $this->dateInPeriod($m['date'], $fromDate, $toDate))
                    ->sortBy(fn ($m) => $m['date'] . '-' . ($m['type'] === 'hold' ? '1' : '2'));

                $openingQty = 0.0;
                foreach ($priorMovements as $pm) {
                    $openingQty += ($pm['type'] === 'hold' ? $pm['qty'] : -$pm['qty']);
                }

                if ($periodMovements->isEmpty() && abs($openingQty) < 0.0001) {
                    continue;
                }

                $entries = [];
                $runningBalance = $openingQty;
                $prodHoldSum = 0.0;
                $prodRelSum = 0.0;

                foreach ($periodMovements as $pm) {
                    $holdQty = $pm['type'] === 'hold' ? $pm['qty'] : 0.0;
                    $relQty = $pm['type'] === 'release' ? $pm['qty'] : 0.0;

                    $prodHoldSum += $holdQty;
                    $prodRelSum += $relQty;
                    $runningBalance += ($holdQty - $relQty);

                    $entries[] = [
                        'date' => Carbon::parse($pm['date'])->format('d-m-Y'),
                        'ref_type' => $pm['ref_type'],
                        'ref_no' => $pm['ref_no'],
                        'hold' => $holdQty,
                        'release' => $relQty,
                        'balance' => $runningBalance,
                    ];
                }

                $partySubTotalHold += $prodHoldSum;
                $partySubTotalRel += $prodRelSum;

                $partyProducts[] = [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'opening' => $openingQty,
                    'entries' => $entries,
                    'sub_hold' => $prodHoldSum,
                    'sub_release' => $prodRelSum,
                    'final_balance' => $runningBalance,
                ];
            }

            if (!empty($partyProducts)) {
                $grandTotalHold += $partySubTotalHold;
                $grandTotalRel += $partySubTotalRel;
                $grandTotalBal += collect($partyProducts)->sum('final_balance');

                $resultParties[] = [
                    'party_name' => $partyName,
                    'products' => $partyProducts,
                    'sub_hold' => $partySubTotalHold,
                    'sub_release' => $partySubTotalRel,
                ];
            }
        }

        return [
            'parties' => $resultParties,
            'grand' => [
                'hold' => $grandTotalHold,
                'release' => $grandTotalRel,
                'balance' => $grandTotalBal,
            ],
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_type' => 'detailed',
            'generated_at' => now(),
        ];
    }

    private function resolveHoldTypeLabel(StockHold $hold): string
    {
        if (!empty(data_get($hold->meta, 'claim_no')) || !empty(data_get($hold->meta, 'claim_id')) || str_contains((string) $hold->remarks, 'Customer Claim Hold')) {
            return 'Claim';
        }
        if ($hold->sale || $hold->sale_id) {
            return 'Sale';
        }

        return 'Hold';
    }

    private function buildHoldOnlyReport(?string $fromDate, ?string $toDate): array
    {
        $rawHolds = collect();

        StockHold::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'partyVendor:id,name',
                'partyCustomer:id,customer_name',
                'product:id,name',
                'sale:id,invoice_no',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_hold_voucher_id')
                        ->where(function ($s) {
                            $s->whereIn('status', ['Posted', 'posted', '0', 0])
                              ->orWhereNotNull('meta->claim_id')
                              ->orWhereNotNull('sale_id');
                        });
                });
            })
            ->chunkById(300, function ($items) use (&$rawHolds, $fromDate, $toDate) {
                foreach ($items as $hold) {
                    if (!$hold->product_id) continue;
                    $qty = $hold->grossHoldQty();
                    if ($qty <= 0) continue;

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromHold($hold);
                    if (!$this->partyMatches($partyType, $partyId) || !$this->productMatches((int) $hold->product_id) || !$this->warehouseMatches((int) ($hold->warehouse_id ?? $hold->voucher?->warehouse_id ?? 0))) {
                        continue;
                    }

                    $voucher = $hold->voucher;
                    $dateStr = $this->pickDate($voucher ?: $hold, ['entry_date', 'date']);
                    if (!$this->dateInPeriod($dateStr, $fromDate, $toDate)) {
                        continue;
                    }

                    [$refType, $refNo] = $this->resolveDetailedRefCode($hold);
                    $typeLabel = $this->resolveHoldTypeLabel($hold);
                    $invoiceNo = $hold->sale?->invoice_no ?? ($hold->voucher?->voucher_no ?? 0);

                    $rawHolds->push([
                        'party_key' => $partyType . ':' . $partyId,
                        'party_name' => $partyName,
                        'hold_id' => $refNo,
                        'hold_date' => Carbon::parse($dateStr)->format('d-m-y'),
                        'invoice_no' => $invoiceNo ?: 0,
                        'type' => $typeLabel,
                        'product_name' => $hold->product->name ?? ('Item #' . $hold->product_id),
                        'qty' => $qty,
                    ]);
                }
            });

        $groupedParties = $rawHolds->groupBy('party_key');
        $parties = [];
        $grandQty = 0;
        $serialCounter = 1;

        foreach ($groupedParties as $partyKey => $rows) {
            $partyName = $rows->first()['party_name'];
            $partyRows = [];

            foreach ($rows as $r) {
                $r['sno'] = $serialCounter++;
                $partyRows[] = $r;
                $grandQty += (int) $r['qty'];
            }

            $parties[] = [
                'party_name' => $partyName,
                'rows' => $partyRows,
            ];
        }

        return [
            'parties' => $parties,
            'grand_qty' => $grandQty,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_type' => 'hold_only',
            'generated_at' => now(),
        ];
    }

    private function buildReleaseOnlyReport(?string $fromDate, ?string $toDate): array
    {
        $rawReleases = collect();

        StockRelease::withoutGlobalScopes()
            ->with([
                'voucher.partyVendor:id,name',
                'voucher.partyCustomer:id,customer_name',
                'hold.voucher.partyVendor:id,name',
                'hold.voucher.partyCustomer:id,customer_name',
                'hold.partyVendor:id,name',
                'hold.partyCustomer:id,customer_name',
                'product:id,name',
            ])
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                    $this->applyUserGroupFilter($v);
                })->orWhere(function ($sub) {
                    $sub->whereNull('stock_release_voucher_id')
                        ->whereIn('status', ['Posted', 'posted']);
                });
            })
            ->chunkById(300, function ($items) use (&$rawReleases, $fromDate, $toDate) {
                foreach ($items as $release) {
                    $qty = (float) $release->release_qty;
                    if ($qty <= 0 || !$release->product_id) continue;

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromRelease($release);
                    if (!$this->partyMatches($partyType, $partyId) || !$this->productMatches((int) $release->product_id) || !$this->warehouseMatches((int) ($release->warehouse_id ?? $release->voucher?->warehouse_id ?? $release->hold?->warehouse_id ?? 0))) {
                        continue;
                    }

                    $voucher = $release->voucher;
                    $dateStr = $this->pickDate($voucher ?: $release, ['date', 'entry_date']);
                    if (!$this->dateInPeriod($dateStr, $fromDate, $toDate)) {
                        continue;
                    }

                    [$refType, $refNo] = $this->resolveReleaseRefCode($release);
                    $holdId = $release->hold?->voucher?->voucher_no ?? ($release->hold_id ?? 0);

                    $rawReleases->push([
                        'party_key' => $partyType . ':' . $partyId,
                        'party_name' => $partyName,
                        'release_id' => $refNo,
                        'release_date' => Carbon::parse($dateStr)->format('d-m-y'),
                        'hold_id' => $holdId ?: 0,
                        'product_name' => $release->product->name ?? ('Item #' . $release->product_id),
                        'qty' => $qty,
                    ]);
                }
            });

        $groupedParties = $rawReleases->groupBy('party_key');
        $parties = [];
        $grandQty = 0;
        $serialCounter = 1;

        foreach ($groupedParties as $partyKey => $rows) {
            $partyName = $rows->first()['party_name'];
            $partyRows = [];

            foreach ($rows as $r) {
                $r['sno'] = $serialCounter++;
                $partyRows[] = $r;
                $grandQty += (int) $r['qty'];
            }

            $parties[] = [
                'party_name' => $partyName,
                'rows' => $partyRows,
            ];
        }

        return [
            'parties' => $parties,
            'grand_qty' => $grandQty,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_type' => 'release_only',
            'generated_at' => now(),
        ];
    }

    private function buildHoldBalanceOnlyReport(?string $fromDate, ?string $toDate): array
    {
        $buckets = [];
        $this->collectHoldMovements($buckets, $fromDate, $toDate);
        $this->collectReleaseMovements($buckets, $fromDate, $toDate);

        $customerGroups = [];
        $grandPayable = 0.0;

        foreach ($buckets as $row) {
            $payable = $row['opening'] + $row['hold'] - $row['rel'];
            if (abs($payable) < 0.0001) {
                continue;
            }

            $partyKey = $row['party_key'];
            if (!isset($customerGroups[$partyKey])) {
                $customerGroups[$partyKey] = [
                    'party_name' => $row['party_name'],
                    'rows' => [],
                    'total_payable' => 0.0,
                ];
            }

            $customerGroups[$partyKey]['rows'][] = [
                'product_name' => $row['product_name'],
                'payable' => $payable,
            ];

            $customerGroups[$partyKey]['total_payable'] += $payable;
            $grandPayable += $payable;
        }

        $sortedGroups = collect($customerGroups)
            ->map(function ($group) {
                usort($group['rows'], fn ($a, $b) => strnatcasecmp($a['product_name'], $b['product_name']));
                return $group;
            })
            ->sortBy('party_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'groups' => $sortedGroups,
            'grand_payable' => $grandPayable,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_type' => 'hold_balance_only',
            'generated_at' => now(),
        ];
    }
}
