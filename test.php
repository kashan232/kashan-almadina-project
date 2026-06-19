<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gl = new App\Http\Controllers\GeneralLedgerController();
echo $gl->calculateOpeningBalance('customer', 27, '2026-06-18');
