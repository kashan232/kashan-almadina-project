<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$ws = \App\Models\WarehouseStock::where('warehouse_id', 3)->where('product_id', 9)->first();
if($ws) {
    $ws->quantity -= 6;
    $ws->save();
    echo 'Fixed stuck stock from SH-0023';
}
