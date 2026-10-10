<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Http\Controllers\GeneralLedgerController;

$gl = app(GeneralLedgerController::class);

$start = microtime(true);
$customers = Customer::all();

$syncedCount = 0;
foreach ($customers as $c) {
    $trueBal = round((float)$gl->calculateOpeningBalance('customer', $c->id, '2099-12-31'), 2);
    
    $latestLedger = CustomerLedger::where('customer_id', $c->id)->latest('id')->first();
    
    if (!$latestLedger) {
        if ($trueBal != 0.0) {
            CustomerLedger::create([
                'customer_id' => $c->id,
                'admin_or_user_id' => 1,
                'date' => date('Y-m-d'),
                'description' => 'Opening / Sync Baseline',
                'opening_balance' => (float)($c->opening_balance ?? 0),
                'previous_balance' => (float)($c->opening_balance ?? 0),
                'debit' => $trueBal > 0 ? $trueBal : 0,
                'credit' => $trueBal < 0 ? abs($trueBal) : 0,
                'closing_balance' => $trueBal,
            ]);
            $syncedCount++;
        }
    } else {
        $stored = round((float)$latestLedger->closing_balance, 2);
        if (abs($trueBal - $stored) > 0.01) {
            $latestLedger->closing_balance = $trueBal;
            $latestLedger->save();
            $syncedCount++;
            echo "Corrected Customer ID {$c->id} ({$c->customer_name}): Stored {$stored} -> True {$trueBal}\n";
        }
    }
}

$end = microtime(true);
echo "Synced {$syncedCount} customer ledgers in " . round($end - $start, 2) . " seconds.\n";
