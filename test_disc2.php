<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rvs = \App\Models\ReceiptsVoucher::where('party_id', 25)->get();
foreach ($rvs as $rv) {
    if ($rv->rvid !== '045') continue;
    $accIds = json_decode($rv->row_account_id, true) ?? [];
    $amounts = json_decode($rv->amount, true) ?? [];
    $discounts = json_decode($rv->discount_value, true) ?? [];
    var_dump($rv->discount_value);
    var_dump($discounts);
    foreach ($accIds as $idx => $aid) {
        $rowAmount = (float)($amounts[$idx] ?? 0);
        $rowDiscount = (float)($discounts[$idx] ?? 0);
        echo "Idx $idx: Amount=$rowAmount, Discount=$rowDiscount\n";
    }
}
