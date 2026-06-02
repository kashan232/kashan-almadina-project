<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = new \App\Http\Controllers\GeneralLedgerController;
$m = new ReflectionMethod($c, 'getDateColumn');
$m->setAccessible(true);
$rvDateCol = $m->invoke($c, 'receipts_vouchers', 'receipt_date');

$receipts = \App\Models\ReceiptsVoucher::where('party_id', 25)
    ->whereIn('type', ['customer', 'walking', 'walkin'])
    ->whereBetween(\Illuminate\Support\Facades\DB::raw($rvDateCol), ['2026-06-01', '2026-06-02'])->get();

foreach ($receipts as $rv) {
    if ($rv->rvid !== '045') continue;
    $accIds = json_decode($rv->row_account_id, true) ?? [];
    $amounts = json_decode($rv->amount, true) ?? [];
    $discounts = json_decode($rv->discount_value, true) ?? [];
    var_dump($accIds, $amounts, $discounts);
}
