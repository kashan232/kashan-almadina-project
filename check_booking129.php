<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = App\Models\Productbooking::find(129);
if ($b) {
    echo "Booking 129 found:\n";
    print_r($b->toArray());
} else {
    echo "Booking 129 not found\n";
    $latest = App\Models\Productbooking::latest()->first();
    if ($latest) {
        echo "Latest booking:\n";
        print_r($latest->toArray());
    }
}
