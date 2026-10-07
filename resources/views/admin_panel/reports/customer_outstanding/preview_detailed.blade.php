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
            min-height: 210mm;
            margin: 15px auto;
            background: #ffffff;
            padding: 5mm 6mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            position: relative;
            box-sizing: border-box;
        }
        .page-sheet-number {
            position: absolute;
            top: 4mm;
            right: 6mm;
            font-size: 9px;
            font-weight: bold;
            color: #666;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 8px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .report-sub {
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 9px;
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
            padding: 4px 3px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: center;
            line-height: 1.15;
        }
        td {
            border: 1px solid #666;
            padding: 2.5px 3px;
            font-size: 8.5px;
        }
        .sno { width: 3%; text-align: center; }
        .type { width: 7%; text-align: center; }
        .customer { width: 12%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .num { text-align: right; white-space: nowrap; }
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
            body { background: #fff; }
            .no-print { display: none !important; }
            .page-sheet {
                width: 100% !important;
                margin: 0 !important;
                padding: 3mm !important;
                box-shadow: none !important;
                page-break-after: always;
                min-height: auto !important;
            }
            .page-sheet-number { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="page-badge" id="pageCountBadge">📄 Estimated Pages: Computing...</div>
        <div class="btn-group">
            <button class="btn-print" onclick="window.print()">Print Report</button>
            <button class="btn-excel" id="btnExportExcel" onclick="exportReportToExcel()">Export to Excel</button>
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

    <div id="pagesWrapper">
        <!-- A4 Page Sheets will be dynamically rendered here -->
    </div>

    <!-- Hidden Raw Report Container for Excel & Pagination calculation -->
    <div id="reportContainer" style="display:none;">
        <div class="company-name">AL-MADINA TRADERS</div>
        <div class="report-header">
            <div class="generated-date">{{ $generated_at->format('l, M j, Y') }}</div>
            <div class="report-title">Outstanding Balance</div>
            <div class="report-sub">Detailed View</div>
        </div>

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

        // 📄 Dynamic A4 Page Sheet Paginator (Exact 22 rows per A4 landscape sheet)
        document.addEventListener("DOMContentLoaded", function() {
            var rawTable = document.getElementById("outstandingReportTable");
            var pagesWrapper = document.getElementById("pagesWrapper");
            var badge = document.getElementById("pageCountBadge");
            if (!rawTable || !pagesWrapper) return;

            var rows = Array.from(rawTable.querySelectorAll("tbody tr"));
            if (rows.length === 0) {
                pagesWrapper.innerHTML = '<div class="page-sheet"><p class="empty-msg">No outstanding balance found.</p></div>';
                if (badge) badge.innerText = "📄 Estimated Pages: 0";
                return;
            }

            var rowsPerPage = 22; // Fits perfectly on A4 landscape sheet with 0/3mm margin
            var totalPages = Math.ceil(rows.length / rowsPerPage);
            if (badge) badge.innerText = "📄 Total Pages: " + totalPages + " A4 Pages";

            var theadHTML = rawTable.querySelector("thead").outerHTML;
            var companyName = "AL-MADINA TRADERS";
            var genDate = "{{ $generated_at->format('l, M j, Y') }}";

            for (var p = 0; p < totalPages; p++) {
                var start = p * rowsPerPage;
                var pageRows = rows.slice(start, start + rowsPerPage);
                
                var sheet = document.createElement("div");
                sheet.className = "page-sheet";

                var sheetNumBadge = document.createElement("div");
                sheetNumBadge.className = "page-sheet-number";
                sheetNumBadge.innerText = "Page " + (p + 1) + " of " + totalPages;
                sheet.appendChild(sheetNumBadge);

                var headerHTML = '<div class="company-name">' + companyName + '</div>' +
                    '<div class="report-header">' +
                        '<div class="generated-date">' + genDate + '</div>' +
                        '<div class="report-title">Outstanding Balance</div>' +
                        '<div class="report-sub">Detailed View</div>' +
                    '</div>';

                var tbodyHTML = pageRows.map(function(r) { return r.outerHTML; }).join("");
                var tableHTML = '<table style="table-layout:fixed; width:100%; border-collapse:collapse; border:1px solid #000;">' + theadHTML + '<tbody>' + tbodyHTML + '</tbody></table>';

                sheet.innerHTML += headerHTML + tableHTML;
                pagesWrapper.appendChild(sheet);
            }
        });
    </script>
</body>
</html>
