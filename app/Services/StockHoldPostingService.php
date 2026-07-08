<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockHold;
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\StockReleaseVoucher;
use App\Models\WarehouseStock;

/**
 * Hold / release stock flow (Reserve + Warehouse move together):
 * - Hold:   Reserve (Credit) +qty, Warehouse (Debit) +qty — liability and warehouse both increase
 * - Release: Reserve (Debit) −qty, Warehouse (Credit) −qty — liability and warehouse both decrease
 * - Available = physical warehouse − reserved hold (unchanged on hold; decreases on release)
 */
class StockHoldPostingService
{
    /**
     * Hold post: increase reserve liability and warehouse physical stock by the same qty.
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

        $remaining = $this->applyHoldReservedIncrease(
            $productId,
            $holdQty,
            $partyType,
            $partyId,
            $excludeVoucherId
        );

        if ($remaining > 0) {
            $this->adjustStock($warehouseId, $productId, $remaining);
        }

        return $remaining;
    }

    /**
     * Release post: warehouse stock and hold liability both decrease.
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
        // The physical stock impact must hit the warehouse chosen on the RELEASE
        // itself. If a release is made from W2 against a hold taken in W1, W2's
        // stock must decrease — not W1's. (warehouse_id 0 = Shop is a valid pick.)
        if ($item->warehouse_id !== null && $item->warehouse_id !== '') {
            return (int) $item->warehouse_id;
        }

        if ($voucher->warehouse_id !== null && $voucher->warehouse_id !== '') {
            return (int) $voucher->warehouse_id;
        }

        // Fallback only when the release did not specify a warehouse: use the
        // original hold's location.
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

        return 0;
    }

    public function applyReleaseVoucherPosting(StockReleaseVoucher $voucher): void
    {
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
        }
    }

    public function physicalQty(int $warehouseId, int $productId): float
    {
        if ((int) $warehouseId === 0) {
            return (float) (Product::find($productId)?->stock ?? 0);
        }

        return (float) (WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->orderByRaw("CASE WHEN status = 'Posted' THEN 0 WHEN status IS NULL THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->value('quantity') ?? 0);
    }

    public function reservedQty(int $warehouseId, int $productId, ?int $excludeVoucherId = null): float
    {
        return StockHold::netReservedForProduct($productId, $warehouseId);
    }

    public function availableQty(int $warehouseId, int $productId, ?int $excludeVoucherId = null): float
    {
        return max(0, $this->physicalQty($warehouseId, $productId) - $this->reservedQty($warehouseId, $productId, $excludeVoucherId));
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
        // Over-release allowed — excess shows as positive in Total Reserved.
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
            $formalHold = StockHold::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->whereNotNull('stock_hold_voucher_id')
                ->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                })
                ->when($partyType && $partyId, function ($q) use ($partyType, $partyId) {
                    $q->where('party_type', $partyType)->where('party_id', $partyId);
                })
                ->orderByDesc('id')
                ->first();
            if ($formalHold) {
                return $formalHold;
            }

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
            if ($hold->isFormalHoldLine()) {
                continue;
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
