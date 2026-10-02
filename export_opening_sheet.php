<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Warehouse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Fetch all active/inactive products sorted strictly by Item ID (id)
$products = Product::withoutGlobalScopes()
    ->with(['brandRelation', 'sub_category_relation', 'sub_category_relation.category', 'latestPrice'])
    ->orderBy('id', 'asc')
    ->get();

$warehouses = Warehouse::withoutGlobalScopes()->orderBy('id', 'asc')->get();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Products Opening Stock');

// Base headers
$headers = [
    'Item ID',
    'Product Name',
    'Category',
    'Sub Category',
    'Brand',
    'Weight',
    'Alert Qty',
    'Total Opening Stock',
];

// Dynamic warehouse columns
foreach ($warehouses as $wh) {
    $headers[] = 'Warehouse Stock (' . $wh->warehouse_name . ')';
}

// Write Header Row
foreach ($headers as $colIndex => $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
    $sheet->setCellValue($colLetter . '1', $header);
}

// Styling Header
$lastColLetter = Coordinate::stringFromColumnIndex(count($headers));
$sheet->getStyle('A1:' . $lastColLetter . '1')->getFont()->setBold(true);
$sheet->getStyle('A1:' . $lastColLetter . '1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('D9EAD3');

// Populate Product Rows
$rowIndex = 2;
foreach ($products as $p) {
    $col = 1;
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->id);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->name);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->sub_category_relation?->category?->name ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->sub_category_relation?->name ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->brandRelation?->name ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->weight ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->alert_qty ?? 0);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $p->opening_total_stock ?? 0);

    // Warehouse stock columns pre-fill
    $savedWhStocks = is_array($p->opening_warehouse_stocks) ? $p->opening_warehouse_stocks : [];
    foreach ($warehouses as $wh) {
        $whQty = $savedWhStocks[$wh->id] ?? $savedWhStocks[(string)$wh->id] ?? 0;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $rowIndex, $whQty);
    }

    $rowIndex++;
}

// Auto size columns
foreach (range(1, count($headers)) as $colIndex) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
}

// Export to file
$fileName = 'export_products_opening_stock.xlsx';
$outputPath = public_path($fileName);

$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

echo "Export completed successfully! File saved to: {$outputPath} (Total Products: " . count($products) . ")\n";
