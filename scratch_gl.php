<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gl = app(\App\Http\Controllers\GeneralLedgerController::class);
$ids = [10072, 10004, 20074];

foreach($ids as $id) {
    $c = \App\Models\Customer::find($id);
    $op = $gl->calculateOpeningBalance('customer', $id, '2020-01-01');
    $txs = $gl->fetchTransactions('customer', $id, '2020-01-01', '2026-10-06');
    $dr = array_sum(array_column($txs, 'debit'));
    $cr = array_sum(array_column($txs, 'credit'));
    $glFull = $op + $dr - $cr;
    $ledgerBal = $c->customerLedger ? $c->customerLedger->closing_balance : 0;
    
    echo sprintf("%-25s | Ledger: %12.2f | GL Full: %12.2f | Diff: %12.2f\n", $c->customer_name, $ledgerBal, $glFull, $ledgerBal - $glFull);
}
