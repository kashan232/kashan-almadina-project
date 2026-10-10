<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$c = App\Models\Customer::where('customer_name', 'like', '%WAQAR%')->first();
echo "Customer DB ID: " . $c->id . " | Customer ID: " . $c->customer_id . " | Name: " . $c->customer_name . "\n";
echo "Customer Table Opening: " . $c->opening_balance . "\n\n";

echo "CUSTOMER LEDGERS ROWS:\n";
foreach(App\Models\CustomerLedger::where('customer_id', $c->id)->get() as $l) {
    echo "ID: {$l->id} | Date: {$l->date} | Desc: {$l->description} | DR: {$l->debit} | CR: {$l->credit} | Prev: {$l->previous_balance} | Close: {$l->closing_balance}\n";
}
