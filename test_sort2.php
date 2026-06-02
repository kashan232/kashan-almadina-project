<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = new \App\Http\Controllers\GeneralLedgerController;
$m = new ReflectionMethod($c, 'fetchTransactions');
$m->setAccessible(true);
$res = $m->invoke($c, 'customer', 25, '2026-06-01', '2026-06-02');
foreach($res as $r) {
    if (strpos($r['id'], 'disc') !== false) {
        var_dump($r);
    }
}
