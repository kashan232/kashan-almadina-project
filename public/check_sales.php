<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$cols = Illuminate\Support\Facades\Schema::getColumnListing('sales');
echo json_encode($cols);
