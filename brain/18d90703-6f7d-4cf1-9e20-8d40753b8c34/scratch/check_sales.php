<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sales = App\Models\Sale::latest()->take(10)->pluck('invoice_no');
print_r($sales);
