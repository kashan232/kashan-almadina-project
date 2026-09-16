<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockHoldVoucher;
use App\Models\StockHold;
use App\Services\StockHoldPostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockHoldImportService
{
    public const COLUMN_MAP = [
        'party_type'  => 'Party Type',
        'party_name'  => 'Party Name',
        'warehouse'   => 'Warehouse',
        'product'     => 'Product Name',
        'hold_qty'    => 'Hold Qty',
        'entry_date'  => 'Entry Date',
        'remarks'     => 'Remarks',
    ];

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Holds');

        $headers = array_values(self::COLUMN_MAP);
        foreach ($headers as $colIndex => $header) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Fetch actual database sample values
        $customerSample = Customer::where('customer_type', 'Main Customer')->value('customer_name') ?? 'ABC Customer';
        $walkinSample   = Customer::where('customer_type', 'Walking Customer')->value('customer_name') ?? 'Walkin Customer';
        $vendorSample   = Vendor::value('name') ?? 'XYZ Vendor';
        $warehouseSample= Warehouse::value('warehouse_name') ?? 'Main Warehouse';
        $productSample  = Product::value('name') ?? 'Sample Product';
        $todayDate      = date('Y-m-d');

        $sampleRows = [
            ['Customer', $customerSample, $warehouseSample, $productSample, 10, $todayDate, 'Initial hold for customer'],
            ['Walking', $walkinSample, $warehouseSample, $productSample, 5, $todayDate, 'Counter hold balance'],
            ['Vendor', $vendorSample, $warehouseSample, $productSample, 15, $todayDate, 'Vendor return hold'],
        ];

        foreach ($sampleRows as $rowIndex => $sampleRow) {
            $excelRow = $rowIndex + 2;
            foreach ($sampleRow as $colIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($column . $excelRow, $value);
            }
        }

        // Style Header Row
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
        $fileName = 'stock_hold_import_template_' . date('Y_m_d_His') . '.xlsx';

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
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return [
                'imported_vouchers' => 0,
                'imported_items'    => 0,
                'skipped'           => 0,
                'errors'            => ['The uploaded Excel file contains no data rows.'],
            ];
        }

        $headerRow = array_shift($rows);
        $colIndexMap = $this->mapHeadersToColumns($headerRow);

        if (empty($colIndexMap['party_type']) || empty($colIndexMap['party_name']) || empty($colIndexMap['product']) || empty($colIndexMap['hold_qty'])) {
            return [
                'imported_vouchers' => 0,
                'imported_items'    => 0,
                'skipped'           => 0,
                'errors'            => ['Missing required column headers in Excel file (Party Type, Party Name, Product Name, Hold Qty).'],
            ];
        }

        // Cache lookups to prevent excessive queries
        $warehouses = Warehouse::all();
        $products   = Product::all();
        $customers  = Customer::all();
        $vendors    = Vendor::all();

        $importedVouchersCount = 0;
        $importedItemsCount    = 0;
        $skippedCount          = 0;
        $errors                = [];

        DB::beginTransaction();
        try {
            $postingService = app(StockHoldPostingService::class);
            $userId = Auth::id() ?? 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $partyTypeRaw  = trim((string)($row[$colIndexMap['party_type']] ?? ''));
                $partyName     = trim((string)($row[$colIndexMap['party_name']] ?? ''));
                $warehouseName = trim((string)($row[$colIndexMap['warehouse']] ?? ''));
                $productName   = trim((string)($row[$colIndexMap['product']] ?? ''));
                $holdQtyRaw    = trim((string)($row[$colIndexMap['hold_qty']] ?? ''));
                $entryDateRaw  = trim((string)($row[$colIndexMap['entry_date']] ?? ''));
                $remarks       = trim((string)($row[$colIndexMap['remarks']] ?? ''));

                // Skip empty lines
                if ($partyTypeRaw === '' && $partyName === '' && $productName === '' && $holdQtyRaw === '') {
                    continue;
                }

                // Standardize Party Type
                $partyTypeLower = strtolower($partyTypeRaw);
                $partyType = 'customer';
                $customerTypeFilter = null;

                if (str_contains($partyTypeLower, 'vendor')) {
                    $partyType = 'vendor';
                } elseif (str_contains($partyTypeLower, 'walk')) {
                    $partyType = 'walkin';
                    $customerTypeFilter = 'Walking Customer';
                } else {
                    $partyType = 'customer';
                    $customerTypeFilter = 'Main Customer';
                }

                // Resolve Party ID
                $partyId = null;
                if ($partyType === 'vendor') {
                    $vendor = $vendors->first(fn($v) => strcasecmp(trim($v->name), $partyName) === 0);
                    if ($vendor) {
                        $partyId = $vendor->id;
                    }
                } else {
                    $customer = $customers->first(function ($c) use ($partyName, $customerTypeFilter) {
                        $matchName = strcasecmp(trim($c->customer_name), $partyName) === 0;
                        if ($customerTypeFilter) {
                            return $matchName && $c->customer_type === $customerTypeFilter;
                        }
                        return $matchName;
                    });
                    if ($customer) {
                        $partyId = $customer->id;
                    }
                }

                if (!$partyId) {
                    $skippedCount++;
                    $errors[] = "Row {$rowNumber}: Party '{$partyName}' ({$partyTypeRaw}) not found in database.";
                    continue;
                }

                // Resolve Warehouse ID
                $warehouse = null;
                if ($warehouseName !== '') {
                    $warehouse = $warehouses->first(fn($w) => strcasecmp(trim($w->warehouse_name), $warehouseName) === 0);
                }
                if (!$warehouse) {
                    $warehouse = $warehouses->first(); // Default to first warehouse if specified is not found
                }
                if (!$warehouse) {
                    $skippedCount++;
                    $errors[] = "Row {$rowNumber}: No valid warehouse found.";
                    continue;
                }

                // Resolve Product ID
                $product = $products->first(fn($p) => strcasecmp(trim($p->name), $productName) === 0);
                if (!$product) {
                    $skippedCount++;
                    $errors[] = "Row {$rowNumber}: Product '{$productName}' not found in database.";
                    continue;
                }

                // Quantity check
                $holdQty = (float) $holdQtyRaw;
                if ($holdQty <= 0) {
                    $skippedCount++;
                    $errors[] = "Row {$rowNumber}: Invalid hold quantity '{$holdQtyRaw}'. Must be greater than 0.";
                    continue;
                }

                // Entry Date
                $entryDate = date('Y-m-d');
                if (!empty($entryDateRaw)) {
                    $parsedDate = date('Y-m-d', strtotime($entryDateRaw));
                    if ($parsedDate && $parsedDate !== '1970-01-01') {
                        $entryDate = $parsedDate;
                    }
                }

                // Create Posted Stock Hold Voucher & Item
                $voucherNo = StockHoldVoucher::generateVoucherNo();
                $voucher = StockHoldVoucher::create([
                    'voucher_no'     => $voucherNo,
                    'date'           => $entryDate,
                    'entry_date'     => $entryDate,
                    'party_type'     => $partyType,
                    'party_id'       => $partyId,
                    'warehouse_id'   => $warehouse->id,
                    'remarks'        => $remarks ?: 'Imported via Excel',
                    'status'         => 'Posted',
                    'created_by'     => $userId,
                    'user_group_ids' => Auth::user()?->userGroups()?->pluck('user_groups.id')->toArray() ?? [],
                ]);

                StockHold::create([
                    'stock_hold_voucher_id' => $voucher->id,
                    'entry_date'            => $entryDate,
                    'party_type'            => $partyType,
                    'party_id'              => $partyId,
                    'warehouse_id'          => $warehouse->id,
                    'product_id'            => $product->id,
                    'sale_qty'              => 0,
                    'hold_qty'              => $holdQty,
                    'status'                => 0, // 0 = Active Hold item in database schema
                    'created_by'            => $userId,
                    'user_group_ids'        => Auth::user()?->userGroups()?->pluck('user_groups.id')->toArray() ?? [],
                ]);

                // Sync posting ledger/stock if needed
                $postingService->postVoucher($voucher->id);

                $importedVouchersCount++;
                $importedItemsCount++;
            }

            DB::commit();

            return [
                'imported_vouchers' => $importedVouchersCount,
                'imported_items'    => $importedItemsCount,
                'skipped'           => $skippedCount,
                'errors'            => $errors,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'imported_vouchers' => 0,
                'imported_items'    => 0,
                'skipped'           => 0,
                'errors'            => ['Import failed due to server error: ' . $e->getMessage()],
            ];
        }
    }

    private function mapHeadersToColumns(array $headerRow): array
    {
        $colIndexMap = [];
        $headerMap = array_change_key_case(array_flip(self::COLUMN_MAP), CASE_LOWER);

        foreach ($headerRow as $colLetter => $rawHeader) {
            $normalizedHeader = strtolower(trim((string)$rawHeader));
            if (isset($headerMap[$normalizedHeader])) {
                $colIndexMap[$headerMap[$normalizedHeader]] = $colLetter;
            }
        }

        return $colIndexMap;
    }
}
