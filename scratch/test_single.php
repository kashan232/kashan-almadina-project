<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Http\Controllers\GeneralLedgerController;

$gl = app(GeneralLedgerController::class);

$start = microtime(true);
// test WAQAR ELECTRONICS (10409)
$waqar = Customer::where('customer_id', '10409')->first() ?? Customer::find(10409);
if ($waqar) {
    $bal = $gl->calculateOpeningBalance('customer', $waqar->id, '2099-12-31');
    echo "Waqar ID: {$waqar->id}, True Bal: {$bal}, Stored Ledger: " . ($waqar->customerLedger ? $waqar->customerLedger->closing_balance : 'N/A') . "\n";
}
$end = microtime(true);
echo "Execution time: " . round($end - $start, 4) . " seconds.\n";
