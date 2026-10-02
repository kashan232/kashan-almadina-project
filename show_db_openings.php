<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\WarehouseStock;

// Fetch all products with their saved DB opening values
$products = Product::withoutGlobalScopes()
    ->select(['id', 'name', 'opening_total_stock', 'opening_shop_stock', 'opening_warehouse_stocks', 'stock'])
    ->get();

echo "=== PRODUCTS DATABASE OPENING STOCK STORED VALUES ===\n\n";

foreach ($products as $p) {
    echo "ID: {$p->id} | Name: {$p->name}\n";
    echo "Total Opening Stock (DB): " . ($p->opening_total_stock ?? 'NULL') . "\n";
    echo "Shop Opening Stock (DB): " . ($p->opening_shop_stock ?? 'NULL') . "\n";
    echo "Current Shop Stock (DB): {$p->stock}\n";
    echo "Warehouses Opening Stocks (JSON): " . (json_encode($p->opening_warehouse_stocks) ?? 'NULL') . "\n";
    
    // Also fetch WarehouseStock table quantities
    $whStocks = WarehouseStock::where('product_id', $p->id)->pluck('quantity', 'warehouse_id')->toArray();
    echo "Current Warehouse Stocks (Table): " . json_encode($whStocks) . "\n";
    echo "-----------------------------------------------------\n";
}
