<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Customer Outstanding Balance</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
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

        /* 📄 A4 Landscape Page Sheet Preview Styling (Short View) */
        .page-sheet {
            width: 297mm;
            height: 200mm;
            margin: 15px auto;
            background: #ffffff;
            padding: 6mm 8mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .table-half-container {
            width: 145mm;
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
            padding: 3px 2px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: center;
            line-height: 1.1;
        }
        td {
            border: 1px solid #666;
            padding: 2px 3px;
            font-size: 8px;
            line-height: 1.1;
        }
        .sno { width: 5%; text-align: center; }
        .customer { width: 27%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
        .num { text-align: right; white-space: nowrap; font-size: 8px; font-family: monospace, Arial; }
        .period-head {
            background: #eceff1;
            font-weight: bold;
        }
        .grand-row td {
            font-weight: bold;
            background: #eceff1;
            border-top: 2px solid #000;
            font-size: 8px;
        }
        .grand-label { text-align: right; }
        .empty-msg { text-align: center; padding: 24px; font-size: 11px; }

        .sheet-footer {
            width: 145mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            color: #333;
            padding-top: 3px;
            border-top: 1px dashed #ccc;
            margin-top: auto;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page-sheet {
                width: 297mm !important;
                height: 200mm !important;
                margin: 0 !important;
                padding: 6mm 8mm !important;
                box-shadow: none !important;
                page-break-after: always;
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
            <button class="btn-close" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">Close</button>
        </div>
    </div>

    @php
        $fmt = function($v) {
            $val = (float)$v;
            if (abs($val) < 0.0001) return '';
            return ($val < 0 ? '-' : '') . number_format(abs($val), 0);
        };
        $fromLabel = $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '';
        $toLabel = $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '';
    @endphp

    <div id="pagesWrapper">
        <!-- A4 Page Sheets will be dynamically rendered here -->
    </div>

    <div id="reportContainer" style="display:none;">
        @if(!empty($rows))
        <table id="outstandingShortTable">
            <thead>
                <tr>
                    <th class="sno" rowspan="2">S#</th>
                    <th class="customer" rowspan="2">Customer Name</th>
                    <th rowspan="2" style="width: 11%;">Opening Balance</th>
                    <th colspan="4" class="period-head">Between {{ $fromLabel }} To. {{ $toLabel }}</th>
                </tr>
                <tr>
                    <th style="width: 14%;">Sales</th>
                    <th style="width: 14%;">SRJ PJ</th>
                    <th style="width: 14%;">Receipts</th>
                    <th style="width: 16%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td class="sno">{{ $i + 1 }}</td>
                    <td class="customer">{{ $row['party_name'] ?? $row['customer_name'] }}</td>
                    <td class="num">{{ $fmt($row['opening']) }}</td>
                    <td class="num">{{ $fmt($row['sales']) }}</td>
                    <td class="num">{{ $fmt($row['sr_pj']) }}</td>
                    <td class="num">{{ $fmt($row['receipts']) }}</td>
                    <td class="num">{{ $fmt($row['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="grand-row">
                    <td colspan="2" class="grand-label">Grand Total Amount</td>
                    <td class="num">{{ $fmt($grand['opening']) }}</td>
                    <td class="num">{{ $fmt($grand['sales']) }}</td>
                    <td class="num">{{ $fmt($grand['sr_pj']) }}</td>
                    <td class="num">{{ $fmt($grand['receipts']) }}</td>
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
            var table = document.getElementById("outstandingShortTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "Outstanding Short"});
            XLSX.writeFile(wb, "Outstanding_Balance_Short.xlsx");
        }

        // 📄 Dynamic A4 Page Sheet Paginator (Short View: 42 rows per A4 landscape half-sheet)
        document.addEventListener("DOMContentLoaded", function() {
            var rawTable = document.getElementById("outstandingShortTable");
            var pagesWrapper = document.getElementById("pagesWrapper");
            var badge = document.getElementById("pageCountBadge");
            if (!rawTable || !pagesWrapper) return;

            var rows = Array.from(rawTable.querySelectorAll("tbody tr"));
            if (rows.length === 0) {
                pagesWrapper.innerHTML = '<div class="page-sheet"><p class="empty-msg">No outstanding balance found.</p></div>';
                if (badge) badge.innerText = "📄 Estimated Pages: 0";
                return;
            }

            var rowsPerPage = 42; // Fits ~42 rows per A4 Landscape Half Page sheet
            var totalPages = Math.ceil(rows.length / rowsPerPage);
            if (badge) badge.innerText = "📄 Total Pages: " + totalPages + " A4 Pages";

            var theadHTML = rawTable.querySelector("thead").outerHTML;
            var genDate = "{{ $generated_at->format('l, F j, Y') }}";

            for (var p = 0; p < totalPages; p++) {
                var start = p * rowsPerPage;
                var pageRows = rows.slice(start, start + rowsPerPage);
                
                var sheet = document.createElement("div");
                sheet.className = "page-sheet";

                var tbodyHTML = pageRows.map(function(r) { return r.outerHTML; }).join("");
                var tableHTML = '<div class="table-half-container"><table style="table-layout:fixed; width:100%; border-collapse:collapse; border:1px solid #000;">' + theadHTML + '<tbody>' + tbodyHTML + '</tbody></table></div>';

                var footerHTML = '<div class="sheet-footer">' +
                    '<div>' + genDate + '</div>' +
                    '<div>Page ' + (p + 1) + ' of ' + totalPages + '</div>' +
                    '</div>';

                sheet.innerHTML = tableHTML + footerHTML;
                pagesWrapper.appendChild(sheet);
            }
        });
    </script>
</body>
</html>
