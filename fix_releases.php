<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StockRelease;
use App\Models\StockReleaseVoucher;

$orphans = StockRelease::whereNull('stock_release_voucher_id')->get();
foreach($orphans as $r) {
    echo "Processing release ID: " . $r->id . "\n";
    $v = StockReleaseVoucher::create([
        'voucher_no'      => $r->release_no ?? 'SR-OLD-' . $r->id,
        'date'            => $r->created_at,
        'hold_voucher_id' => $r->hold->stock_hold_voucher_id ?? null,
        'party_type'      => $r->party_type,
        'party_id'        => $r->party_id,
        'warehouse_id'    => $r->warehouse_id,
        'status'          => $r->status ?? 'Posted'
    ]);
    $r->update(['stock_release_voucher_id' => $v->id]);
    echo "Created voucher ID: " . $v->id . "\n";
}
echo "Done.\n";
