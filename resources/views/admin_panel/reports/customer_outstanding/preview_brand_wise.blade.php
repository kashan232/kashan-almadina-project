<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Customer Outstanding — Brand &amp; Item Wise</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 6mm;
            background-color: #fff;
        }
        .no-print {
            position: sticky;
            top: 0;
            z-index: 9999;
            padding: 10px 15px;
            background: #2a2d32;
            color: #fff;
            border-bottom: 1px solid #1a1c1e;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            margin: -6mm -6mm 15px -6mm;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            padding: 6px 16px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            color: #fff;
        }
        .btn-print { background: #c2185b; }
        .btn-print:hover { background: #ad1457; }
        .btn-excel { background: #2e7d32; }
        .btn-excel:hover { background: #1b5e20; }
        .btn-pdf { background: #0288d1; }
        .btn-pdf:hover { background: #0277bd; }
        .btn-close { background: #616161; }
        .btn-close:hover { background: #424242; }

        .company-header {
            text-align: center;
            margin-bottom: 3px;
        }
        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: #0d47a1;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #c2185b;
            font-size: 16px;
            font-weight: bold;
            margin: 2px 0;
        }
        .date-range {
            text-align: center;
            font-size: 10.5px;
            font-weight: bold;
            margin-top: 3px;
            color: #333;
        }
        .date-range span {
            text-decoration: underline;
        }
        .generated-date {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 15px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 5px 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 3px 5px;
            vertical-align: middle;
            font-size: 9.5px;
        }

        /* Brand Heading Row */
        .brand-heading-row td {
            background-color: #e8eaf6 !important;
            border-top: 2px solid #000 !important;
            border-bottom: 1px solid #3f51b5 !important;
            padding: 7px 8px;
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Data Row */
        .item-row:nth-child(even) {
            background-color: #fafafa;
        }

        /* Brand Total Row */
        .brand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 6px;
            background-color: #f5f5f5;
        }
        .qty-box {
            background-color: #c8e6c9 !important;
            text-align: center;
            font-weight: bold;
        }
        .val-box {
            background-color: #cfd8dc !important;
            text-align: right;
            font-weight: bold;
        }
        .sales-val-box {
            background-color: #e1f5fe !important;
            text-align: right;
            font-weight: bold;
        }

        /* Grand Total Row */
        .grand-total-row td {
            font-weight: bold;
            font-size: 11px;
            padding: 8px 6px;
            border-top: 3px double #000 !important;
            border-bottom: 3px double #000 !important;
            background-color: #eceff1 !important;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div style="font-weight: bold; font-size: 13px;">Customer Outstanding — Brand Wise</div>
        <div class="btn-group">
            <button class="btn-action btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print Report</button>
            <button class="btn-action btn-excel" id="btnExportExcel" onclick="exportReportToExcel()"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
            <button class="btn-action btn-pdf" id="btnExportPDF" onclick="exportReportToPDF()"><i class="fa fa-file-pdf-o"></i> Export to PDF</button>
            <button class="btn-action btn-close" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">Close</button>
        </div>
    </div>

    <div id="reportContainer">
        <div class="company-header">
            <h1 class="company-title">AL-MADINA TRADERS</h1>
        </div>
        <div class="report-header">
            <div class="report-title">Customer Outstanding — Brand &amp; Item Wise</div>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
                &nbsp;&nbsp;To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
            <div class="generated-date">Generated: {{ $generated_at->format('d-m-Y h:i A') }}</div>
        </div>

        <table id="outstandingBrandWiseTable">
            <thead>
                <tr>
                    <th width="15%" class="text-left">Category</th>
                    <th width="32%" class="text-left">Item Name</th>
                    <th width="8%">Qty</th>
                    <th width="11%" class="text-right">Retail Price</th>
                    <th width="12%" class="text-right">Retail Value</th>
                    <th width="10%" class="text-right">Sales Price Avg</th>
                    <th width="12%" class="text-right">Sales Value</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $g_qty = 0;
                    $g_retail_val = 0;
                    $g_sales_val = 0;
                @endphp

                @if($grouped->isEmpty())
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; font-size: 12px; color: #777;">No Data Found for Selected Range</td>
                    </tr>
                @endif

                @foreach($grouped as $brandId => $items)
                    @php
                        $brandName = isset($brands) && $brands->has($brandId)
                            ? $brands->get($brandId)->name
                            : ($brandId == 0 ? 'Other' : 'Brand #' . $brandId);

                        $b_qty = 0;
                        $b_retail_val = 0;
                        $b_sales_val = 0;

                        // Group by product_id AND retail_price so different retail prices stay separate
                        $productGroups = $items->groupBy(function($item) {
                            $pId = $item->product_id;
                            $rPrice = (float) ($item->retail_price ?? 0);
                            return $pId . '_' . number_format($rPrice, 2, '.', '');
                        });

                        // Strict Sorting: Level 1 = Subcategory ID (sub_category_id ASC), Level 2 = Item ID (product_id ASC)
                        $sortedGroups = $productGroups->sort(function ($groupA, $groupB) {
                            $firstA = $groupA->first();
                            $firstB = $groupB->first();

                            $subCatA = (int) ($firstA->product?->sub_category_id ?? 999999);
                            $subCatB = (int) ($firstB->product?->sub_category_id ?? 999999);

                            if ($subCatA !== $subCatB) {
                                return $subCatA <=> $subCatB;
                            }

                            $prodIdA = (int) ($firstA->product_id ?? 0);
                            $prodIdB = (int) ($firstB->product_id ?? 0);

                            return $prodIdA <=> $prodIdB;
                        });
                    @endphp

                    <!-- Brand Heading Row -->
                    <tr class="brand-heading-row">
                        <td colspan="7" class="text-left">
                            {{ strtoupper($brandName) }}
                        </td>
                    </tr>

                    @foreach($sortedGroups as $group)
                        @php
                            $first = $group->first();
                            $productObj = $first->product;
                            $categoryName = $productObj?->sub_category_relation?->name 
                                         ?? $productObj?->subcategory?->name 
                                         ?? $productObj?->categoryRelation?->name 
                                         ?? $productObj?->category?->name 
                                         ?? '-';
                            $productName = $productObj ? $productObj->name : ('Product #' . $first->product_id);

                            $qty = $group->sum('sales_qty');
                            $price = (float) ($first->retail_price ?? 0);
                            $retail_value = $qty * $price;

                            $sales_value = $group->sum('amount');
                            $sales_price_avg = $qty != 0 ? abs($sales_value / $qty) : 0;

                            $b_qty += $qty;
                            $b_retail_val += $retail_value;
                            $b_sales_val += $sales_value;
                        @endphp
                        <tr class="item-row">
                            <td class="text-left">{{ $categoryName }}</td>
                            <td class="text-left">{{ $productName }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($price, 0) }}</td>
                            <td class="text-right"><b>{{ number_format($retail_value, 0) }}</b></td>
                            <td class="text-right">{{ number_format($sales_price_avg, 0) }}</td>
                            <td class="text-right"><b>{{ number_format($sales_value, 0) }}</b></td>
                        </tr>
                    @endforeach

                    <!-- Brand Total Row -->
                    <tr class="brand-total-row">
                        <td colspan="2" class="text-right" style="color: #0d47a1;">{{ strtoupper($brandName) }} Total:</td>
                        <td class="qty-box">{{ number_format($b_qty) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($b_retail_val, 0) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="sales-val-box">{{ number_format($b_sales_val, 0) }}</td>
                    </tr>

                    <!-- Separation Gap Between Brands -->
                    <tr style="height: 10px;"><td colspan="7" style="border:none; background:#fff;"></td></tr>

                    @php
                        $g_qty += $b_qty;
                        $g_retail_val += $b_retail_val;
                        $g_sales_val += $b_sales_val;
                    @endphp
                @endforeach

                <!-- Grand Total Row -->
                <tr class="grand-total-row">
                    <td colspan="2" class="text-right">Grand Total:</td>
                    <td class="qty-box" style="background-color: #cfd8dc !important;">{{ number_format($g_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #bbdefb !important;">{{ number_format($g_retail_val, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="sales-val-box" style="background-color: #bbdefb !important;">{{ number_format($g_sales_val, 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- html2pdf for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function exportReportToExcel() {
            var table = document.getElementById("outstandingBrandWiseTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "Brand Wise Outstanding"});
            XLSX.writeFile(wb, "Customer_Outstanding_Brand_Wise.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            if (!element) return;
            var opt = {
                margin:       [6, 6, 6, 6],
                filename:     'Customer_Outstanding_Brand_Wise.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
