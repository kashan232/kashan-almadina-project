<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$receipts = \App\Models\ClaimItemReceipt::all();
foreach($receipts as $r) {
    echo "ID: {$r->id}, Voucher: {$r->voucher_no}, Status: {$r->status}\n";
}
