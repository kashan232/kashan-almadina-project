<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = \App\Models\Product::where('name', 'HT 1800')->with('latestPrice')->first();
if ($product) {
    echo json_encode($product->toArray(), JSON_PRETTY_PRINT);
} else {
    echo "Product not found";
}
