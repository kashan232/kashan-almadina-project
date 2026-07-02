<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$item = \App\Models\PurchaseItem::with('product.latestPrice')
    ->whereHas('purchase', fn ($q) => $q->where('invoice_no', '079'))
    ->first();

if ($item) {
    echo "price={$item->price} form_rate={$item->form_rate} form_total={$item->form_line_total}\n";
}
