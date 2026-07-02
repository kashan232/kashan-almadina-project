<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$obj = new \App\Http\Controllers\StockHoldController();
$ref = new ReflectionMethod($obj, 'adjustStock');
$ref->setAccessible(true);
$ref->invoke($obj, 3, 9, 6); // Add 6 of product 9 to warehouse 3

echo 'Done. New Qty: ' . \App\Models\WarehouseStock::where('warehouse_id', 3)->where('product_id', 9)->first()->quantity;
