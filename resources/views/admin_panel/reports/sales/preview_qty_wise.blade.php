<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Sales Report only Retail Value</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 15px;
        }
        .report-title {
            color: #c2185b;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        .date-range span {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        /* Brand Heading Row */
        .brand-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 0 5px 0;
            font-weight: bold;
            font-size: 13px;
            color: #0d47a1;
        }

        /* Data Row */
        .item-row td {
            border: 1px solid #999;
        }

        /* Total Row */
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #c8e6c9;
            text-align: center;
        }
        .val-box {
            background-color: #cfd8dc;
            text-align: right;
        }

        /* Grand Total Row */
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 10px 6px;
            border-top: 2px solid #000;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
        @include('admin_panel.reports.sales.partials.report_line_styles')
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
        <button id="btnExportExcel" onclick="exportReportToExcel()" style="padding: 10px 25px; background: #2e7d32; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-excel-o"></i> Export to Excel
        </button>
        <button id="btnExportPDF" onclick="exportReportToPDF()" style="padding: 10px 25px; background: #0288d1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-pdf-o"></i> Export to PDF
        </button>
    </div>

    <div id="reportContainer" style="background: #fff; padding: 5px;">
        <div class="report-header">
            <h1 class="report-title">Sales Report only Retail Value</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
            <tr>
                <th width="15%" class="text-left">Category</th>
                <th width="30%" class="text-left">Item Name</th>
                <th width="8%">Qty</th>
                <th width="11%">Retail Price</th>
                <th width="12%">Retail Value</th>
                <th width="11%">Sales Price Avg</th>
                <th width="13%">Sales Value</th>
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
                    <td colspan="7" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $brandName => $items)
                @php
                    $b_qty = 0; 
                    $b_retail_val = 0;
                    $b_sales_val = 0;

                    // Group by product_id AND retail_price so different retail prices stay separate, but same product & retail price sum together
                    $productGroups = $items->groupBy(function($item) {
                        $pId = $item->product_id;
                        $rPrice = (float) ($item->retail_price ?? 0);
                        return $pId . '_' . number_format($rPrice, 2, '.', '');
                    });
                @endphp
                
                <!-- Brand Heading -->
                <tr class="brand-heading-row">
                    <td colspan="7" class="text-left" style="border-top: 2px solid #000; padding-top: 15px; font-weight: bold; color: #0d47a1;">{{ strtoupper($brandName) }}</td>
                </tr>

                    @foreach($productGroups as $group)
                        @php
                            $first = $group->first();
                            $productObj = $first->product;
                            $categoryName = $productObj?->sub_category_relation?->name 
                                         ?? $productObj?->subcategory?->name 
                                         ?? $productObj?->categoryRelation?->name 
                                         ?? '-';
                            $productName = $productObj ? $productObj->name : 'N/A';
                        
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
                        <td class="text-right fw-bold"><b>{{ number_format($sales_value, 0) }}</b></td>
                    </tr>
                @endforeach

                <!-- Brand Total Row -->
                <tr class="total-row">
                    <td colspan="2" class="text-right" style="color: #0d47a1;">{{ $brandName }} Total:</td>
                    <td class="qty-box">{{ number_format($b_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($b_retail_val, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #e1f5fe;">{{ number_format($b_sales_val, 0) }}</td>
                </tr>
                <!-- Separation Gap -->
                <tr style="height: 15px;"><td colspan="7" style="border:none;"></td></tr>

                @php 
                    $g_qty += $b_qty; 
                    $g_retail_val += $b_retail_val; 
                    $g_sales_val += $b_sales_val;
                @endphp
            @endforeach

            <!-- Grand Total -->
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($g_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($g_retail_val, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($g_sales_val, 0) }}</td>
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
            var table = document.getElementById("salesReportTable");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Qty Wise Sales Report"});
            XLSX.writeFile(wb, "Qty_Wise_Sales_Report.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Qty_Wise_Sales_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
