<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = \App\Models\WarehouseStock::where('status', 'Unposted')
    ->update(['status' => 'Posted']);

echo "Updated $updated warehouse stock records from Unposted to Posted.\n";
