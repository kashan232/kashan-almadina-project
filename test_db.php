<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transfer = App\Models\StockTransfer::with('items')->latest()->first();
echo "Last Transfer ID: " . $transfer->id . "\n";
echo "From WH: " . $transfer->from_warehouse_id . ", To WH: " . $transfer->to_warehouse_id . "\n";
echo "Status: " . $transfer->status . "\n";
echo "to_shop: " . $transfer->to_shop . "\n";

foreach ($transfer->items as $item) {
    echo "Item ID: " . $item->product_id . ", Qty: " . $item->quantity . "\n";
    $destStock = App\Models\WarehouseStock::where('warehouse_id', $transfer->to_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->first();
    echo "Dest Stock Record: " . ($destStock ? json_encode($destStock->toArray()) : 'Not Found') . "\n";
}
