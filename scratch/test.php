<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = App\Models\Customer::where('customer_type', '!=', 'Walking Customer')
    ->get(['id', 'customer_id', 'customer_name'])
    ->filter(function($c) {
        return $c->id == 27 || $c->customer_id === '27' || $c->customer_id === 'CUST-27';
    })->values()->toArray();

echo json_encode($data);
