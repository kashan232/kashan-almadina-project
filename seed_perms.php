<?php
$perms = [
    'Dashboard', 'Products', 'Category', 'Sub Category', 'Brands', 'Units', 
    'Inward Gatepass', 'Add Gatepass', 'Purchase', 'Purchase Return', 'Stock Wastage', 'Vendor', 
    'Warehouse', 'Warehouse Stock', 'Stock Transfer', 'Sales', 'Sale Return', 'Stock Hold', 'Stock Release', 
    'Customer', 'Sales Officer', 'Zone', 'Customer Claim', 'Claim Acceptance', 'Claim Receipt', 
    'Users', 'Roles', 'Permissions', 'Branches', 'User Groups', 
    'Chart Of Accounts', 'Narrations', 'Receipts Voucher', 'Payment Voucher', 'Expense Voucher', 'Income Voucher', 'Journal Voucher', 'Adjustment Voucher', 
    'Rollback Posting', 'General Ledger', 'Reports Dashboard', 'Sales Report', 'Purchase Report', 'Claim Report', 'Claim Acceptance Report', 'Claim Receipt Report', 'Stock Wastage Report', 'Stock Transfer Report', 'Receipt Voucher Report', 'Payment Voucher Report', 'Expense Voucher Report', 'Income Voucher Report', 'Journal Voucher Report', 'Adjustment Voucher Report'
];

foreach ($perms as $p) {
    Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
}
echo "Permissions seeded successfully.\n";
