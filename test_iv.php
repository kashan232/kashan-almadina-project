<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ctrl = new \App\Http\Controllers\GeneralLedgerController();
$tx = $ctrl->fetchTransactions('customer', 27, '2026-06-01', '2026-06-17', 'App\Models\Customer');
$iv_tx = array_filter($tx, function($t) { return $t['ref'] == 'IV'; });
echo "\nFound IV transactions from fetchTransactions: " . count($iv_tx) . "\n";
print_r(array_values($iv_tx));
$ivDateCol = "COALESCE(entry_date, DATE(created_at))";

$id = 27;
$start = '2026-06-01';
$end = '2026-06-17';

$incomes = \App\Models\IncomeVoucher::where(function($q) use ($id) {
        $q->whereJsonContains('party_id', (string)$id)
          ->orWhereJsonContains('party_id', (int)$id);
    })
    ->whereIn('status', ['posted', 'Posted'])
    ->whereBetween(DB::raw($ivDateCol), [$start, $end])->get();

echo "Count from raw query: " . $incomes->count() . "\n";
foreach ($incomes as $iv) {
    echo "Processing IV ID: " . $iv->id . "\n";
    $types = json_decode($iv->party_type, true) ?? [];
    $pIds = json_decode($iv->party_id, true) ?? [];
    $amounts = json_decode($iv->amount, true) ?? [];
    
    $typeArray = ['customer', 'walking', 'walkin'];
    
    foreach ($pIds as $idx => $pid) {
        echo "  Checking pid: '$pid', id: '$id', types[idx]: '" . ($types[$idx]??'') . "'\n";
        if ($pid == $id && in_array($types[$idx] ?? '', $typeArray)) {
            $rowAmount = (float)($amounts[$idx] ?? 0);
            echo "    Match found! rowAmount: $rowAmount\n";
        }
    }
}
