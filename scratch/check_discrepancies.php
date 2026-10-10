<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$builder = app(\App\Services\CustomerOutstandingBalanceReportBuilder::class);
$request = new \Illuminate\Http\Request([
    'from_date' => '2020-01-01',
    'to_date' => date('Y-m-d'),
    'report_type' => 'detailed',
]);
$data = $builder->buildDetailed($request);

$discrepancies = [];
foreach ($data['rows'] as $r) {
    $cid = $r['party_id'];
    $trueBal = round($r['calc_balance'], 2);
    $storedLedger = round(\App\Models\CustomerLedger::where('customer_id', $cid)->latest('id')->value('closing_balance') ?? 0, 2);
    $custTableOpening = round(\App\Models\Customer::where('id', $cid)->value('opening_balance') ?? 0, 2);
    
    // If stored ledger closing differs from true calculated balance
    if (abs($trueBal - $storedLedger) > 0.01) {
        $discrepancies[] = [
            'id' => $cid,
            'name' => $r['customer_name'],
            'true_balance' => $trueBal,
            'stored_ledger' => $storedLedger,
            'cust_opening' => $custTableOpening,
            'diff' => round($trueBal - $storedLedger, 2),
        ];
    }
}

echo "TOTAL CUSTOMERS WITH LEDGER DISCREPANCIES: " . count($discrepancies) . "\n\n";
foreach ($discrepancies as $d) {
    echo "Customer ID: {$d['id']} | Name: {$d['name']} | True Bal: {$d['true_balance']} | Stored Ledger: {$d['stored_ledger']} | Diff: {$d['diff']}\n";
}
