<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rv = \App\Models\ReceiptsVoucher::where('rvid', '045')->first();
var_dump($rv->discount_value);
$discounts = json_decode($rv->discount_value, true) ?? [];
var_dump($discounts);
var_dump($discounts[0] ?? 0);

