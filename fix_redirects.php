<?php
$file = 'app/Http/Controllers/VoucherController.php';
$content = file_get_contents($file);

$content = str_replace(
    "return back()->with('success', 'Expense Voucher posted successfully!');",
    "return redirect()->route('all-expense-vochers')->with('success', 'Expense Voucher posted successfully!');",
    $content
);

$content = str_replace(
    "return back()->with('success', 'Payment Voucher posted successfully!');",
    "return redirect()->route('all-Payment-vochers')->with('success', 'Payment Voucher posted successfully!');",
    $content
);

$content = str_replace(
    "return back()->with('success', 'Income Voucher posted successfully!');",
    "return redirect()->route('all-income-vochers')->with('success', 'Income Voucher posted successfully!');",
    $content
);

$content = str_replace(
    "return back()->with('success', 'Journal Voucher posted successfully!');",
    "return redirect()->route('all-journal-vochers')->with('success', 'Journal Voucher posted successfully!');",
    $content
);

file_put_contents($file, $content);
echo "Fixed";
