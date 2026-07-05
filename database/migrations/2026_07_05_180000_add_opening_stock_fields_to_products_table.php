<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'opening_total_stock')) {
                $table->decimal('opening_total_stock', 18, 3)->nullable()->after('stock');
            }
            if (!Schema::hasColumn('products', 'opening_shop_stock')) {
                $table->decimal('opening_shop_stock', 18, 3)->nullable()->after('opening_total_stock');
            }
            if (!Schema::hasColumn('products', 'opening_warehouse_stocks')) {
                $table->json('opening_warehouse_stocks')->nullable()->after('opening_shop_stock');
            }
        });

        $this->backfillOpeningStockFromAdjustments();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['opening_warehouse_stocks', 'opening_shop_stock', 'opening_total_stock'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillOpeningStockFromAdjustments(): void
    {
        if (!Schema::hasTable('stock_adjustment_details') || !Schema::hasTable('stock_adjustments')) {
            return;
        }

        $rows = DB::table('stock_adjustment_details as sad')
            ->join('stock_adjustments as sa', 'sa.id', '=', 'sad.stock_adjustment_id')
            ->where('sa.remarks', 'like', 'Opening Stock Distribution for Product:%')
            ->select('sad.product_id', 'sa.warehouse_id', 'sad.qty')
            ->get()
            ->groupBy('product_id');

        foreach ($rows as $productId => $items) {
            $warehouseMap = [];
            foreach ($items as $item) {
                if ($item->warehouse_id === null) {
                    continue;
                }
                $warehouseMap[(string) $item->warehouse_id] = (float) $item->qty;
            }

            if (empty($warehouseMap)) {
                continue;
            }

            $product = DB::table('products')->where('id', $productId)->first();
            if (!$product) {
                continue;
            }

            $warehouseTotal = array_sum($warehouseMap);
            $shopStock = max(0, (float) ($product->stock ?? 0));
            $total = $warehouseTotal + $shopStock;

            DB::table('products')->where('id', $productId)->update([
                'opening_warehouse_stocks' => json_encode($warehouseMap),
                'opening_shop_stock' => $shopStock,
                'opening_total_stock' => $total,
            ]);
        }
    }
};
