<?php

namespace App\Services;

use App\Models\ClaimAcceptanceItem;
use App\Models\ClaimCreditNoteItem;
use App\Models\ClaimItemReceiptItem;
use App\Models\CustomerClaim;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockHold;
use App\Models\StockRelease;
use App\Models\StockTransferProduct;
use App\Models\StockWastageDetail;
use App\Models\UserGroup;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockReportBuilder
{
    private array $filters = [];

    private Collection $movements;

    /** @var array<string, array<string, float>> */
    private array $ledger = [];

    private const COLS = [
        'pur', 'pur_ret', 'sales', 'sales_ret',
        'claim_in', 'claim_out', 'trf_in', 'trf_out',
        'waste', 'hold', 'release',
    ];

    public function build(Request $request): array
    {
        $this->filters = $this->extractFilters($request);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $this->movements = collect();

        $this->collectPurchases();
        $this->collectPurchaseReturns();
        $this->collectSales();
        $this->collectSaleReturns();
        $this->collectCustomerClaims();
        $this->collectClaimAcceptances();
        $this->collectClaimItemReceipts();
        $this->collectClaimCreditNotes();
        $this->collectTransfers();
        $this->collectWastage();
        $this->collectHolds();
        $this->collectReleases();
        $this->collectAdjustments();

        $this->aggregateLedger();

        $productIds = $this->filteredProductIds();
        $warehouseIds = $this->resolvedWarehouseIds();

        $rows = [];
        foreach ($warehouseIds as $warehouseId) {
            foreach ($productIds as $productId) {
                $row = $this->buildRow((int) $productId, (int) $warehouseId, $fromDate, $toDate);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        $grouped = collect($rows)->groupBy('warehouse_id')->sortKeys();

        return [
            'grouped' => $grouped,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_label' => $fromDate
                ? Carbon::parse($fromDate)->subDay()->format('d-m-y')
                : '',
            'closing_label' => $toDate
                ? Carbon::parse($toDate)->format('d-m-y')
                : '',
            'grand' => $this->sumRows($rows),
        ];
    }

    private function extractFilters(Request $request): array
    {
        return [
            'user_groups' => $request->user_group ?? [],
            'warehouses' => $request->warehouse ?? [],
            'categories' => $request->category ?? [],
            'brands' => $request->brand ?? [],
            'items' => $request->item ?? [],
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'totalGroups' => UserGroup::count(),
            'totalWarehouses' => Warehouse::withoutGlobalScopes()->count() + 1,
            'totalCategories' => \App\Models\Category::count(),
            'totalBrands' => \App\Models\Brand::count(),
            'totalProducts' => Product::count(),
        ];
    }

    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function key(int $productId, int $warehouseId): string
    {
        return $productId . '|' . $warehouseId;
    }

    private function addMovement(int $productId, int $warehouseId, string $date, string $column, float $qty, float $balanceEffect): void
    {
        if ($qty == 0.0 && $balanceEffect == 0.0) {
            return;
        }

        if (!$this->productMatchesFilters($productId)) {
            return;
        }

        if (!$this->warehouseMatchesFilter($warehouseId)) {
            return;
        }

        $this->movements->push([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'date' => $date,
            'column' => $column,
            'qty' => abs($qty),
            'balance_effect' => $balanceEffect,
        ]);
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

    private function productMatchesFilters(int $productId): bool
    {
        static $cache = null;
        if ($cache === null) {
            $cache = $this->filteredProductIds()->flip();
        }

        return isset($cache[$productId]);
    }

    private function filteredProductIds(): Collection
    {
        $query = Product::query()->orderBy('name');

        if ($this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts'])) {
            $query->whereIn('id', $this->filters['items']);
        }
        if ($this->shouldApplyFilter($this->filters['brands'], $this->filters['totalBrands'])) {
            $query->whereIn('brand_id', $this->filters['brands']);
        }
        if ($this->shouldApplyFilter($this->filters['categories'], $this->filters['totalCategories'])) {
            $query->whereIn('category_id', $this->filters['categories']);
        }

        return $query->pluck('id');
    }

    private function warehouseMatchesFilter(int $warehouseId): bool
    {
        if (!$this->shouldApplyFilter($this->filters['warehouses'], $this->filters['totalWarehouses'])) {
            return true;
        }

        $selected = array_map('strval', $this->filters['warehouses']);

        return in_array((string) $warehouseId, $selected, true);
    }

    private function resolvedWarehouseIds(): array
    {
        if ($this->shouldApplyFilter($this->filters['warehouses'], $this->filters['totalWarehouses'])) {
            return array_map('intval', $this->filters['warehouses']);
        }

        $ids = Warehouse::withoutGlobalScopes()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_merge([0], $ids);
    }

    private function warehouseLabel(int $warehouseId): string
    {
        if ($warehouseId === 0) {
            return 'Shop';
        }

        static $names = null;
        if ($names === null) {
            $names = Warehouse::withoutGlobalScopes()->pluck('warehouse_name', 'id')->all();
        }

        return $names[$warehouseId] ?? ('WH #' . $warehouseId);
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

    private function collectPurchases(): void
    {
        PurchaseItem::with(['purchase' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('purchase', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $purchase = $item->purchase;
                    if (!$purchase) {
                        continue;
                    }
                    $wh = (int) ($purchase->warehouse_id ?? 0);
                    $date = $this->pickDate($purchase, ['current_date', 'entry_date']);
                    $qty = (float) $item->qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'pur', $qty, $qty);
                }
            });
    }

    private function collectPurchaseReturns(): void
    {
        PurchaseReturnItem::with(['purchaseReturn' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('purchaseReturn', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $ret = $item->purchaseReturn;
                    if (!$ret) {
                        continue;
                    }
                    $wh = (int) ($ret->warehouse_id ?? 0);
                    $date = $this->pickDate($ret, ['current_date', 'entry_date']);
                    $qty = (float) $item->qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'pur_ret', $qty, -$qty);
                }
            });
    }

    private function collectSales(): void
    {
        SaleItem::with(['sale' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('sale', function ($q) {
                $q->withoutGlobalScopes()->where('is_sale_order', 0);
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $sale = $item->sale;
                    if (!$sale) {
                        continue;
                    }
                    $wh = (int) ($item->warehouse_id ?? 0);
                    $date = $this->pickDate($sale, ['entry_date']);
                    $qty = (float) $item->sales_qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'sales', $qty, -$qty);
                }
            });
    }

    private function collectSaleReturns(): void
    {
        SaleReturnItem::with(['saleReturn' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('saleReturn', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $ret = $item->saleReturn;
                    if (!$ret) {
                        continue;
                    }
                    $wh = (int) ($item->warehouse_id ?? 0);
                    $date = $this->pickDate($ret, ['current_date', 'entry_date']);
                    $qty = (float) $item->sales_qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'sales_ret', $qty, $qty);
                }
            });
    }

    private function collectCustomerClaims(): void
    {
        CustomerClaim::withoutGlobalScopes()
            ->where('status', 'Posted')
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(200, function ($claims) {
                foreach ($claims as $claim) {
                    $date = $this->pickDate($claim, ['claim_date', 'entry_date']);

                    $this->addMovement(
                        (int) $claim->product_id,
                        (int) $claim->claim_warehouse_id,
                        $date,
                        'claim_in',
                        1,
                        1
                    );

                    if ($claim->claim_type === 'item_return' && $claim->original_warehouse_id) {
                        $this->addMovement(
                            (int) $claim->product_id,
                            (int) $claim->original_warehouse_id,
                            $date,
                            'claim_out',
                            1,
                            -1
                        );
                    }

                    if ($claim->claim_type === 'credit_note' && $claim->replacement_from_warehouse_id && $claim->replacement_product_id) {
                        $this->addMovement(
                            (int) $claim->replacement_product_id,
                            (int) $claim->replacement_from_warehouse_id,
                            $date,
                            'claim_out',
                            1,
                            -1
                        );
                    }

                    if ($claim->claim_type === 'claim_hold') {
                        $this->addMovement(
                            (int) $claim->product_id,
                            (int) ($claim->original_warehouse_id ?? $claim->claim_warehouse_id),
                            $date,
                            'hold',
                            1,
                            0
                        );
                    }
                }
            });
    }

    private function collectClaimAcceptances(): void
    {
        ClaimAcceptanceItem::with(['voucher' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $v = $item->voucher;
                    if (!$v) {
                        continue;
                    }
                    $date = $this->pickDate($v, ['date', 'entry_date']);
                    $qty = (float) $item->quantity;
                    $pid = (int) $item->product_id;
                    $this->addMovement($pid, (int) $v->from_warehouse_id, $date, 'claim_out', $qty, -$qty);
                    $this->addMovement($pid, (int) $v->to_warehouse_id, $date, 'claim_in', $qty, $qty);
                }
            });
    }

    private function collectClaimItemReceipts(): void
    {
        ClaimItemReceiptItem::with(['receipt' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('receipt', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $v = $item->receipt;
                    if (!$v) {
                        continue;
                    }
                    $date = $this->pickDate($v, ['date', 'entry_date']);
                    $qty = (float) $item->quantity;
                    $pid = (int) $item->product_id;
                    $this->addMovement($pid, (int) $v->from_warehouse_id, $date, 'claim_out', $qty, -$qty);
                    $this->addMovement($pid, (int) $v->to_warehouse_id, $date, 'claim_in', $qty, $qty);
                }
            });
    }

    private function collectClaimCreditNotes(): void
    {
        ClaimCreditNoteItem::with(['creditNote' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('creditNote', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $note = $item->creditNote;
                    if (!$note) {
                        continue;
                    }
                    $date = $this->pickDate($note, ['date', 'entry_date']);
                    $qty = (float) $item->quantity;
                    $pid = (int) $item->product_id;
                    $this->addMovement($pid, (int) $note->from_warehouse_id, $date, 'claim_out', $qty, -$qty);
                    $this->addMovement($pid, (int) $note->to_warehouse_id, $date, 'claim_in', $qty, $qty);
                }
            });
    }

    private function collectTransfers(): void
    {
        StockTransferProduct::with(['transfer' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('transfer', function ($q) {
                $q->withoutGlobalScopes()->whereIn('status', ['Posted', 'accepted']);
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $tr = $item->transfer;
                    if (!$tr) {
                        continue;
                    }
                    $date = $this->pickDate($tr, ['entry_date', 'date']);
                    $qty = (float) $item->quantity;
                    $pid = (int) $item->product_id;
                    $fromWh = $tr->from_shop ? 0 : (int) $tr->from_warehouse_id;
                    $toWh = $tr->to_shop ? 0 : (int) $tr->to_warehouse_id;
                    $this->addMovement($pid, $fromWh, $date, 'trf_out', $qty, -$qty);
                    $this->addMovement($pid, $toWh, $date, 'trf_in', $qty, $qty);
                }
            });
    }

    private function collectWastage(): void
    {
        StockWastageDetail::with(['wastage' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereHas('wastage', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
                $this->applyUserGroupFilter($q);
            })
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $w = $item->wastage;
                    if (!$w) {
                        continue;
                    }
                    $wh = (int) ($w->warehouse_id ?? 0);
                    $date = $this->pickDate($w, ['date', 'entry_date']);
                    $qty = (float) $item->qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'waste', $qty, -$qty);
                }
            });
    }

    private function collectHolds(): void
    {
        StockHold::withoutGlobalScopes()
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
            })
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $date = $this->pickDate($item, ['entry_date']);
                    $qty = (float) $item->hold_qty;
                    $wh = (int) ($item->warehouse_id ?? 0);
                    $this->addMovement((int) $item->product_id, $wh, $date, 'hold', $qty, 0);
                }
            });
    }

    private function collectReleases(): void
    {
        StockRelease::withoutGlobalScopes()
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
            })
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $voucher = $item->voucher;
                    $date = $this->pickDate($voucher ?? $item, ['date', 'entry_date']);
                    $qty = (float) $item->release_qty;
                    $wh = (int) ($voucher->warehouse_id ?? $item->warehouse_id ?? 0);
                    $this->addMovement((int) $item->product_id, $wh, $date, 'release', $qty, -$qty);
                }
            });
    }

    private function collectAdjustments(): void
    {
        StockAdjustmentItem::with(['adjustment'])
            ->whereHas('adjustment', fn ($q) => $q->where('status', 'Posted'))
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $adj = $item->adjustment;
                    if (!$adj) {
                        continue;
                    }
                    $wh = (int) ($adj->warehouse_id ?? 0);
                    $date = $this->pickDate($adj, ['date', 'entry_date']);
                    $qty = (float) $item->qty;
                    $this->addMovement((int) $item->product_id, $wh, $date, 'adj', $qty, $qty);
                }
            });
    }

    private function aggregateLedger(): void
    {
        foreach ($this->movements as $move) {
            $k = $this->key($move['product_id'], $move['warehouse_id']);
            if (!isset($this->ledger[$k])) {
                $this->ledger[$k] = [
                    'product_id' => $move['product_id'],
                    'warehouse_id' => $move['warehouse_id'],
                    'by_date' => [],
                ];
            }

            $date = $move['date'];
            if (!isset($this->ledger[$k]['by_date'][$date])) {
                $this->ledger[$k]['by_date'][$date] = [
                    'balance_effect' => 0,
                    'cols' => array_fill_keys(self::COLS, 0.0),
                ];
            }

            $this->ledger[$k]['by_date'][$date]['balance_effect'] += $move['balance_effect'];
            if ($move['column'] !== 'adj' && isset($this->ledger[$k]['by_date'][$date]['cols'][$move['column']])) {
                $this->ledger[$k]['by_date'][$date]['cols'][$move['column']] += $move['qty'];
            }
        }
    }

    private function getCurrentStock(int $productId, int $warehouseId): float
    {
        if ($warehouseId === 0) {
            return (float) Product::where('id', $productId)->value('stock');
        }

        return (float) (WarehouseStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0);
    }

    private function buildRow(int $productId, int $warehouseId, ?string $fromDate, ?string $toDate): ?array
    {
        $k = $this->key($productId, $warehouseId);
        $current = $this->getCurrentStock($productId, $warehouseId);
        $byDate = $this->ledger[$k]['by_date'] ?? [];

        $effectAfterFrom = 0.0;
        $periodCols = array_fill_keys(self::COLS, 0.0);
        $periodBalance = 0.0;

        foreach ($byDate as $date => $data) {
            if ($fromDate && $date >= $fromDate) {
                $effectAfterFrom += $data['balance_effect'];
            }
            if ($this->dateInPeriod($date, $fromDate, $toDate)) {
                $periodBalance += $data['balance_effect'];
                foreach (self::COLS as $col) {
                    $periodCols[$col] += $data['cols'][$col] ?? 0;
                }
            }
        }

        $opening = $current - $effectAfterFrom;
        $closing = $opening + $periodBalance;

        if ($this->isZeroRow($opening, $closing, $periodCols)) {
            return null;
        }

        static $productNames = null;
        if ($productNames === null) {
            $productNames = Product::pluck('name', 'id')->all();
        }

        return array_merge([
            'product_id' => $productId,
            'product_name' => $productNames[$productId] ?? ('Item #' . $productId),
            'warehouse_id' => $warehouseId,
            'warehouse_label' => $this->warehouseLabel($warehouseId),
            'opening' => $opening,
            'closing' => $closing,
        ], $periodCols);
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

    private function isZeroRow(float $opening, float $closing, array $cols): bool
    {
        if (abs($opening) > 0.0001 || abs($closing) > 0.0001) {
            return false;
        }

        foreach ($cols as $val) {
            if (abs($val) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    private function sumRows(array $rows): array
    {
        $totals = array_fill_keys(array_merge(['opening', 'closing'], self::COLS), 0.0);
        foreach ($rows as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += $row[$key] ?? 0;
            }
        }

        return $totals;
    }
}
