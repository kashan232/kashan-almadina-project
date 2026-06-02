<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = [
    'ReceiptsVoucher', 'PaymentVoucher', 'JournalVoucher', 
    'ExpenseVoucher', 'IncomeVoucher', 'Purchase', 'Sale', 
    'PurchaseReturn', 'SaleReturn'
];

foreach ($models as $m) {
    $cls = '\App\Models\\'.$m;
    if (class_exists($cls)) {
        $inst = new $cls;
        if (in_array('status', \Illuminate\Support\Facades\Schema::getColumnListing($inst->getTable()))) {
            echo $m . ": YES\n";
        } else {
            echo $m . ": NO\n";
        }
    } else {
        // Fallback if model doesn't exist, check table directly (e.g. expense_vouchers)
        $tableMap = [
            'ExpenseVoucher' => 'expense_vouchers',
            'IncomeVoucher' => 'income_vouchers'
        ];
        if (isset($tableMap[$m])) {
            if (in_array('status', \Illuminate\Support\Facades\Schema::getColumnListing($tableMap[$m]))) {
                echo $m . " (Table): YES\n";
            } else {
                echo $m . " (Table): NO\n";
            }
        }
    }
}
