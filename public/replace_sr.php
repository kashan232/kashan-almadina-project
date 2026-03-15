<?php
$files = [
    'c:/xampp/htdocs/Al-madina-bettery/resources/views/admin_panel/sale_return/index.blade.php',
    'c:/xampp/htdocs/Al-madina-bettery/resources/views/admin_panel/sale_return/add_return.blade.php',
    'c:/xampp/htdocs/Al-madina-bettery/resources/views/admin_panel/sale_return/print_return.blade.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Exact replacements
        $content = str_replace('Purchase Return', 'Sale Return', $content);
        $content = str_replace('purchase return', 'sale return', $content);
        $content = str_replace('PurchaseReturn', 'SaleReturn', $content);
        $content = str_replace('purchasereturn', 'salereturn', $content);
        $content = str_replace('purchase.return', 'sale.return', $content);
        $content = str_replace('purchase_return', 'sale_return', $content);
        $content = str_replace('Purchase Returns', 'Sale Returns', $content);
        
        $content = str_replace('Purchase Invoice', 'Sale Invoice', $content);
        $content = str_replace('purchase_invoice', 'sale_invoice', $content);
        
        $content = str_replace('purchase_id', 'sale_id', $content);
        $content = str_replace('purchase', 'sale', $content);
        $content = str_replace('Purchase', 'Sale', $content);
        
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
