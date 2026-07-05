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
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\StockReleaseVoucher;
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

    public function buildLedger(Request $request): array
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

        $ledgers = [];
        foreach ($this->filteredProductIds() as $productId) {
            foreach ($this->resolvedWarehouseIds() as $warehouseId) {
                $ledger = $this->compileLedger((int) $productId, (int) $warehouseId, $fromDate, $toDate);
                if ($ledger !== null) {
                    $ledgers[] = $ledger;
                }
            }
        }

        return [
            'ledgers' => $ledgers,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

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
            'subcategories' => $request->subcategory ?? [],
            'brands' => $request->brand ?? [],
            'items' => $request->item ?? [],
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'totalGroups' => UserGroup::count(),
            'totalWarehouses' => Warehouse::withoutGlobalScopes()->count() + 1,
            'totalCategories' => \App\Models\Category::count(),
            'totalSubcategories' => \App\Models\Subcategory::count(),
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

    private function addMovement(
        int $productId,
        int $warehouseId,
        string $date,
        string $column,
        float $qty,
        float $balanceEffect,
        string $refId = '',
        string $typeCode = '',
        string $partyName = '',
        float $price = 0,
        float $amount = 0,
        int $txnId = 0
    ): void {
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
            'ref_id' => $txnId > 0 ? (string) $txnId : $refId,
            'txn_id' => $txnId,
            'type_code' => $typeCode,
            'party_name' => $partyName,
            'price' => $price,
            'amount' => $amount,
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
        if ($this->shouldApplyFilter($this->filters['subcategories'], $this->filters['totalSubcategories'])) {
            $query->whereIn('sub_category_id', $this->filters['subcategories']);
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
        PurchaseItem::with(['purchase' => fn ($q) => $q->withoutGlobalScopes()->with(['vendor', 'purchasable'])])
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
                    $price = (float) ($item->price ?? 0);
                    $party = $purchase->vendor->name ?? $purchase->purchasable->name ?? '';
                    $this->addMovement(
                        (int) $item->product_id,
                        $wh,
                        $date,
                        'pur',
                        $qty,
                        $qty,
                        (string) ($purchase->invoice_no ?? ''),
                        'PI',
                        $party,
                        $price,
                        (float) ($item->form_line_total ?? ($price * $qty)),
                        (int) ($purchase->id ?? 0)
                    );
                }
            });
    }

    private function collectPurchaseReturns(): void
    {
        PurchaseReturnItem::with(['purchaseReturn' => fn ($q) => $q->withoutGlobalScopes()->with('purchasable')])
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
                    $price = (float) ($item->price ?? 0);
                    $party = $ret->purchasable->name ?? $ret->purchasable->customer_name ?? '';
                    $this->addMovement(
                        (int) $item->product_id,
                        $wh,
                        $date,
                        'pur_ret',
                        $qty,
                        -$qty,
                        (string) ($ret->invoice_no ?? ''),
                        'PR',
                        $party,
                        $price,
                        (float) ($item->line_total ?? ($price * $qty)),
                        (int) ($ret->id ?? 0)
                    );
                }
            });
    }

    private function collectSales(): void
    {
        SaleItem::with(['sale' => fn ($q) => $q->withoutGlobalScopes()->with('customer')])
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
                    $price = (float) ($item->sales_rate ?: $item->sales_price ?: 0);
                    $party = $sale->customer->customer_name ?? 'WALK IN CUSTOMER';
                    $this->addMovement(
                        (int) $item->product_id,
                        $wh,
                        $date,
                        'sales',
                        $qty,
                        -$qty,
                        (string) ($sale->invoice_no ?? ''),
                        'SI',
                        $party,
                        $price,
                        (float) ($item->amount ?? ($price * $qty)),
                        (int) ($sale->id ?? 0)
                    );
                }
            });
    }

    private function collectSaleReturns(): void
    {
        SaleReturnItem::with(['saleReturn' => fn ($q) => $q->withoutGlobalScopes()->with('customer')])
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
                    $price = (float) ($item->sales_price ?? 0);
                    $party = $ret->customer->customer_name ?? 'WALK IN CUSTOMER';
                    $this->addMovement(
                        (int) $item->product_id,
                        $wh,
                        $date,
                        'sales_ret',
                        $qty,
                        $qty,
                        (string) ($ret->invoice_no ?? ''),
                        'SR',
                        $party,
                        $price,
                        (float) ($item->amount ?? ($price * $qty)),
                        (int) ($ret->id ?? 0)
                    );
                }
            });
    }

    private function collectCustomerClaims(): void
    {
        CustomerClaim::withoutGlobalScopes()
            ->with('party')
            ->where('status', 'Posted')
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(200, function ($claims) {
                foreach ($claims as $claim) {
                    $date = $this->pickDate($claim, ['claim_date', 'entry_date']);
                    $party = $claim->party_name;
                    $price = (float) ($claim->sales_price ?: $claim->replacement_sales_price ?: 0);
                    $ref = (string) ($claim->claim_no ?? '');
                    $claimId = (int) ($claim->id ?? 0);

                    $this->addMovement(
                        (int) $claim->product_id,
                        (int) $claim->claim_warehouse_id,
                        $date,
                        'claim_in',
                        1,
                        1,
                        $ref,
                        'CLM',
                        $party,
                        $price,
                        $price,
                        $claimId
                    );

                    if ($claim->claim_type === 'item_return' && $claim->original_warehouse_id) {
                        $this->addMovement(
                            (int) $claim->product_id,
                            (int) $claim->original_warehouse_id,
                            $date,
                            'claim_out',
                            1,
                            -1,
                            $ref,
                            'CLM',
                            $party,
                            $price,
                            $price,
                            $claimId
                        );
                    }

                    if ($claim->claim_type === 'credit_note' && $claim->replacement_from_warehouse_id && $claim->replacement_product_id) {
                        $this->addMovement(
                            (int) $claim->replacement_product_id,
                            (int) $claim->replacement_from_warehouse_id,
                            $date,
                            'claim_out',
                            1,
                            -1,
                            $ref,
                            'CLM',
                            $party,
                            (float) ($claim->replacement_sales_price ?? $price),
                            (float) ($claim->replacement_sales_price ?? $price),
                            $claimId
                        );
                    }

                    if ($claim->claim_type === 'claim_hold') {
                        $this->addMovement(
                            (int) $claim->product_id,
                            (int) ($claim->original_warehouse_id ?? $claim->claim_warehouse_id),
                            $date,
                            'hold',
                            1,
                            0,
                            $ref,
                            'HD',
                            $party,
                            $price,
                            $price,
                            $claimId
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
                    $ref = (string) ($v->voucher_no ?? $v->id ?? '');
                    $party = method_exists($v, 'partyName') ? $v->partyName() : '';
                    $this->addMovement($pid, (int) $v->from_warehouse_id, $date, 'claim_out', $qty, -$qty, $ref, 'CA', $party, 0, 0);
                    $this->addMovement($pid, (int) $v->to_warehouse_id, $date, 'claim_in', $qty, $qty, $ref, 'CA', $party, 0, 0);
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
                    $ref = (string) ($v->voucher_no ?? $v->id ?? '');
                    $party = method_exists($v, 'partyName') ? $v->partyName() : '';
                    $this->addMovement($pid, (int) $v->from_warehouse_id, $date, 'claim_out', $qty, -$qty, $ref, 'CIR', $party, 0, 0);
                    $this->addMovement($pid, (int) $v->to_warehouse_id, $date, 'claim_in', $qty, $qty, $ref, 'CIR', $party, 0, 0);
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
                    $ref = (string) ($note->voucher_no ?? $note->id ?? '');
                    $party = method_exists($note, 'partyName') ? $note->partyName() : '';
                    $price = (float) ($item->price ?? 0);
                    $this->addMovement($pid, (int) $note->from_warehouse_id, $date, 'claim_out', $qty, -$qty, $ref, 'CCN', $party, $price, $price * $qty);
                    $this->addMovement($pid, (int) $note->to_warehouse_id, $date, 'claim_in', $qty, $qty, $ref, 'CCN', $party, $price, $price * $qty);
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
                    $ref = (string) ($tr->transfer_no ?? $tr->id ?? '');
                    $price = (float) ($item->price ?? 0);
                    $this->addMovement($pid, $fromWh, $date, 'trf_out', $qty, -$qty, $ref, 'TR', 'Transfer Out', $price, $price * $qty);
                    $this->addMovement($pid, $toWh, $date, 'trf_in', $qty, $qty, $ref, 'TR', 'Transfer In', $price, $price * $qty);
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
                    $ref = (string) ($w->gwn_id ?? $w->id ?? '');
                    $this->addMovement((int) $item->product_id, $wh, $date, 'waste', $qty, -$qty, $ref, 'WS', 'Wastage', 0, 0);
                }
            });
    }

    private function resolveHoldWarehouseId(StockHold $item): int
    {
        $voucher = $item->voucher;

        return (int) ($item->warehouse_id ?? ($voucher->warehouse_id ?? 0));
    }

    /** Report queries bypass group scope but must ignore soft-deleted hold lines. */
    private function stockHoldReportQuery()
    {
        return StockHold::withoutGlobalScopes()->whereNull('deleted_at');
    }

    /** Live hold rows — same scope as /warehouse_stocks (group + soft-delete). */
    private function activeHoldQuery()
    {
        return StockHold::query();
    }

    /** Net reserved qty — same rules as /warehouse_stocks (posted voucher or overflow row). */
    private function currentHoldQty(int $productId, ?int $warehouseId = null): float
    {
        $query = $this->activeHoldQuery()
            ->where('product_id', $productId)
            ->where('hold_qty', '!=', 0)
            ->where(function ($q) {
                $q->whereNull('stock_hold_voucher_id')
                    ->orWhereHas('voucher', function ($v) {
                        $v->withoutGlobalScopes()->where('status', 'Posted');
                    });
            });

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->sum('hold_qty');
    }

    private function currentHoldQtyForWarehouses(int $productId, array $warehouseIds): float
    {
        $total = 0.0;
        foreach ($warehouseIds as $warehouseId) {
            $total += $this->currentHoldQty($productId, (int) $warehouseId);
        }

        return $total;
    }

    /** Same warehouse resolution as StockHoldController::resolveReleaseWarehouseId. */
    private function resolveReleaseWarehouseId(StockReleaseVoucher $voucher, StockRelease $item): int
    {
        if ($item->hold_id) {
            $hold = $item->relationLoaded('hold')
                ? $item->hold
                : StockHold::withoutGlobalScopes()->find($item->hold_id);
            if ($hold && $hold->warehouse_id !== null && $hold->warehouse_id !== '') {
                return (int) $hold->warehouse_id;
            }
        }

        if ($voucher->hold_voucher_id) {
            $holdVoucher = StockHoldVoucher::withoutGlobalScopes()->find($voucher->hold_voucher_id);
            if ($holdVoucher && $holdVoucher->warehouse_id !== null && $holdVoucher->warehouse_id !== '') {
                return (int) $holdVoucher->warehouse_id;
            }
        }

        return (int) ($item->warehouse_id ?: $voucher->warehouse_id);
    }

    private function collectHolds(): void
    {
        $this->stockHoldReportQuery()
            ->with('voucher')
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
            })
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $date = $this->pickDate($item, ['entry_date']);
                    $qty = (float) $item->hold_qty;
                    if ($qty <= 0) {
                        continue;
                    }
                    $wh = $this->resolveHoldWarehouseId($item);
                    $ref = (string) ($item->voucher->hold_id ?? $item->id ?? '');
                    // Hold adds physical stock (+qty), same as warehouse_stocks on post.
                    $this->addMovement((int) $item->product_id, $wh, $date, 'hold', $qty, $qty, $ref, 'SH', 'Stock Hold', 0, 0);
                }
            });
    }

    private function collectReleases(): void
    {
        StockRelease::withoutGlobalScopes()
            ->with(['voucher', 'hold'])
            ->whereHas('voucher', function ($q) {
                $q->withoutGlobalScopes()->where('status', 'Posted');
            })
            ->when(true, fn ($q) => $this->applyUserGroupFilter($q))
            ->chunkById(500, function ($items) {
                foreach ($items as $item) {
                    $voucher = $item->voucher;
                    if (!$voucher) {
                        continue;
                    }
                    $date = $this->pickDate($voucher, ['date', 'entry_date']);
                    $qty = (float) $item->release_qty;
                    if ($qty <= 0) {
                        continue;
                    }
                    $wh = $this->resolveReleaseWarehouseId($voucher, $item);
                    $ref = (string) ($voucher->release_id ?? $item->id ?? '');
                    // Release deducts physical stock (-qty), same as warehouse_stocks on post.
                    $this->addMovement((int) $item->product_id, $wh, $date, 'release', $qty, -$qty, $ref, 'SR', 'Stock Release', 0, 0);
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
                    $ref = (string) ($adj->adj_id ?? $adj->id ?? '');
                    $this->addMovement((int) $item->product_id, $wh, $date, 'adj', $qty, $qty, $ref, 'AD', 'Adjustment', 0, 0);
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

    private function compileLedger(int $productId, int $warehouseId, ?string $fromDate, ?string $toDate): ?array
    {
        $txns = $this->movements->filter(function ($m) use ($productId, $warehouseId) {
            return (int) $m['product_id'] === $productId && (int) $m['warehouse_id'] === $warehouseId;
        });

        $current = $this->getCurrentStock($productId, $warehouseId);
        $effectAfterFrom = $txns->filter(fn ($m) => $fromDate && $m['date'] >= $fromDate)->sum('balance_effect');
        $opening = $current - $effectAfterFrom;

        $periodTxns = $txns->filter(fn ($m) => $this->dateInPeriod($m['date'], $fromDate, $toDate))
            ->sortBy(fn ($m) => sprintf(
                '%s|%010d|%s',
                $m['date'],
                (int) ($m['txn_id'] ?? 0),
                $m['type_code'] ?? ''
            ));

        if ($periodTxns->isEmpty() && abs($opening) < 0.0001 && abs($current) < 0.0001) {
            return null;
        }

        static $productNames = null;
        if ($productNames === null) {
            $productNames = Product::pluck('name', 'id')->all();
        }

        $rows = [];
        $balance = $opening;
        $totals = array_merge(
            ['amount' => 0.0, 'opn_balance' => $opening],
            array_fill_keys(self::COLS, 0.0)
        );

        $rows[] = [
            'ref_id' => '0',
            'date' => $fromDate ? Carbon::parse($fromDate)->format('d-m-y') : '',
            'type_code' => 'B/F',
            'party_name' => '',
            'price' => null,
            'amount' => null,
            'opn_balance' => $opening,
            'cols' => array_fill_keys(self::COLS, 0.0),
            'balance' => $opening,
            'is_bf' => true,
        ];

        foreach ($periodTxns as $txn) {
            $balance += (float) $txn['balance_effect'];
            $cols = array_fill_keys(self::COLS, 0.0);
            $col = $txn['column'];
            if ($col !== 'adj' && isset($cols[$col])) {
                $cols[$col] = (float) $txn['qty'];
                $totals[$col] += (float) $txn['qty'];
            }

            $amount = (float) ($txn['amount'] ?? 0);
            $totals['amount'] += $amount;

            $rows[] = [
                'ref_id' => $txn['ref_id'] ?? '',
                'date' => Carbon::parse($txn['date'])->format('d-m-y'),
                'type_code' => $txn['type_code'] ?? strtoupper($col),
                'party_name' => $txn['party_name'] ?? '',
                'price' => (float) ($txn['price'] ?? 0),
                'amount' => $amount,
                'opn_balance' => null,
                'cols' => $cols,
                'balance' => $balance,
                'is_bf' => false,
            ];
        }

        $holdQty = $this->currentHoldQty($productId, $warehouseId);
        $totals['hold'] = $holdQty;

        return [
            'product_id' => $productId,
            'product_name' => $productNames[$productId] ?? ('Item #' . $productId),
            'warehouse_id' => $warehouseId,
            'warehouse_label' => $this->warehouseLabel($warehouseId),
            'rows' => $rows,
            'totals' => array_merge($totals, ['balance' => $balance]),
            'hold_qty' => $holdQty,
        ];
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

    public function buildRetail(Request $request): array
    {
        $this->filters = $this->extractFilters($request);

        $products = Product::query()
            ->with('latestPrice')
            ->when($this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts']), fn ($q) => $q->whereIn('id', $this->filters['items']))
            ->when($this->shouldApplyFilter($this->filters['brands'], $this->filters['totalBrands']), fn ($q) => $q->whereIn('brand_id', $this->filters['brands']))
            ->when($this->shouldApplyFilter($this->filters['categories'], $this->filters['totalCategories']), fn ($q) => $q->whereIn('category_id', $this->filters['categories']))
            ->when($this->shouldApplyFilter($this->filters['subcategories'], $this->filters['totalSubcategories']), fn ($q) => $q->whereIn('sub_category_id', $this->filters['subcategories']))
            ->orderBy('name')
            ->get();

        $groups = [];
        $grand = [
            'physical_qty' => 0.0,
            'physical_amount' => 0.0,
            'hold_qty' => 0.0,
            'hold_amount' => 0.0,
            'retail_amount' => 0.0,
        ];

        foreach ($this->resolvedWarehouseIds() as $warehouseId) {
            $rows = [];
            $whTotal = [
                'physical_qty' => 0.0,
                'physical_amount' => 0.0,
                'hold_qty' => 0.0,
                'hold_amount' => 0.0,
                'retail_amount' => 0.0,
            ];

            foreach ($products as $product) {
                $retailPrice = (float) ($product->latestPrice->sale_retail_price ?? 0);
                $physicalQty = $this->getCurrentStock((int) $product->id, (int) $warehouseId);
                $holdQty = $this->currentHoldQty((int) $product->id, (int) $warehouseId);

                if (abs($physicalQty) < 0.0001 && abs($holdQty) < 0.0001) {
                    continue;
                }

                $physicalAmount = $physicalQty * $retailPrice;
                $holdAmount = $holdQty * $retailPrice;
                $retailAmount = $physicalAmount + $holdAmount;

                $rows[] = [
                    'product_name' => $product->name,
                    'physical_qty' => $physicalQty,
                    'hold_qty' => $holdQty,
                    'retail_price' => $retailPrice,
                    'retail_amount' => $retailAmount,
                ];

                $whTotal['physical_qty'] += $physicalQty;
                $whTotal['physical_amount'] += $physicalAmount;
                $whTotal['hold_qty'] += $holdQty;
                $whTotal['hold_amount'] += $holdAmount;
                $whTotal['retail_amount'] += $retailAmount;
            }

            if (empty($rows)) {
                continue;
            }

            $groups[] = [
                'warehouse_id' => (int) $warehouseId,
                'warehouse_label' => $this->warehouseLabel((int) $warehouseId),
                'rows' => $rows,
                'totals' => $whTotal,
            ];

            $grand['physical_qty'] += $whTotal['physical_qty'];
            $grand['physical_amount'] += $whTotal['physical_amount'];
            $grand['hold_qty'] += $whTotal['hold_qty'];
            $grand['hold_amount'] += $whTotal['hold_amount'];
            $grand['retail_amount'] = ($grand['retail_amount'] ?? 0) + $whTotal['retail_amount'];
        }

        return [
            'groups' => $groups,
            'grand' => $grand,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'generated_at' => now(),
        ];
    }

    public function buildHold(Request $request): array
    {
        $this->filters = $this->extractFilters($request);

        $products = Product::query()
            ->with('brandRelation')
            ->when($this->shouldApplyFilter($this->filters['items'], $this->filters['totalProducts']), fn ($q) => $q->whereIn('id', $this->filters['items']))
            ->when($this->shouldApplyFilter($this->filters['brands'], $this->filters['totalBrands']), fn ($q) => $q->whereIn('brand_id', $this->filters['brands']))
            ->when($this->shouldApplyFilter($this->filters['categories'], $this->filters['totalCategories']), fn ($q) => $q->whereIn('category_id', $this->filters['categories']))
            ->when($this->shouldApplyFilter($this->filters['subcategories'], $this->filters['totalSubcategories']), fn ($q) => $q->whereIn('sub_category_id', $this->filters['subcategories']))
            ->orderBy('name')
            ->get();

        $warehouseIds = $this->resolvedWarehouseIds();

        $brandBuckets = [];
        $grand = ['hold_qty' => 0.0];

        foreach ($products as $product) {
            $holdQty = $this->currentHoldQtyForWarehouses((int) $product->id, $warehouseIds);
            if (abs($holdQty) < 0.0001) {
                continue;
            }

            $brandId = (int) ($product->brand_id ?? 0);
            $brandName = $product->brandRelation->name ?? 'No Brand';

            if (!isset($brandBuckets[$brandId])) {
                $brandBuckets[$brandId] = [
                    'brand_id' => $brandId,
                    'brand_name' => $brandName,
                    'rows' => [],
                    'totals' => ['hold_qty' => 0.0],
                ];
            }

            $brandBuckets[$brandId]['rows'][] = [
                'product_name' => $product->name,
                'hold_qty' => $holdQty,
            ];
            $brandBuckets[$brandId]['totals']['hold_qty'] += $holdQty;
            $grand['hold_qty'] += $holdQty;
        }

        $groups = collect($brandBuckets)
            ->sortBy('brand_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'grand' => $grand,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'generated_at' => now(),
        ];
    }
}
