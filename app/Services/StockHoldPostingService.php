<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalVoucher;
use App\Models\Product;
use App\Models\StockHold;
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\StockReleaseVoucher;
use App\Models\WarehouseStock;

/**
 * Client rule (Hold / Warehouse liability):
 * - Stock Hold:   Hold = Credit (liability ↑), Warehouse = Debit (liability ↑)
 * - Stock Release: Hold = Debit (liability ↓), Warehouse = Credit (liability ↓)
 *
 * Stock qty mirrors this: hold post increases physical + hold qty; release decreases physical.
 */
class StockHoldPostingService
{
    /**
     * Hold post: Warehouse Debit (+physical), Hold Credit (+reserved qty).
     */
    public function applyHoldEffects(
        int $warehouseId,
        int $productId,
        float $holdQty,
        ?string $partyType = null,
        ?int $partyId = null,
        ?int $excludeVoucherId = null
    ): float {
        if ($holdQty <= 0) {
            return 0;
        }

        $this->adjustStock($warehouseId, $productId, $holdQty);

        return $this->applyHoldReservedIncrease(
            $productId,
            $holdQty,
            $partyType,
            $partyId,
            $excludeVoucherId
        );
    }

    /**
     * Release post: Warehouse Credit (-physical), Hold Debit (-reserved via release records).
     */
    public function applyReleaseEffects(
        int $warehouseId,
        int $productId,
        float $releaseQty,
        ?int $explicitHoldId = null,
        ?int $holdVoucherId = null,
        ?int $claimId = null,
        ?string $partyType = null,
        ?int $partyId = null
    ): ?StockHold {
        if ($releaseQty <= 0) {
            return null;
        }

        $this->adjustStock($warehouseId, $productId, -$releaseQty);

        return $this->reduceReservedForRelease(
            $productId,
            $releaseQty,
            $explicitHoldId,
            $holdVoucherId,
            $claimId,
            $partyType,
            $partyId
        );
    }

    public function resolveReleaseWarehouseId(StockReleaseVoucher $voucher, StockRelease $item): int
    {
        if ($item->hold_id) {
            $hold = StockHold::withoutGlobalScopes()->find($item->hold_id);
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

    public function applyReleaseVoucherPosting(StockReleaseVoucher $voucher): float
    {
        $totalAmount = 0.0;

        $items = StockRelease::withoutGlobalScopes()
            ->where('stock_release_voucher_id', $voucher->id)
            ->get();

        foreach ($items as $item) {
            $releaseQty = (float) $item->release_qty;
            if ($releaseQty <= 0) {
                $item->delete();
                continue;
            }

            $linkedHold = null;
            if ($item->hold_id) {
                $linkedHold = StockHold::withoutGlobalScopes()
                    ->withSum(StockHold::postedReleasesWithSum(), 'release_qty')
                    ->find($item->hold_id);
            }

            $this->assertReleaseQtyAllowed($linkedHold, $releaseQty);

            $this->applyReleaseEffects(
                $this->resolveReleaseWarehouseId($voucher, $item),
                (int) $item->product_id,
                $releaseQty,
                $item->hold_id ? (int) $item->hold_id : null,
                $voucher->hold_voucher_id ? (int) $voucher->hold_voucher_id : null,
                $voucher->claim_id ? (int) $voucher->claim_id : null,
                $voucher->party_type,
                $voucher->party_id ? (int) $voucher->party_id : null
            );

            $item->update(['status' => 'Posted']);
            $totalAmount += $this->lineAmount((int) $item->product_id, $releaseQty);
        }

        $this->postReleaseAccounting($voucher, $totalAmount);

        return $totalAmount;
    }

    public function postHoldAccounting(StockHoldVoucher $voucher, float $totalAmount): void
    {
        if ($totalAmount <= 0 || !$voucher->hold_account_id || !$voucher->warehouse_account_id) {
            return;
        }

        $holdAccount = Account::find($voucher->hold_account_id);
        $warehouseAccount = Account::find($voucher->warehouse_account_id);
        if (!$holdAccount || !$warehouseAccount) {
            return;
        }

        $jvid = 'SH-GL-' . ($voucher->voucher_no ?? $voucher->id);
        if (JournalVoucher::withoutGlobalScopes()->where('jvid', $jvid)->exists()) {
            return;
        }

        // Hold Credit (liability increase)
        $holdAccount->opening_balance = ($holdAccount->opening_balance ?? 0) - $totalAmount;
        $holdAccount->save();

        // Warehouse Debit (liability increase)
        $warehouseAccount->opening_balance = ($warehouseAccount->opening_balance ?? 0) + $totalAmount;
        $warehouseAccount->save();

        JournalVoucher::create([
            'jvid' => $jvid,
            'entry_date' => $voucher->date ?? now()->toDateString(),
            'status' => 'posted',
            'total_debit' => $totalAmount,
            'total_credit' => $totalAmount,
            'party_type' => json_encode([(string) ($warehouseAccount->head_id ?? ''), (string) ($holdAccount->head_id ?? '')]),
            'party_id' => json_encode([$warehouseAccount->id, $holdAccount->id]),
            'debit' => json_encode([$totalAmount, 0]),
            'credit' => json_encode([0, $totalAmount]),
            'remarks' => 'Stock Hold ' . ($voucher->display_no ?? $voucher->voucher_no),
        ]);
    }

    public function postReleaseAccounting(StockReleaseVoucher $voucher, float $totalAmount): void
    {
        if ($totalAmount <= 0 || !$voucher->hold_account_id || !$voucher->warehouse_account_id) {
            return;
        }

        $holdAccount = Account::find($voucher->hold_account_id);
        $warehouseAccount = Account::find($voucher->warehouse_account_id);
        if (!$holdAccount || !$warehouseAccount) {
            return;
        }

        $jvid = 'SR-GL-' . ($voucher->voucher_no ?? $voucher->id);
        if (JournalVoucher::withoutGlobalScopes()->where('jvid', $jvid)->exists()) {
            return;
        }

        // Hold Debit (liability decrease)
        $holdAccount->opening_balance = ($holdAccount->opening_balance ?? 0) + $totalAmount;
        $holdAccount->save();

        // Warehouse Credit (liability decrease)
        $warehouseAccount->opening_balance = ($warehouseAccount->opening_balance ?? 0) - $totalAmount;
        $warehouseAccount->save();

        JournalVoucher::create([
            'jvid' => $jvid,
            'entry_date' => $voucher->date ?? now()->toDateString(),
            'status' => 'posted',
            'total_debit' => $totalAmount,
            'total_credit' => $totalAmount,
            'party_type' => json_encode([(string) ($holdAccount->head_id ?? ''), (string) ($warehouseAccount->head_id ?? '')]),
            'party_id' => json_encode([$holdAccount->id, $warehouseAccount->id]),
            'debit' => json_encode([$totalAmount, 0]),
            'credit' => json_encode([0, $totalAmount]),
            'remarks' => 'Stock Release ' . ($voucher->display_no ?? $voucher->voucher_no),
        ]);
    }

    public function reverseHoldAccounting(StockHoldVoucher $voucher, float $totalAmount): void
    {
        if ($totalAmount <= 0 || !$voucher->hold_account_id || !$voucher->warehouse_account_id) {
            return;
        }

        $holdAccount = Account::find($voucher->hold_account_id);
        $warehouseAccount = Account::find($voucher->warehouse_account_id);
        if (!$holdAccount || !$warehouseAccount) {
            return;
        }

        $holdAccount->opening_balance = ($holdAccount->opening_balance ?? 0) + $totalAmount;
        $holdAccount->save();
        $warehouseAccount->opening_balance = ($warehouseAccount->opening_balance ?? 0) - $totalAmount;
        $warehouseAccount->save();

        JournalVoucher::withoutGlobalScopes()
            ->where('jvid', 'SH-GL-' . ($voucher->voucher_no ?? $voucher->id))
            ->delete();
    }

    public function reverseReleaseAccounting(StockReleaseVoucher $voucher, float $totalAmount): void
    {
        if ($totalAmount <= 0 || !$voucher->hold_account_id || !$voucher->warehouse_account_id) {
            return;
        }

        $holdAccount = Account::find($voucher->hold_account_id);
        $warehouseAccount = Account::find($voucher->warehouse_account_id);
        if (!$holdAccount || !$warehouseAccount) {
            return;
        }

        $holdAccount->opening_balance = ($holdAccount->opening_balance ?? 0) - $totalAmount;
        $holdAccount->save();
        $warehouseAccount->opening_balance = ($warehouseAccount->opening_balance ?? 0) + $totalAmount;
        $warehouseAccount->save();

        JournalVoucher::withoutGlobalScopes()
            ->where('jvid', 'SR-GL-' . ($voucher->voucher_no ?? $voucher->id))
            ->delete();
    }

    public function computeHoldVoucherAmount(StockHoldVoucher $voucher): float
    {
        $voucher->loadMissing('items');
        $total = 0.0;
        foreach ($voucher->items as $item) {
            $total += $this->lineAmount((int) $item->product_id, (float) $item->hold_qty);
        }

        return $total;
    }

    public function computeReleaseVoucherAmount(StockReleaseVoucher $voucher): float
    {
        $items = StockRelease::withoutGlobalScopes()
            ->where('stock_release_voucher_id', $voucher->id)
            ->get();

        $total = 0.0;
        foreach ($items as $item) {
            $total += $this->lineAmount((int) $item->product_id, (float) $item->release_qty);
        }

        return $total;
    }

    public function lineAmount(int $productId, float $qty): float
    {
        if ($qty <= 0) {
            return 0.0;
        }

        $product = Product::with('latestPrice')->find($productId);
        $rate = (float) ($product?->latestPrice?->sale_net_amount ?? 0);

        return round($qty * $rate, 2);
    }

    public function adjustStock(int $warehouseId, int $productId, float $qty): void
    {
        if ((int) $warehouseId === 0) {
            $product = Product::find($productId);
            if ($product) {
                $product->stock = ($product->stock ?? 0) + $qty;
                $product->save();
            }

            return;
        }

        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->orderByRaw("CASE WHEN status = 'Posted' THEN 0 WHEN status IS NULL THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->first();

        if (!$stock) {
            $stock = new WarehouseStock([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);
        }

        $stock->quantity = ($stock->quantity ?? 0) + $qty;
        $stock->status = 'Posted';
        $stock->save();
    }

    public function assertReleaseQtyAllowed(?StockHold $hold, float $releaseQty): void
    {
        if (!$hold || !$hold->isFormalHoldLine()) {
            return;
        }

        $remaining = $hold->remainingHoldQty();
        if ($releaseQty > $remaining + 0.0001) {
            throw new \InvalidArgumentException(
                'Release qty (' . $releaseQty . ') exceeds remaining hold qty (' . $remaining . ') for this hold line.'
            );
        }
    }

    private function applyHoldReservedIncrease(
        int $productId,
        float $holdQty,
        ?string $partyType = null,
        ?int $partyId = null,
        ?int $excludeVoucherId = null
    ): float {
        $remaining = $holdQty;

        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            })
            ->where('hold_qty', '<', 0)
            ->where(function ($q) {
                $q->whereNull('stock_hold_voucher_id')
                    ->orWhereHas('voucher', function ($v) {
                        $v->where('status', 'Posted');
                    });
            });

        if ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        }

        if ($excludeVoucherId) {
            $query->where(function ($q) use ($excludeVoucherId) {
                $q->whereNull('stock_hold_voucher_id')
                    ->orWhere('stock_hold_voucher_id', '!=', $excludeVoucherId);
            });
        }

        $negativeHolds = $query->orderBy('id')->get();

        foreach ($negativeHolds as $hold) {
            if ($remaining <= 0) {
                break;
            }

            $debt = abs((float) $hold->hold_qty);
            $pay = min($remaining, $debt);
            $hold->hold_qty = (float) $hold->hold_qty + $pay;
            $hold->status = (float) $hold->hold_qty == 0 ? 1 : 0;
            $hold->save();
            $remaining -= $pay;
        }

        return $remaining;
    }

    private function reduceReservedForRelease(
        int $productId,
        float $releaseQty,
        ?int $explicitHoldId = null,
        ?int $holdVoucherId = null,
        ?int $claimId = null,
        ?string $partyType = null,
        ?int $partyId = null
    ): ?StockHold {
        $remaining = $releaseQty;
        $primaryHold = null;
        $explicitHold = null;

        if ($explicitHoldId) {
            $explicitHold = StockHold::withoutGlobalScopes()
                ->withSum(StockHold::postedReleasesWithSum(), 'release_qty')
                ->find($explicitHoldId);
            if ($explicitHold && (int) $explicitHold->product_id === $productId) {
                $primaryHold = $explicitHold;
                if ($explicitHold->isFormalHoldLine()) {
                    return $primaryHold;
                }
            }
        }

        if (!$holdVoucherId && $explicitHold?->stock_hold_voucher_id) {
            $holdVoucherId = (int) $explicitHold->stock_hold_voucher_id;
        }

        if ($holdVoucherId) {
            $formalHold = StockHold::withoutGlobalScopes()
                ->withSum(StockHold::postedReleasesWithSum(), 'release_qty')
                ->where('stock_hold_voucher_id', $holdVoucherId)
                ->where('product_id', $productId)
                ->first();
            if ($formalHold) {
                return $formalHold;
            }
        }

        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            })
            ->where('hold_qty', '>', 0);

        if ($claimId) {
            $query->where('meta->claim_id', (string) $claimId);
        } elseif ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        } elseif ($explicitHoldId) {
            if ($explicitHold?->stock_hold_voucher_id) {
                $query->where('stock_hold_voucher_id', $explicitHold->stock_hold_voucher_id);
            } else {
                $query->where('id', $explicitHoldId);
            }
        } else {
            return $this->applyReleaseOverflow($productId, $releaseQty, $partyType, $partyId, null);
        }

        if ($explicitHoldId) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$explicitHoldId]);
        }
        $holds = $query->orderBy('id')->get();

        foreach ($holds as $hold) {
            if ($remaining <= 0) {
                break;
            }
            if (!$primaryHold) {
                $primaryHold = $hold;
            }

            $deduct = min((float) $hold->hold_qty, $remaining);
            $hold->hold_qty = (float) $hold->hold_qty - $deduct;
            $hold->status = (float) $hold->hold_qty <= 0 ? 1 : 0;
            $hold->save();
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            $overflowHold = $primaryHold ?? $explicitHold ?? $this->findReleaseOverflowHold($productId, $partyType, $partyId);
            if (!$overflowHold) {
                $overflowHold = $this->createReleaseOverflowHold($productId, $partyType, $partyId, $explicitHold?->warehouse_id);
            }

            $overflowHold->hold_qty = (float) $overflowHold->hold_qty - $remaining;
            $overflowHold->status = (float) $overflowHold->hold_qty < 0 ? 0 : (((float) $overflowHold->hold_qty == 0) ? 1 : 0);
            $overflowHold->save();
            $primaryHold = $primaryHold ?? $overflowHold;
        }

        return $primaryHold;
    }

    private function applyReleaseOverflow(
        int $productId,
        float $releaseQty,
        ?string $partyType = null,
        ?int $partyId = null,
        ?StockHold $preferredHold = null
    ): ?StockHold {
        $hold = $preferredHold ?? $this->findReleaseOverflowHold($productId, $partyType, $partyId);
        if (!$hold) {
            $hold = $this->createReleaseOverflowHold($productId, $partyType, $partyId, null);
        }

        $hold->hold_qty = (float) $hold->hold_qty - $releaseQty;
        $hold->status = (float) $hold->hold_qty < 0 ? 0 : (((float) $hold->hold_qty == 0) ? 1 : 0);
        $hold->save();

        return $hold;
    }

    private function findReleaseOverflowHold(int $productId, ?string $partyType, ?int $partyId): ?StockHold
    {
        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            });

        if ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        }

        return $query->orderByRaw('CASE WHEN hold_qty <= 0 THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->first();
    }

    private function createReleaseOverflowHold(
        int $productId,
        ?string $partyType,
        ?int $partyId,
        $warehouseId
    ): StockHold {
        return StockHold::create([
            'product_id' => $productId,
            'party_type' => $partyType,
            'party_id' => $partyId,
            'warehouse_id' => $warehouseId ?? 0,
            'hold_qty' => 0,
            'status' => 0,
            'entry_date' => now()->toDateString(),
            'entry_time' => now()->format('H:i'),
        ]);
    }
}
