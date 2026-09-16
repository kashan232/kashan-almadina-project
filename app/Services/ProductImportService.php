<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductImportService
{
    public const COLUMN_MAP = [
        'name'                    => 'Product Name',
        'category'                => 'Category',
        'sub_category'            => 'Sub Category',
        'brand'                   => 'Brand',
        'weight'                  => 'Weight',
        'alert_qty'               => 'Alert Qty',
        'opening_stock'           => 'Total Opening Stock',
        'purchase_retail_price'   => 'Purchase Retail Price',
        'purchase_tax_percent'    => 'Purchase Tax %',
        'purchase_discount_percent'=> 'Purchase Disc %',
        'sale_retail_price'       => 'Sale Retail Price',
        'sale_tax_percent'        => 'Sale Tax %',
        'sale_discount_percent'   => 'Sale Disc %',
        'wht_percent'             => 'WHT %',
    ];

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Import');

        $headers = array_values(self::COLUMN_MAP);
        
        // Add dynamic warehouse columns for opening stock
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        foreach ($warehouses as $wh) {
            $headers[] = 'Warehouse Stock (' . $wh->warehouse_name . ')';
        }

        foreach ($headers as $colIndex => $header) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Fetch sample data from DB
        $catSample    = Category::value('name') ?? 'Batteries';
        $subCatSample = Subcategory::value('name') ?? 'Tubular';
        $brandSample  = Brand::value('name') ?? 'Phoenix';

        $sampleRow = [
            'Sample Product HT 55',
            $catSample,
            $subCatSample,
            $brandSample,
            '25 KG',
            5,
            50, // Total opening stock
            10000, // Purchase Retail
            0,     // Purchase Tax %
            0,     // Purchase Disc %
            12000, // Sale Retail
            0,     // Sale Tax %
            0,     // Sale Disc %
            0,     // WHT %
        ];

        // Sample warehouse stocks
        foreach ($warehouses as $wh) {
            $sampleRow[] = 10;
        }

        $excelRow = 2;
        foreach ($sampleRow as $colIndex => $value) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . $excelRow, $value);
        }

        // Header Styling
        $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';
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

    public function downloadTemplateResponse()
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();
        $fileName = 'product_import_template_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function importFromFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Map Headers
        $headerMap = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $val = trim((string) $sheet->getCell([$col, 1])->getValue());
            if (!empty($val)) {
                $headerMap[strtolower($val)] = $col;
            }
        }

        $warehouses = Warehouse::withoutGlobalScopes()->get();
        $whHeaderMap = [];
        foreach ($warehouses as $wh) {
            $whHeaderKey = strtolower('warehouse stock (' . $wh->warehouse_name . ')');
            if (isset($headerMap[$whHeaderKey])) {
                $whHeaderMap[$wh->id] = $headerMap[$whHeaderKey];
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $getVal = function ($headerTitle) use ($sheet, $headerMap, $row) {
                $key = strtolower($headerTitle);
                if (!isset($headerMap[$key])) return null;
                $val = $sheet->getCell([$headerMap[$key], $row])->getValue();
                return is_string($val) ? trim($val) : $val;
            };

            $name = $getVal(self::COLUMN_MAP['name']);
            if (empty($name)) {
                continue; // Skip empty rows
            }

            $catName = $getVal(self::COLUMN_MAP['category']);
            $subCatName = $getVal(self::COLUMN_MAP['sub_category']);
            $brandName = $getVal(self::COLUMN_MAP['brand']);

            // Find or create Category, Subcategory, Brand
            $category = Category::where('name', $catName)->first();
            if (!$category && !empty($catName)) {
                $category = Category::create(['name' => $catName]);
            }

            $subCategory = null;
            if ($category && !empty($subCatName)) {
                $subCategory = Subcategory::where('category_id', $category->id)->where('name', $subCatName)->first();
                if (!$subCategory) {
                    $subCategory = Subcategory::create(['category_id' => $category->id, 'name' => $subCatName]);
                }
            }

            $brand = Brand::where('name', $brandName)->first();
            if (!$brand && !empty($brandName)) {
                $brand = Brand::create(['name' => $brandName]);
            }

            $totalOpeningStock = (float)($getVal(self::COLUMN_MAP['opening_stock']) ?? 0);
            $weight = $getVal(self::COLUMN_MAP['weight']);
            $alertQty = $getVal(self::COLUMN_MAP['alert_qty']);

            // Warehouse stock map for row
            $whStocks = [];
            $whStockTotal = 0;
            foreach ($whHeaderMap as $whId => $colIdx) {
                $wQty = (float)($sheet->getCell([$colIdx, $row])->getValue() ?? 0);
                $whStocks[$whId] = $wQty;
                $whStockTotal += $wQty;
            }

            $shopStock = $totalOpeningStock - $whStockTotal;

            try {
                DB::beginTransaction();

                // Check existing product
                $product = Product::where('name', $name)->first();

                if ($product) {
                    $product->update([
                        'category_id'     => $category?->id ?? $product->category_id,
                        'sub_category_id' => $subCategory?->id ?? $product->sub_category_id,
                        'brand_id'        => $brand?->id ?? $product->brand_id,
                        'weight'          => $weight ?: $product->weight,
                        'alert_qty'       => $alertQty !== null ? $alertQty : $product->alert_qty,
                        'stock'           => $shopStock,
                    ]);
                } else {
                    $product = Product::create([
                        'name'            => $name,
                        'category_id'     => $category?->id,
                        'sub_category_id' => $subCategory?->id,
                        'brand_id'        => $brand?->id,
                        'weight'          => $weight,
                        'alert_qty'       => $alertQty ?: 0,
                        'stock'           => $shopStock,
                        'status'          => 1,
                    ]);
                }

                // Prices
                $purRetail  = (float)($getVal(self::COLUMN_MAP['purchase_retail_price']) ?? 0);
                $purTaxPct  = (float)($getVal(self::COLUMN_MAP['purchase_tax_percent']) ?? 0);
                $purDiscPct = (float)($getVal(self::COLUMN_MAP['purchase_discount_percent']) ?? 0);
                $saleRetail = (float)($getVal(self::COLUMN_MAP['sale_retail_price']) ?? 0);
                $saleTaxPct = (float)($getVal(self::COLUMN_MAP['sale_tax_percent']) ?? 0);
                $saleDiscPct= (float)($getVal(self::COLUMN_MAP['sale_discount_percent']) ?? 0);
                $whtPct     = (float)($getVal(self::COLUMN_MAP['wht_percent']) ?? 0);

                $purTaxAmt = ($purRetail * $purTaxPct) / 100;
                $purDiscAmt = ($purRetail * $purDiscPct) / 100;
                $purNet = $purRetail + $purTaxAmt - $purDiscAmt;

                $saleTaxAmt = ($saleRetail * $saleTaxPct) / 100;
                $saleDiscAmt = ($saleRetail * $saleDiscPct) / 100;
                $saleNet = $saleRetail + $saleTaxAmt - $saleDiscAmt;

                $latestPrice = $product->latestPrice;
                if ($latestPrice) {
                    $latestPrice->update([
                        'purchase_retail_price'    => $purRetail,
                        'purchase_tax_percent'     => $purTaxPct,
                        'purchase_tax_amount'      => $purTaxAmt,
                        'purchase_discount_percent'=> $purDiscPct,
                        'purchase_discount_amount' => $purDiscAmt,
                        'purchase_net_amount'      => $purNet,
                        'sale_retail_price'        => $saleRetail,
                        'sale_tax_percent'         => $saleTaxPct,
                        'sale_tax_amount'          => $saleTaxAmt,
                        'sale_discount_percent'    => $saleDiscPct,
                        'sale_discount_amount'     => $saleDiscAmt,
                        'sale_net_amount'          => $saleNet,
                        'sale_wht_percent'          => $whtPct,
                    ]);
                } else {
                    $product->prices()->create([
                        'purchase_retail_price'    => $purRetail,
                        'purchase_tax_percent'     => $purTaxPct,
                        'purchase_tax_amount'      => $purTaxAmt,
                        'purchase_discount_percent'=> $purDiscPct,
                        'purchase_discount_amount' => $purDiscAmt,
                        'purchase_net_amount'      => $purNet,
                        'sale_retail_price'        => $saleRetail,
                        'sale_tax_percent'         => $saleTaxPct,
                        'sale_tax_amount'          => $saleTaxAmt,
                        'sale_discount_percent'    => $saleDiscPct,
                        'sale_discount_amount'     => $saleDiscAmt,
                        'sale_net_amount'          => $saleNet,
                        'sale_wht_percent'          => $whtPct,
                        'start_date'               => date('Y-m-d'),
                    ]);
                }

                // Warehouse stocks sync
                foreach ($whStocks as $whId => $qty) {
                    WarehouseStock::updateOrCreate(
                        ['warehouse_id' => $whId, 'product_id' => $product->id],
                        ['quantity' => $qty, 'status' => 'Posted', 'remarks' => 'Import Opening Distribution']
                    );
                }

                DB::commit();
                $imported++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped++;
                $errors[] = "Row {$row} ({$name}): " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }
}
