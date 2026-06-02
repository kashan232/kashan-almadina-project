<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactions = [];
$receipts = \App\Models\ReceiptsVoucher::where('party_id', 25)
    ->whereIn('type', ['customer', 'walking', 'walkin'])
    ->whereBetween(\Illuminate\Support\Facades\DB::raw('receipt_date'), ['2026-06-01', '2026-06-02'])->get();

foreach ($receipts as $rv) {
    if ($rv->rvid !== '045') continue;
    $accIds = json_decode($rv->row_account_id, true) ?? [];
    $amounts = json_decode($rv->amount, true) ?? [];
    $narrIds = json_decode($rv->narration_id, true) ?? [];
    $discounts = json_decode($rv->discount_value, true) ?? [];

    foreach ($accIds as $idx => $aid) {
        $rowAmount = (float)($amounts[$idx] ?? 0);
        $rowDiscount = (float)($discounts[$idx] ?? 0);
        if ($rowAmount <= 0 && $rowDiscount <= 0) continue;

        $accName = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $aid)->value('title');
        $narrText = '';
        if (isset($narrIds[$idx])) {
            if (is_numeric($narrIds[$idx])) {
                $narrText = \Illuminate\Support\Facades\DB::table('narrations')->where('id', $narrIds[$idx])->value('narration');
            } else {
                $narrText = $narrIds[$idx];
            }
        }
        
        $desc = ($accName ? "$accName " : "") . ($narrText ? " ($narrText)" : ($rv->remarks ?? ''));

        $ref = 'RV';
        $inv = $rv->rvid;

        if ($rowAmount > 0) {
            $transactions[] = [
                'id' => $rv->id . '_' . $idx,
                'desc' => $desc,
                'credit' => $rowAmount,
            ];
        }
        
        if ($rowDiscount > 0) {
            $transactions[] = [
                'id' => $rv->id . '_disc_' . $idx,
                'desc' => "Discount: " . $desc,
                'credit' => $rowDiscount,
            ];
        }
    }
}
var_dump($transactions);
