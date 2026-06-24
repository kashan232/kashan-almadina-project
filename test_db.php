<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/sale/add', 'POST', [
    'customer' => 1,
    'entry_date' => date('Y-m-d'),
    'entry_time' => date('H:i'),
    'warehouse_name' => [1],
    'product_id' => [1],
    'stock' => [100],
    'sales-price' => [1234.56],
    'sales-qty' => [1],
    'retail-price' => [1500],
    'sales-rate' => [1234.56],
    'discount-percent' => [0],
    'discount-amount' => [0],
    'sales-amount' => [1234.56],
]);

$controller = app(\App\Http\Controllers\SaleController::class);
$response = $controller->store($request);

$sale = \App\Models\Sale::latest('id')->first();
echo json_encode($sale->items->first()->toArray(), JSON_PRETTY_PRINT);
