<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$claims = \App\Models\CustomerClaim::where('status', 'Unposted')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach($claims as $claim) {
    echo "ID: {$claim->id}, Claim No: {$claim->claim_no}, Product: {$claim->product_id}, Orig WH: {$claim->original_warehouse_id}, Claim WH: {$claim->claim_warehouse_id}\n";
}
