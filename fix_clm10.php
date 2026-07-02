<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Add 1 to Original Warehouse (3)
$wsOrig = \App\Models\WarehouseStock::where('warehouse_id', 3)->where('product_id', 9)->first();
if($wsOrig) {
    $wsOrig->quantity += 1;
    $wsOrig->save();
}

// Subtract 1 from Claim Warehouse (7)
$wsClaim = \App\Models\WarehouseStock::where('warehouse_id', 7)->where('product_id', 9)->first();
if($wsClaim) {
    $wsClaim->quantity -= 1;
    $wsClaim->save();
}

echo "Fixed stuck stock for CLM-10 (xp-450)\n";
