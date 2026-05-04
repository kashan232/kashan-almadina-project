<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sale = App\Models\Sale::where('invoice_no', 'INVSLE-066')->with('items')->first();
if ($sale) {
    echo "Sale ID: " . $sale->id . " | Invoice: " . $sale->invoice_no . "\n";
    foreach ($sale->items as $i) {
        echo "Item ID: " . $i->id . " | SPrice: " . $i->sales_price . " | Disc: " . $i->discount_amount . " | Qty: " . $i->sales_qty . " | Rate: " . $i->sales_rate . " | Amt: " . $i->amount . "\n";
    }
} else {
    echo "Sale not found.\n";
}
