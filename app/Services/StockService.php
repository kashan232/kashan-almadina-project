<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WarehouseStock;

/**
 * Single source of truth for physical stock mutations.
 *
 * Shop (warehouse_id 0) → products.stock
 * Warehouse (warehouse_id > 0) → warehouse_stocks.quantity
 *
 * Positive qty = Debit (+) stock in, Negative qty = Credit (−) stock out.
 */
class StockService
{
    public function normalizeWarehouseId($warehouseId): int
    {
        if ($warehouseId === null || $warehouseId === '' || $warehouseId === 'shop') {
            return 0;
        }

        return (int) $warehouseId;
    }

    public function isShop($warehouseId): bool
    {
        return $this->normalizeWarehouseId($warehouseId) === 0;
    }

    /**
     * Adjust physical stock. Positive = add, negative = subtract.
     */
    public function adjust(int $productId, $warehouseId, float $qty, bool $createMissingRow = true): void
    {
        if ($qty == 0.0 || $productId <= 0) {
            return;
        }

        $wh = $this->normalizeWarehouseId($warehouseId);

        if ($wh === 0) {
            $product = Product::find($productId);
            if (!$product) {
                return;
            }
            $product->stock = ($product->stock ?? 0) + $qty;
            $product->save();

            return;
        }

        $stock = WarehouseStock::where('warehouse_id', $wh)
            ->where('product_id', $productId)
            ->orderByRaw("CASE WHEN status = 'Posted' THEN 0 WHEN status IS NULL THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->first();

        if (!$stock) {
            if ($qty < 0 && !$createMissingRow) {
                return;
            }

            WarehouseStock::create([
                'warehouse_id' => $wh,
                'product_id'   => $productId,
                'quantity'     => $qty,
                'status'       => 'Posted',
            ]);

            return;
        }

        $stock->quantity = ($stock->quantity ?? 0) + $qty;
        $stock->status = $stock->status ?: 'Posted';
        $stock->save();
    }

    public function add(int $productId, $warehouseId, float $qty): void
    {
        $this->adjust($productId, $warehouseId, abs($qty));
    }

    public function subtract(int $productId, $warehouseId, float $qty, bool $createMissingRow = true): void
    {
        $this->adjust($productId, $warehouseId, -abs($qty), $createMissingRow);
    }

    /** Move stock from one location to another (Credit from, Debit to). */
    public function transfer(int $productId, $fromWarehouseId, $toWarehouseId, float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $this->subtract($productId, $fromWarehouseId, $qty, true);
        $this->add($productId, $toWarehouseId, $qty);
    }

    public function physicalQty(int $productId, $warehouseId): float
    {
        $wh = $this->normalizeWarehouseId($warehouseId);

        if ($wh === 0) {
            return (float) (Product::find($productId)?->stock ?? 0);
        }

        return (float) (WarehouseStock::where('warehouse_id', $wh)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);
    }
}
