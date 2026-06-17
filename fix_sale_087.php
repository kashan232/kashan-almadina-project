<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\Sale::find(63);
if ($s) {
    $s->partyType = 'customer';
    $s->entry_date = substr($s->created_at, 0, 10);
    $s->entry_time = substr($s->created_at, 11, 8);
    $s->save();
    echo "Fixed Sale 087\n";
}

$oldVoucher = App\Models\Voucher::where('narration', 'Discount on Sale: 087')
    ->where('date', '<', '2026-06-17')
    ->first();
if ($oldVoucher) {
    $oldVoucher->delete();
    echo "Deleted old duplicate discount voucher\n";
}
