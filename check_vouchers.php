<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['vouchers', 'expense_vouchers', 'income_vouchers', 'sales'];
foreach ($tables as $t) {
    if (in_array('status', \Illuminate\Support\Facades\Schema::getColumnListing($t))) {
        echo $t . ": YES\n";
    } else {
        echo $t . ": NO\n";
    }
}
