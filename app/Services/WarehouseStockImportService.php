<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WarehouseStockImportService
{
    /**
     * Build Export Spreadsheet with current stocks per warehouse.
     */
    public function buildExportSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Warehouse Stocks');

        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();

        $headers = [
            'Product ID',
            'Product Name',
            'Brand',
            'Shop Stock',
        ];

        foreach ($warehouses as $wh) {
            $headers[] = 'Warehouse Stock (' . $wh->warehouse_name . ' [ID:' . $wh->id . '])';
        }

        foreach ($headers as $colIndex => $header) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        $products = Product::with(['warehouseStocks' => function ($q) {
            $q->where('status', 'Posted');
        }, 'brandRelation'])->orderBy('name')->get();

        $excelRow = 2;
        foreach ($products as $product) {
            $row = [
                $product->id,
                $product->name,
                $product->brandRelation->name ?? '',
                (float) ($product->stock ?? 0),
            ];

            foreach ($warehouses as $wh) {
                $whQty = (float) $product->warehouseStocks
                    ->where('warehouse_id', $wh->id)
                    ->sum('quantity');
                $row[] = $whQty;
            }

            foreach ($row as $colIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($column . $excelRow, $value);
            }
            $excelRow++;
        }

        // Header Styling
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9EAD3');

        foreach (range(1, count($headers)) as $colIndex) {
            $column = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Download Excel file response.
     */
    public function downloadExportResponse()
    {
        $spreadsheet = $this->buildExportSpreadsheet();
        $writer = new Xlsx($spreadsheet);

        $filename = 'warehouse_stock_export_' . date('Y-m-d_H-i') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import stock balances from uploaded Excel / CSV file.
     */
    public function importFromFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return ['imported' => 0, 'errors' => ['File contains no data rows.']];
        }

        $headerRow = array_shift($rows);
        $headerMap = [];
        $warehouseColMap = []; // ColIndex => warehouse_id

        $warehouses = Warehouse::withoutGlobalScopes()->get();
        $whByName = $warehouses->pluck('id', 'warehouse_name')->all();
        $whById = $warehouses->pluck('id', 'id')->all();

        foreach ($headerRow as $colLetter => $headerText) {
            $cleanHeader = trim((string) $headerText);
            if (empty($cleanHeader)) continue;

            $headerMap[strtolower($cleanHeader)] = $colLetter;

            // Check if column is Warehouse Stock
            if (preg_match('/Warehouse Stock \((.*?)\)/i', $cleanHeader, $matches)) {
                $whStr = trim($matches[1]);
                $whId = null;

                if (preg_match('/\[ID:(\d+)\]/i', $whStr, $idMatch)) {
                    $whId = (int) $idMatch[1];
                } elseif (isset($whByName[$whStr])) {
                    $whId = (int) $whByName[$whStr];
                }

                if ($whId && isset($whById[$whId])) {
                    $warehouseColMap[$colLetter] = $whId;
                }
            }
        }

        $idCol = $headerMap['product id'] ?? $headerMap['id'] ?? null;
        $nameCol = $headerMap['product name'] ?? $headerMap['name'] ?? null;
        $shopCol = $headerMap['shop stock'] ?? null;

        if (!$idCol && !$nameCol) {
            return ['imported' => 0, 'errors' => ['Column "Product ID" or "Product Name" is required in Excel.']];
        }

        $imported = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $rowNum++;

                $productId = $idCol ? (int) trim((string) ($row[$idCol] ?? '')) : 0;
                $productName = $nameCol ? trim((string) ($row[$nameCol] ?? '')) : '';

                if (!$productId && empty($productName)) {
                    continue; // Skip empty rows
                }

                $product = null;
                if ($productId) {
                    $product = Product::find($productId);
                }
                if (!$product && !empty($productName)) {
                    $product = Product::where('name', $productName)->first();
                }

                if (!$product) {
                    $errors[] = "Row {$rowNum}: Product not found (ID: {$productId}, Name: '{$productName}')";
                    continue;
                }

                // 1. Update Shop Stock if present in Excel
                if ($shopCol && isset($row[$shopCol]) && $row[$shopCol] !== '') {
                    $shopQty = (float) $row[$shopCol];
                    $product->stock = $shopQty;
                    $product->opening_shop_stock = $shopQty;
                }

                // 2. Update Warehouse Stocks for each warehouse column
                $warehouseStockMap = $product->opening_warehouse_stocks ?? [];
                if (!is_array($warehouseStockMap)) {
                    $warehouseStockMap = [];
                }

                foreach ($warehouseColMap as $colLetter => $whId) {
                    if (isset($row[$colLetter]) && $row[$colLetter] !== '') {
                        $whQty = (float) $row[$colLetter];

                        $whStock = WarehouseStock::firstOrNew([
                            'warehouse_id' => $whId,
                            'product_id'   => $product->id,
                        ]);
                        $whStock->quantity = $whQty;
                        $whStock->status = 'Posted';
                        $whStock->remarks = 'Excel Stock Import';
                        $whStock->save();

                        $warehouseStockMap[(string) $whId] = $whQty;
                    }
                }

                // 3. Recalculate opening total stock & save product
                $whTotal = array_sum(array_map('floatval', $warehouseStockMap));
                $shopStockVal = (float) ($product->stock ?? 0);
                $product->opening_warehouse_stocks = $warehouseStockMap;
                $product->opening_total_stock = $shopStockVal + $whTotal;
                $product->save();

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ['imported' => 0, 'errors' => ['Import error: ' . $e->getMessage()]];
        }

        return [
            'imported' => $imported,
            'skipped'  => count($errors),
            'errors'   => $errors,
        ];
    }
}
