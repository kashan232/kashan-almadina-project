<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invoice = \App\Models\Sale::orderBy('id', 'desc')->first()->invoice_no;
$controller = app(\App\Http\Controllers\SaleReturnController::class);
$response = $controller->getSaleDetails($invoice);
echo $response->content();
