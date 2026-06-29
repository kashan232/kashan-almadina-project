<?php

$modules = [
    'InwardGatepass' => ['model' => 'InwardGatepass', 'controller' => 'InwardGatepassController', 'view_dir' => 'inward'],
    'Purchase' => ['model' => 'Purchase', 'controller' => 'PurchaseController', 'view_dir' => 'purchase'],
    'PurchaseReturn' => ['model' => 'PurchaseReturn', 'controller' => 'PurchaseReturnController', 'view_dir' => 'purchase_return'],
    'StockWastage' => ['model' => 'StockWastage', 'controller' => 'StockWastageController', 'view_dir' => 'stock_wastage'],
    'Vendor' => ['model' => 'Vendor', 'controller' => 'VendorController', 'view_dir' => 'vendors'],
    'StockTransfer' => ['model' => 'StockTransfer', 'controller' => 'StockTransferController', 'view_dir' => 'warehouses/stock_transfers'],
    'Warehouse' => ['model' => 'Warehouse', 'controller' => 'WarehouseController', 'view_dir' => 'warehouses'],
    'SaleReturn' => ['model' => 'SaleReturn', 'controller' => 'SaleReturnController', 'view_dir' => 'sale_return'],
    'StockHold' => ['model' => 'StockHoldVoucher', 'controller' => 'StockHoldController', 'view_dir' => 'sale/booking'],
    'StockRelease' => ['model' => 'StockReleaseVoucher', 'controller' => 'StockReleaseController', 'view_dir' => 'sale/booking'],
    'SalesOfficer' => ['model' => 'SalesOfficer', 'controller' => 'SalesOfficerController', 'view_dir' => 'sales_officer'],
    'Zone' => ['model' => 'Zone', 'controller' => 'ZoneController', 'view_dir' => 'zone'],
    'CustomerClaim' => ['model' => 'CustomerClaim', 'controller' => 'CustomerClaimController', 'view_dir' => 'customer_claims'],
    'ClaimAcceptance' => ['model' => 'ClaimAcceptance', 'controller' => 'ClaimAcceptanceController', 'view_dir' => 'claim_acceptance'],
    'ClaimItemReceipt' => ['model' => 'ClaimItemReceipt', 'controller' => 'ClaimItemReceiptController', 'view_dir' => 'claim_item_receipt'],
    'ReceiptVoucher' => ['model' => 'ReceiptsVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
    'PaymentVoucher' => ['model' => 'PaymentVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
    'ExpenseVoucher' => ['model' => 'ExpenseVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
    'IncomeVoucher' => ['model' => 'IncomeVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
    'JournalVoucher' => ['model' => 'JournalVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
    'AdjustmentVoucher' => ['model' => 'AdjustmentVoucher', 'controller' => 'VoucherController', 'view_dir' => 'vouchers'],
];

// Helper to update Models
foreach($modules as $name => $meta) {
    $modelFile = __DIR__."/app/Models/{$meta['model']}.php";
    if (file_exists($modelFile)) {
        $content = file_get_contents($modelFile);
        if (strpos($content, 'function creator') === false && strpos($content, 'function user') === false) {
            $creatorMethod = "\n    public function creator()\n    {\n        return \$this->belongsTo(User::class, 'created_by');\n    }\n";
            // Insert before the last closing brace
            $pos = strrpos($content, '}');
            if ($pos !== false) {
                $content = substr_replace($content, $creatorMethod, $pos, 0);
                file_put_contents($modelFile, $content);
                echo "Added creator() to {$meta['model']}.php\n";
            }
        }
    } else {
        echo "Missing model file: {$modelFile}\n";
    }
}
