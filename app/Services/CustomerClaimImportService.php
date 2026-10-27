<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\CustomerClaim;
use App\Models\Account;
use App\Http\Controllers\CustomerClaimController;
use App\Services\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CustomerClaimImportService
{
    public const COLUMN_MAP = [
        'claim_type'                 => 'Claim Type',
        'claim_date'                 => 'Claim Date',
        'party_type'                 => 'Party Type',
        'party_name'                 => 'Customer / Party Name',
        'product_name'               => 'Claimed Product Name',
        'claim_warehouse'            => 'Claim Warehouse',
        'original_warehouse'         => 'Original Warehouse / Shop',
        'card_no'                    => 'Warranty Card No',
        'mfg_date'                   => 'Mfg Date / Code',
        'fault_found'                => 'Fault Found',
        'claim_income'               => 'Claim Amount / Fee',
        'replacement_product'        => 'Replacement Product (Credit Note)',
        'replacement_from_warehouse' => 'Replacement From Warehouse',
        'remarks'                    => 'Remarks',
    ];

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Claims');

        $headers = array_values(self::COLUMN_MAP);
        foreach ($headers as $colIndex => $header) {
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Fetch actual real samples from DB
        $customerSample = Customer::where('status', 1)->value('customer_name') ?? 'ABC Customer';
        $productSample1 = Product::where('status', 1)->value('name') ?? 'Battery 100AH';
        $productSample2 = Product::where('status', 1)->skip(1)->value('name') ?? 'Battery 120AH';
        $claimWarehouse = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->value('warehouse_name') ?? 'Claim Hyd';
        $todayDate      = date('Y-m-d');

        // Sample rows demonstrating different claim types
        $sampleRows = [
            [
                'Item Return',
                $todayDate,
                'Customer',
                $customerSample,
                $productSample1,
                $claimWarehouse,
                'Shop',
                'WC-89211',
                '06/25',
                'Cell Dead',
                0,
                '',
                '',
                'Customer return claim',
            ],
            [
                'Credit Note',
                $todayDate,
                'Customer',
                $customerSample,
                $productSample1,
                $claimWarehouse,
                'Shop',
                'WC-89212',
                '07/25',
                'Gravity Low / Backup Issue',
                500,
                $productSample2,
                'Shop',
                'Replacement battery issued on spot',
            ],
            [
                'Claim Hold',
                $todayDate,
                'Customer',
                $customerSample,
                $productSample1,
                $claimWarehouse,
                'Shop',
                'WC-89213',
                '08/25',
                'Testing Under Observation',
                0,
                '',
                '',
                'Held for laboratory inspection',
            ],
        ];

        foreach ($sampleRows as $rowIndex => $sampleRow) {
            $excelRow = $rowIndex + 2;
            foreach ($sampleRow as $colIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($column . $excelRow, $value);
            }
        }

        // Styling
        $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2A5C8A');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $column = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function downloadTemplateResponse()
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();
        $fileName = 'customer_claim_import_template_' . date('Y_m_d_His') . '.xlsx';

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

    public function importFromFile(string $filePath, int $userId, string $targetStatus = 'Posted'): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return [
                'imported' => 0,
                'errors' => ['Excel file is empty or missing headers.'],
            ];
        }

        // Header mapping
        $headerRow = array_shift($rows);
        $columnIndexMap = [];
        foreach ($headerRow as $colLetter => $headerText) {
            $cleanHeader = strtolower(trim((string) $headerText));
            foreach (self::COLUMN_MAP as $fieldKey => $label) {
                if (strtolower($label) === $cleanHeader || str_replace(' ', '', strtolower($label)) === str_replace(' ', '', $cleanHeader)) {
                    $columnIndexMap[$fieldKey] = $colLetter;
                    break;
                }
            }
        }

        // Check essential columns
        $essential = ['claim_type', 'party_name', 'product_name'];
        foreach ($essential as $reqField) {
            if (!isset($columnIndexMap[$reqField])) {
                return [
                    'imported' => 0,
                    'errors' => ["Missing required column header: " . self::COLUMN_MAP[$reqField]],
                ];
            }
        }

        // Pre-fetch caches for fast lookup
        $customers = Customer::withoutGlobalScopes()->get();
        $vendors = Vendor::withoutGlobalScopes()->get();
        $products = Product::withoutGlobalScopes()->get();
        $warehouses = Warehouse::withoutGlobalScopes()->get();
        $claimWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->get();

        $controller = app(CustomerClaimController::class);
        $ledgerService = app(PartyLedgerService::class);

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            // Skip entirely blank rows
            $hasData = false;
            foreach ($row as $cell) {
                if (!empty(trim((string)$cell))) {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            $claimTypeRaw = trim((string)($row[$columnIndexMap['claim_type']] ?? ''));
            $partyNameRaw = trim((string)($row[$columnIndexMap['party_name']] ?? ''));
            $productNameRaw = trim((string)($row[$columnIndexMap['product_name']] ?? ''));

            if (empty($claimTypeRaw) || empty($partyNameRaw) || empty($productNameRaw)) {
                $errors[] = "Row {$rowNumber}: Claim Type, Party Name, and Product Name are required.";
                continue;
            }

            // 1. Resolve Claim Type
            $claimTypeLower = strtolower($claimTypeRaw);
            if (str_contains($claimTypeLower, 'credit') || str_contains($claimTypeLower, 'replace')) {
                $claimType = 'credit_note';
            } elseif (str_contains($claimTypeLower, 'hold')) {
                $claimType = 'claim_hold';
            } else {
                $claimType = 'item_return';
            }

            // 2. Resolve Party Type & Party ID
            $partyTypeRaw = isset($columnIndexMap['party_type']) ? strtolower(trim((string)($row[$columnIndexMap['party_type']] ?? ''))) : 'customer';
            $partyType = 'customer';
            if (str_contains($partyTypeRaw, 'vendor')) {
                $partyType = 'vendor';
            } elseif (str_contains($partyTypeRaw, 'walk')) {
                $partyType = 'walkin';
            }

            $party = null;
            if ($partyType === 'vendor') {
                if (is_numeric($partyNameRaw)) {
                    $party = $vendors->firstWhere('id', (int)$partyNameRaw);
                }
                if (!$party) {
                    $party = $vendors->first(fn($v) => strcasecmp(trim($v->name), $partyNameRaw) === 0);
                }
                if (!$party) {
                    $party = $vendors->first(fn($v) => stripos($v->name, $partyNameRaw) !== false);
                }
                if (!$party) {
                    $errors[] = "Row {$rowNumber}: Vendor '{$partyNameRaw}' not found in database.";
                    continue;
                }
            } else {
                if (is_numeric($partyNameRaw)) {
                    $party = $customers->firstWhere('id', (int)$partyNameRaw);
                }
                if (!$party) {
                    $party = $customers->first(fn($c) => strcasecmp(trim($c->customer_name), $partyNameRaw) === 0);
                }
                if (!$party) {
                    $party = $customers->first(fn($c) => stripos($c->customer_name, $partyNameRaw) !== false);
                }
                if (!$party) {
                    $errors[] = "Row {$rowNumber}: Customer '{$partyNameRaw}' not found in database.";
                    continue;
                }
            }
            $partyId = $party->id;

            // 3. Resolve Product
            $product = null;
            if (is_numeric($productNameRaw)) {
                $product = $products->firstWhere('id', (int)$productNameRaw);
            }
            if (!$product) {
                $product = $products->first(fn($p) => strcasecmp(trim($p->name), $productNameRaw) === 0);
            }
            if (!$product) {
                $product = $products->first(fn($p) => stripos($p->name, $productNameRaw) !== false);
            }
            if (!$product) {
                $errors[] = "Row {$rowNumber}: Product '{$productNameRaw}' not found in database.";
                continue;
            }

            // 4. Resolve Claim Warehouse
            $claimWhRaw = isset($columnIndexMap['claim_warehouse']) ? trim((string)($row[$columnIndexMap['claim_warehouse']] ?? '')) : '';
            $claimWh = null;
            if (!empty($claimWhRaw)) {
                if (is_numeric($claimWhRaw)) {
                    $claimWh = $claimWarehouses->firstWhere('id', (int)$claimWhRaw);
                }
                if (!$claimWh) {
                    $claimWh = $claimWarehouses->first(fn($w) => strcasecmp(trim($w->warehouse_name), $claimWhRaw) === 0);
                }
            }
            if (!$claimWh) {
                $claimWh = $claimWarehouses->first();
            }
            if (!$claimWh) {
                $errors[] = "Row {$rowNumber}: No valid Customer Claim Warehouse found in system.";
                continue;
            }

            // 5. Resolve Original Warehouse
            $origWhRaw = isset($columnIndexMap['original_warehouse']) ? trim((string)($row[$columnIndexMap['original_warehouse']] ?? '')) : '';
            $originalWhId = 0; // Default to Shop (0)
            if (!empty($origWhRaw) && strtolower($origWhRaw) !== 'shop' && $origWhRaw !== '0') {
                $ow = is_numeric($origWhRaw) 
                    ? $warehouses->firstWhere('id', (int)$origWhRaw) 
                    : $warehouses->first(fn($w) => strcasecmp(trim($w->warehouse_name), $origWhRaw) === 0);
                if ($ow) {
                    $originalWhId = $ow->id;
                }
            }

            // 6. Resolve Replacement Product & Warehouse (if Credit Note)
            $replacementProductId = null;
            $replacementFromWhId = 0;
            $replacementRetailPrice = 0;
            $replacementSalesPrice = 0;

            if ($claimType === 'credit_note') {
                $repProdRaw = isset($columnIndexMap['replacement_product']) ? trim((string)($row[$columnIndexMap['replacement_product']] ?? '')) : '';
                if (!empty($repProdRaw)) {
                    $repProd = is_numeric($repProdRaw) 
                        ? $products->firstWhere('id', (int)$repProdRaw) 
                        : $products->first(fn($p) => strcasecmp(trim($p->name), $repProdRaw) === 0 || stripos($p->name, $repProdRaw) !== false);
                    if ($repProd) {
                        $replacementProductId = $repProd->id;
                        $replacementRetailPrice = (float)($repProd->retail_price ?? $repProd->price ?? 0);
                        $replacementSalesPrice = (float)($repProd->price ?? $repProd->retail_price ?? 0);
                    }
                }
                if (!$replacementProductId) {
                    // Fallback to same product
                    $replacementProductId = $product->id;
                    $replacementRetailPrice = (float)($product->retail_price ?? $product->price ?? 0);
                    $replacementSalesPrice = (float)($product->price ?? $product->retail_price ?? 0);
                }

                $repWhRaw = isset($columnIndexMap['replacement_from_warehouse']) ? trim((string)($row[$columnIndexMap['replacement_from_warehouse']] ?? '')) : '';
                if (!empty($repWhRaw) && strtolower($repWhRaw) !== 'shop' && $repWhRaw !== '0') {
                    $rw = is_numeric($repWhRaw) 
                        ? $warehouses->firstWhere('id', (int)$repWhRaw) 
                        : $warehouses->first(fn($w) => strcasecmp(trim($w->warehouse_name), $repWhRaw) === 0);
                    if ($rw) {
                        $replacementFromWhId = $rw->id;
                    }
                }
            }

            // 7. Parse Date
            $rawDate = isset($columnIndexMap['claim_date']) ? $row[$columnIndexMap['claim_date']] : null;
            $claimDate = $this->parseExcelDate($rawDate);

            // Other fields
            $cardNo = isset($columnIndexMap['card_no']) ? trim((string)($row[$columnIndexMap['card_no']] ?? '')) : null;
            $mfgDate = isset($columnIndexMap['mfg_date']) ? trim((string)($row[$columnIndexMap['mfg_date']] ?? '')) : null;
            $faultFound = isset($columnIndexMap['fault_found']) ? trim((string)($row[$columnIndexMap['fault_found']] ?? '')) : null;
            $claimIncome = isset($columnIndexMap['claim_income']) ? (float)str_replace(',', '', (string)($row[$columnIndexMap['claim_income']] ?? 0)) : 0;
            $remarks = isset($columnIndexMap['remarks']) ? trim((string)($row[$columnIndexMap['remarks']] ?? '')) : 'Imported from Excel';

            $retailPrice = (float)($product->retail_price ?? $product->price ?? 0);
            $salesPrice = (float)($product->price ?? $product->retail_price ?? 0);

            // Save inside transaction
            try {
                DB::beginTransaction();

                $claim = new CustomerClaim();
                $claim->claim_no = CustomerClaim::generateClaimNo();
                $claim->entry_date = $claimDate;
                $claim->entry_time = date('H:i:s');
                $claim->claim_date = $claimDate;
                $claim->claim_type = $claimType;
                $claim->party_type = $partyType;
                $claim->party_id = $partyId;
                $claim->product_id = $product->id;
                $claim->mfg_date = $mfgDate;
                $claim->sales_price = $salesPrice;
                $claim->retail_price = $retailPrice;
                $claim->card_no = $cardNo;
                $claim->bill_date = $claimDate;
                $claim->original_warehouse_id = $originalWhId;
                $claim->claim_warehouse_id = $claimWh->id;
                $claim->claim_income = $claimIncome;
                $claim->cash_account_id = $controller->resolveDefaultCashAccountId();
                $claim->income_account_id = $controller->resolveDefaultIncomeAccountId();
                $claim->fault_found = $faultFound;
                $claim->remarks = $remarks;

                if ($claimType === 'credit_note') {
                    $claim->replacement_product_id = $replacementProductId;
                    $claim->replacement_retail_price = $replacementRetailPrice;
                    $claim->replacement_sales_price = $replacementSalesPrice;
                    $claim->replacement_from_warehouse_id = $replacementFromWhId;
                    $claim->replacement_to_warehouse_id = $claimWh->id;
                }

                $claim->status = ($targetStatus === 'Draft') ? 'Draft' : 'Posted';
                $claim->created_by = $userId;
                $claim->save();

                if ($claim->status === 'Posted') {
                    $controller->syncInventory($claim);
                    $ledgerService->postCustomerClaim($claim);
                }

                DB::commit();
                $imported++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Row {$rowNumber} (" . ($partyNameRaw ?: 'N/A') . "): " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    private function parseExcelDate($raw): string
    {
        if (empty($raw)) {
            return date('Y-m-d');
        }

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        $str = trim((string) $raw);
        $ts = strtotime(str_replace('/', '-', $str));
        if ($ts && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return date('Y-m-d');
    }
}
