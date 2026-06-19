<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gl = new App\Http\Controllers\GeneralLedgerController();
$transactions = $gl->fetchTransactions('customer', 27, '1970-01-01', '2026-06-17');

$totalDebits = 0;
$totalCredits = 0;

$sums = [];

foreach ($transactions as $t) {
    $totalDebits += $t['debit'];
    $totalCredits += $t['credit'];
    
    $ref = $t['ref'];
    if (!isset($sums[$ref])) {
        $sums[$ref] = ['debit' => 0, 'credit' => 0];
    }
    $sums[$ref]['debit'] += $t['debit'];
    $sums[$ref]['credit'] += $t['credit'];
}

echo "fetchTransactions Totals up to 2026-06-17:\n";
echo "Total Debits: $totalDebits\n";
echo "Total Credits: $totalCredits\n";
echo "Net Balance: " . ($totalDebits - $totalCredits) . "\n\n";

echo "By Reference:\n";
foreach ($sums as $ref => $sum) {
    echo "$ref -> Debit: " . $sum['debit'] . " | Credit: " . $sum['credit'] . "\n";
}
