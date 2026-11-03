<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Customer Outstanding Balance — Detailed</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
            background: #525659;
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
        }
        .no-print .btn-group button {
            padding: 7px 16px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .btn-print { background: #e91e63; color: #fff; }
        .btn-print:hover { background: #d81b60; }
        .btn-excel { background: #2e7d32; color: #fff; margin-left: 8px; }
        .btn-excel:hover { background: #1b5e20; }
        .btn-pdf { background: #0288d1; color: #fff; margin-left: 8px; }
        .btn-pdf:hover { background: #0277bd; }
        .btn-close { background: #616161; color: #fff; margin-left: 8px; }
        .page-badge {
            background: #1976d2;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        /* 📄 A4 Landscape Sheet Preview Styling */
        .page-sheet {
            width: 297mm;
            margin: 15px auto;
            background: #ffffff;
            padding: 4mm 6mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            position: relative;
            box-sizing: border-box;
        }
        
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 10px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 17px;
            font-weight: bold;
            margin: 0;
        }
        .report-sub {
            font-size: 11px;
            font-weight: bold;
            color: #333;
        }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 10px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }
        th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 3px 1.5px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            line-height: 1.1;
        }
        td {
            border: 1px solid #666;
            padding: 2.5px 2px;
            font-size: 8px;
            line-height: 1.1;
        }
        .sno { width: 2.5%; text-align: center; }
        .type { width: 5.5%; text-align: center; }
        .customer { width: 14%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .num { text-align: right; white-space: nowrap; font-size: 8px; }
        .period-head { background: #eceff1; }
        .col-green { color: #1b5e20; font-weight: 600; }
        .col-red { color: #b71c1c; font-weight: 600; }
        .grand-row td {
            font-weight: bold;
            background: #eceff1;
            border-top: 2px solid #000;
        }
        .grand-label { text-align: right; }
        .empty-msg { text-align: center; padding: 24px; font-size: 11px; }

        @media print {
            @page { 
                size: A4 landscape; 
                margin: 4mm 5mm; 
            }
            body { 
                background: #fff !important; 
                margin: 0 !important; 
                padding: 0 !important; 
            }
            .no-print { 
                display: none !important; 
            }
            .page-sheet {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
            }
            thead {
                display: table-header-group !important; /* Repeats table header at top of every printed page */
            }
            tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .grand-row {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="page-badge" id="pageCountBadge">📄 Estimated Pages: Computing...</div>
        <div class="btn-group">
            <button class="btn-print" onclick="window.print()">Print Report</button>
            <button class="btn-excel" id="btnExportExcel" onclick="exportReportToExcel()">Export to Excel</button>
            <button class="btn-pdf" id="btnExportPDF" onclick="exportReportToPDF()">Export to PDF</button>
            <button class="btn-close" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">Close</button>
        </div>
    </div>

    @php
        $fmt = function($v, $isDeduction = false) {
            $val = (float)$v;
            if (abs($val) < 0.0001) return '';
            $displayVal = abs($val);
            if ($isDeduction) {
                return '-' . number_format($displayVal, 0);
            }
            return ($val < 0 ? '-' : '') . number_format($displayVal, 0);
        };
        $fromLabel = $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '';
        $toLabel = $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '';
        $periodCols = [
            ['key' => 'sales', 'head' => 'Sales', 'class' => '', 'is_deduction' => false],
            ['key' => 'c_rep', 'head' => 'C. Rep', 'class' => '', 'is_deduction' => false],
            ['key' => 'payment', 'head' => 'Payment', 'class' => 'col-green', 'is_deduction' => false],
            ['key' => 'income', 'head' => 'Income', 'class' => 'col-green', 'is_deduction' => false],
            ['key' => 'jv_dr', 'head' => 'JV-DR.', 'class' => '', 'is_deduction' => false],
            ['key' => 'av_dr', 'head' => 'AV-DR.', 'class' => '', 'is_deduction' => false],
            ['key' => 'cir', 'head' => 'CIR', 'class' => 'col-green', 'is_deduction' => false],
            ['key' => 'purchase', 'head' => 'Purchase', 'class' => 'col-red', 'is_deduction' => true],
            ['key' => 'pur_ret', 'head' => 'Pur Ret', 'class' => 'col-green', 'is_deduction' => false],
            ['key' => 's_ret', 'head' => 'S. Ret', 'class' => 'col-red', 'is_deduction' => true],
            ['key' => 'clm_cn', 'head' => 'CLM CN', 'class' => 'col-red', 'is_deduction' => true],
            ['key' => 'receipts', 'head' => 'Receipts', 'class' => 'col-red', 'is_deduction' => true],
            ['key' => 'exp_dis', 'head' => 'Exp / Dis', 'class' => 'col-green', 'is_deduction' => true],
            ['key' => 'jv_cr', 'head' => 'JV-CR.', 'class' => 'col-green', 'is_deduction' => true],
            ['key' => 'av_cr', 'head' => 'AV-CR.', 'class' => 'col-green', 'is_deduction' => true],
        ];
    @endphp

    <div class="page-sheet" id="reportContainer">
        @if(!empty($rows))
        <table id="outstandingReportTable">
            <thead>
                <tr>
                    <th class="sno" rowspan="2">S#</th>
                    <th class="type" rowspan="2">Type</th>
                    <th class="customer" rowspan="2">Party Name</th>
                    <th rowspan="2">Opening Balance</th>
                    <th colspan="{{ count($periodCols) + 1 }}" class="period-head">Between {{ $fromLabel }} To. {{ $toLabel }}</th>
                </tr>
                <tr>
                    @foreach($periodCols as $col)
                    <th class="{{ $col['class'] }}">{{ $col['head'] }}</th>
                    @endforeach
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td class="sno">{{ $i + 1 }}</td>
                    <td class="type">{{ $row['party_type_label'] ?? 'Customer' }}</td>
                    <td class="customer" title="{{ $row['party_name'] ?? $row['customer_name'] }}">{{ $row['party_name'] ?? $row['customer_name'] }}</td>
                    <td class="num">{{ $fmt($row['opening']) }}</td>
                    @foreach($periodCols as $col)
                    <td class="num {{ $col['class'] }}">{{ $fmt($row[$col['key']] ?? 0, $col['is_deduction']) }}</td>
                    @endforeach
                    <td class="num">{{ $fmt($row['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="grand-row">
                    <td colspan="3" class="grand-label">Grand Total Amount</td>
                    <td class="num">{{ $fmt($grand['opening']) }}</td>
                    @foreach($periodCols as $col)
                    <td class="num {{ $col['class'] }}">{{ $fmt($grand[$col['key']] ?? 0, $col['is_deduction']) }}</td>
                    @endforeach
                    <td class="num">{{ $fmt($grand['balance']) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p class="empty-msg">No outstanding balance found for selected filters.</p>
        @endif
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("outstandingReportTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "Outstanding Detailed"});
            XLSX.writeFile(wb, "Outstanding_Balance_Detailed.xlsx");
        }

        document.addEventListener("DOMContentLoaded", function() {
            var rawTable = document.getElementById("outstandingReportTable");
            var badge = document.getElementById("pageCountBadge");
            if (!rawTable || !badge) return;

            var rows = rawTable.querySelectorAll("tbody tr:not(.grand-row)");
            if (rows.length === 0) {
                badge.innerText = "📄 Total Customers: 0";
                return;
            }

            // Estimate pages in A4 landscape (~44 rows per page)
            var estPages = Math.max(1, Math.ceil(rows.length / 44));
            badge.innerText = "📄 Total Customers: " + rows.length + " | ~" + estPages + " A4 " + (estPages === 1 ? "Page" : "Pages");
        });
    </script>
<!-- html2pdf for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            if (!element) return;
            var opt = {
                margin:       [4, 5, 4, 5],
                filename:     "Customer_Outstanding_Detailed.pdf",
                image:        { type: "jpeg", quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: "mm", format: "a4", orientation: "landscape" }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
