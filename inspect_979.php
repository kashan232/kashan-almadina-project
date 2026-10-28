<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\Sale::with(['items', 'customer'])->find(979);
if (!$s) {
    echo "Sale 979 not found\n";
    exit;
}
echo "ID: {$s->id}\n";
echo "Invoice No: {$s->invoice_no}\n";
echo "Customer: " . ($s->customer->customer_name ?? 'N/A') . "\n";
echo "Address: {$s->address}\n";
echo "Items Count: " . $s->items->count() . "\n";
echo "Subtotal: {$s->sub_total2}\n";
