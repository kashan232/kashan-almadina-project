<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$cols1 = Illuminate\Support\Facades\Schema::getColumnListing('sale_returns');
$cols2 = Illuminate\Support\Facades\Schema::getColumnListing('sale_return_items');

echo json_encode(['Sale Returns:' => $cols1, 'Sale Return Items:' => $cols2], JSON_PRETTY_PRINT);
