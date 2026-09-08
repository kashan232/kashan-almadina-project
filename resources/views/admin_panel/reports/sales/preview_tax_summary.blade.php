<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Tax Summary Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
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
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 15px;
            font-size: 10px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .category-total-row {
            background-color: #cfd8dc;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #9e9e9e;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
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
            <div class="report-title">Tax Summary Report</div>
            <div class="date-range">
                From: {{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }} To: {{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="15%">Type</th>
                    <th width="30%" class="text-left">Party Name</th>
                    <th width="15%">CNIC / NTN</th>
                    <th width="10%">Qty</th>
                    <th width="10%">Retail Value</th>
                    <th width="10%">Tax Amount</th>
                    <th width="10%">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $g_qty = 0; 
                    $g_retail = 0; 
                    $g_tax = 0; 
                    $g_inclusive = 0; 
                @endphp

                @foreach($grouped as $type => $customers)
                    @php
                        $t_qty = 0; $t_retail = 0; $t_tax = 0; $t_inclusive = 0;
                    @endphp

                    @foreach($customers as $customerId => $items)
                        @php
                            $firstItem = $items->first();
                            $customer = $firstItem->sale->customer;
                            if (!$customer) {
                                $customer = (object)[
                                    'customer_name' => 'CASH CUSTOMER',
                                    'cnic' => 'N/A'
                                ];
                            }
                            
                            $c_qty = 0;
                            $c_retail = 0;
                            $c_tax = 0;
                            $c_inclusive = 0;

                            foreach($items as $item) {
                                $q = $item->sales_qty;
                                $r = ($item->retail_price ?? 0) * $q;
                                $s = $item->amount;
                                $t = $r * 0.18;
                                $inc = $r + $t;

                                $c_qty += $q;
                                $c_retail += $r;
                                $c_tax += $t;
                                $c_inclusive += $inc;
                            }

                            $t_qty += $c_qty;
                            $t_retail += $c_retail;
                            $t_tax += $c_tax;
                            $t_inclusive += $c_inclusive;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $type }}</td>
                            <td class="text-left">{{ $customer->customer_name }}</td>
                            <td class="text-center">{{ $customer->cnic }}</td>
                            <td class="text-center">{{ number_format($c_qty) }}</td>
                            <td class="text-right">{{ number_format($c_retail, 2) }}</td>
                            <td class="text-right">{{ number_format($c_tax, 2) }}</td>
                            <td class="text-right">{{ number_format($c_inclusive, 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="category-total-row">
                        <td colspan="3" class="text-right">Total {{ $type }}.</td>
                        <td class="text-center">{{ number_format($t_qty) }}</td>
                        <td class="text-right">{{ number_format($t_retail, 2) }}</td>
                        <td class="text-right">{{ number_format($t_tax, 2) }}</td>
                        <td class="text-right">{{ number_format($t_inclusive, 2) }}</td>
                    </tr>

                    @php
                        $g_qty += $t_qty;
                        $g_retail += $t_retail;
                        $g_tax += $t_tax;
                        $g_inclusive += $t_inclusive;
                    @endphp
                @endforeach

                <tr class="grand-total-row">
                    <td colspan="3" class="text-right">Grand Total</td>
                    <td class="text-center">{{ number_format($g_qty) }}</td>
                    <td class="text-right">{{ number_format($g_retail, 2) }}</td>
                    <td class="text-right">{{ number_format($g_tax, 2) }}</td>
                    <td class="text-right">{{ number_format($g_inclusive, 2) }}</td>
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
            var wb = XLSX.utils.table_to_book(table, {sheet: "Tax Summary Report"});
            XLSX.writeFile(wb, "Tax_Summary_Report.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Tax_Summary_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
