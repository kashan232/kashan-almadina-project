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
            'report_type' => in_array($request->report_type, ['party', 'item'], true) ? $request->report_type : 'party',
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
            return;
        }

        if ($this->dateInPeriod($date, $fromDate, $toDate)) {
            $buckets[$key][$kind === 'hold' ? 'hold' : 'rel'] += $qty;
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
            ])
            ->withSum(StockHold::postedReleasesWithSum(), 'release_qty')
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(300, function ($items) use (&$buckets, $fromDate, $toDate) {
                foreach ($items as $hold) {
                    $voucher = $hold->voucher;
                    if (!$voucher || !$hold->product_id) {
                        continue;
                    }

                    // Report Hold column = original posted hold qty (e.g. 15), not net after release.
                    $qty = $hold->grossHoldQty();
                    if ($qty <= 0) {
                        continue;
                    }

                    [$partyType, $partyId, $partyName] = $this->resolvePartyFromHold($hold);

                    $this->applyQtyToBucket($buckets, [
                        'party' => [$partyType, $partyId, $partyName],
                        'product_id' => (int) $hold->product_id,
                        'product_name' => $hold->product->name ?? ('Item #' . $hold->product_id),
                        'warehouse_id' => (int) ($hold->warehouse_id ?? $voucher->warehouse_id ?? 0),
                        'date' => $this->pickDate($voucher, ['date', 'entry_date']),
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

                    $this->applyQtyToBucket($buckets, [
                        'party' => [$partyType, $partyId, $partyName],
                        'product_id' => (int) $release->product_id,
                        'product_name' => $release->product->name ?? ('Item #' . $release->product_id),
                        'warehouse_id' => (int) ($release->warehouse_id ?? $voucher?->warehouse_id ?? $release->hold?->warehouse_id ?? 0),
                        'date' => $this->pickDate($voucher ?: $release, ['date', 'entry_date']),
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
        if (!$this->isZeroRow($row['opening'], $row['hold'], $row['rel'], $payable)) {
            return true;
        }

        if (str_starts_with($row['party_key'], 'customer:')) {
            $customerId = (int) substr($row['party_key'], strlen('customer:'));
            if (in_array($customerId, $this->selectedCustomerIds(), true)) {
                return true;
            }
        }

        return false;
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
        $productIds = collect($buckets)->pluck('product_id')->unique()->filter()->values();
        $products = $this->loadProductsByIds($productIds);

        $partyGroups = [];

        foreach ($buckets as $row) {
            $product = $products->get($row['product_id']);
            $row['opening'] = $product ? $this->resolveProductOpening($product) : 0.0;

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

        $productIds = collect($customerBuckets)->pluck('product_id')->unique()->filter()->values();
        $products = $this->loadProductsByIds($productIds);

        $partyGroups = [];

        foreach ($customerBuckets as $row) {
            $product = $products->get($row['product_id']);
            $row['opening'] = $product ? $this->resolveProductOpening($product) : 0.0;

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
}
