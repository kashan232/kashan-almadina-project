<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Services\CustomerOutstandingBalanceReportBuilder;
use App\Http\Controllers\GeneralLedgerController;

$start = microtime(true);
$builder = app(CustomerOutstandingBalanceReportBuilder::class);
$request = new \Illuminate\Http\Request([
    'from_date' => '2020-01-01',
    'to_date' => date('Y-m-d'),
    'report_type' => 'detailed',
]);
$data = $builder->buildDetailed($request);

$synced = 0;
foreach ($data['rows'] as $r) {
    $cid = $r['party_id'];
    $trueBal = round((float)$r['calc_balance'], 2);
    $latestLedger = CustomerLedger::where('customer_id', $cid)->latest('id')->first();
    $storedLedger = round((float)($latestLedger->closing_balance ?? 0), 2);

    if (!$latestLedger || abs($trueBal - $storedLedger) > 0.01) {
        $synced++;
        if ($latestLedger) {
            $latestLedger->closing_balance = $trueBal;
            $latestLedger->save();
        } else {
            CustomerLedger::create([
                'customer_id' => $cid,
                'admin_or_user_id' => 1,
                'date' => date('Y-m-d'),
                'description' => 'Ledger Auto-Sync Baseline',
                'opening_balance' => 0,
                'previous_balance' => $trueBal,
                'debit' => 0,
                'credit' => 0,
                'closing_balance' => $trueBal,
            ]);
        }
    }
}

$end = microtime(true);
echo "Synced {$synced} customer ledgers in " . round($end - $start, 2) . " seconds.\n";
