<?php

$file = 'app/Http/Controllers/GeneralLedgerController.php';
$content = file_get_contents($file);

// We need to add ->whereIn('status', ['posted', 'Posted', 'Approved']) to the queries of:
// ReceiptsVoucher, PaymentVoucher, JournalVoucher, ExpenseVoucher, IncomeVoucher, SaleReturn

// ReceiptsVoucher
$content = preg_replace(
    "/ReceiptsVoucher::where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "ReceiptsVoucher::where($1)$2->whereIn('status', ['posted', 'Posted'])$3",
    $content
);

// PaymentVoucher
$content = preg_replace(
    "/PaymentVoucher::where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "PaymentVoucher::where($1)$2->whereIn('status', ['posted', 'Posted'])$3",
    $content
);

// JournalVoucher
$content = preg_replace(
    "/JournalVoucher::where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "JournalVoucher::where($1)$2->whereIn('status', ['posted', 'Posted'])$3",
    $content
);

// DB::table('expense_vouchers')
$content = preg_replace(
    "/DB::table\('expense_vouchers'\)->where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "DB::table('expense_vouchers')->where($1)$2->whereIn('status', ['posted', 'Posted'])$3",
    $content
);

// DB::table('income_vouchers')
$content = preg_replace(
    "/DB::table\('income_vouchers'\)->where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "DB::table('income_vouchers')->where($1)$2->whereIn('status', ['posted', 'Posted'])$3",
    $content
);

// SaleReturn
$content = preg_replace(
    "/SaleReturn::(?:with\(\[.*?\]\)->)?where\((.*?)\)(.*?)(->whereBetween.*?->get\(\))/s",
    "$0", // Wait, need to see if we can just append it safely.
    $content
);

file_put_contents('app/Http/Controllers/GeneralLedgerController.php_tmp', $content);
echo "Done";
