<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        if ($type == 'customer') {
            $acCode = $account_info->customer_id ?: $account_info->id;
            $acName = $account_info->customer_name;
        } elseif ($type == 'vendor') {
            $acCode = $account_info->vendor_id ?: $account_info->id;
            $acName = $account_info->name;
        } else {
            $acCode = $account_info->account_code ?: $account_info->id;
            $acName = $account_info->title;
        }
        $fromLabel = $startDate ? date('d-m-y', strtotime($startDate)) : '';
        $toLabel = $endDate ? date('d-m-y', strtotime($endDate)) : '';
    @endphp
    <title>Ledger Summary - {{ $acCode }} {{ $acName }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #000;
            margin: 0;
            padding: 0;
            background: #525659;
        }

        /* 🎛️ Sticky Top Control Bar (Like Customer Outstanding) */
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
        .no-print .btn-group {
            display: flex;
            gap: 8px;
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
        .btn-excel { background: #2e7d32; color: #fff; }
        .btn-excel:hover { background: #1b5e20; }
        .btn-close { background: #616161; color: #fff; }
        .btn-close:hover { background: #424242; }
        .page-badge {
            background: #1976d2;
            color: #fff;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* 📄 A5 Portrait Page Sheet (Half of A4: 148mm x 210mm) */
        .page-sheet {
            width: 148mm;
            min-height: 210mm;
            height: 210mm;
            margin: 15px auto;
            background: #ffffff;
            padding: 4mm 5mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .page-sheet-number {
            position: absolute;
            top: 1.5mm;
            right: 4mm;
            font-size: 8px;
            font-weight: bold;
            color: #555;
            background: #f0f0f0;
            padding: 1px 5px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }

        /* 🏷️ Header Box (Matching Image Exactly) */
        .ledger-header-box {
            border: 1.5px solid #000;
            padding: 3px 6px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            box-sizing: border-box;
        }
        .ledger-header-title {
            font-size: 11px;
            font-weight: bold;
            color: #000080; /* Dark Navy Blue */
        }
        .ledger-header-date {
            font-size: 10px;
            font-weight: bold;
            color: #000;
        }

        /* 📊 Table Styling */
        .ledger-table-wrap {
            flex: 1 1 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            table-layout: fixed;
            background: #fff;
        }
        .ledger-table th {
            background: #d9d9d9;
            border: 1px solid #777;
            padding: 2.5px 2px;
            font-size: 8px;
            font-weight: bold;
            color: #000;
            text-align: center;
            line-height: 1.1;
        }
        .ledger-table td {
            border: 1px solid #777;
            padding: 2px 3px;
            font-size: 8px;
            line-height: 1.15;
            vertical-align: middle;
            color: #000;
        }
        .col-date   { width: 10%; text-align: center; white-space: nowrap; }
        .col-ref    { width: 12%; text-align: center; white-space: nowrap; }
        .col-desc   { width: 37%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .col-qty    { width: 6%; text-align: center; white-space: nowrap; }
        .col-amt    { width: 11%; text-align: right; white-space: nowrap; }
        .col-credit { width: 10%; text-align: right; white-space: nowrap; }
        .col-bal    { width: 14%; text-align: right; white-space: nowrap; padding: 1px 3px !important; }

        /* DR / CR Split in Balance Column */
        .balance-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 !important;
        }
        .balance-cell .drcr {
            font-weight: bold;
            font-size: 7.5px;
            margin-right: 4px;
            flex-shrink: 0;
            text-align: left;
        }
        .balance-cell .bal-val {
            font-weight: bold;
            font-size: 8px;
            text-align: right;
            flex-grow: 1;
        }

        /* Total Row (Navy blue text, bold, matches image) */
        .total-row td {
            border-top: 1.5px solid #000 !important;
            border-bottom: 1.5px solid #000 !important;
            background: #fff;
            padding: 3px 2px;
            font-weight: bold;
            font-size: 8px;
        }
        .total-label {
            text-align: right;
            color: #000080 !important;
            font-weight: bold;
            padding-right: 6px !important;
        }
        .total-val {
            color: #000080 !important;
            font-weight: bold;
        }

        /* 📑 Footer */
        .sheet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5px;
            color: #000;
            padding-top: 2px;
            margin-top: auto;
            border-top: 1px solid #777;
        }
        .sheet-footer .footer-left {
            font-weight: bold;
            color: #000;
        }
        .sheet-footer .footer-right {
            color: #333;
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 0;
            }
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-sheet-number {
                display: none !important;
            }
            .page-sheet {
                width: 148mm !important;
                height: 210mm !important;
                max-height: 210mm !important;
                margin: 0 !important;
                padding: 4mm 5mm !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .page-sheet:last-child {
                page-break-after: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Navigation Controls -->
    <div class="no-print">
        <div class="page-badge" id="pageCountBadge">📄 Total Pages: Computing...</div>
        <div class="btn-group">
            <button class="btn-print" onclick="window.print()">Print Report</button>
            <button class="btn-excel" onclick="exportReportToExcel()">Export to Excel</button>
            <button class="btn-close" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">Close</button>
        </div>
    </div>

    <!-- Container for dynamic A5 sheets rendered by JavaScript -->
    <div id="pagesWrapper"></div>

    <!-- Hidden Raw Data Container -->
    <div id="rawReportContainer" style="display: none;">
        <!-- Header Template -->
        <div id="rawHeaderBox">
            <div class="ledger-header-title">
                Ledger of:- &nbsp;&nbsp; <span>{{ $acCode }}</span> &nbsp;-&nbsp; <span>{{ $acName }}</span>
            </div>
            <div class="ledger-header-date">
                From: {{ $fromLabel }} To: {{ $toLabel }}
            </div>
        </div>

        <!-- Full Table Template -->
        <table id="ledgerRawTable" class="ledger-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-ref">Reference</th>
                    <th class="col-desc" style="letter-spacing: 1.5px;">Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Debit</th>
                    <th class="col-credit">Credit</th>
                    <th class="col-bal">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $runningBalance = (float)$openingBalance;
                    $totalDebit = 0;
                    $totalCredit = 0;
                    $totalQty = 0;
                @endphp

                <!-- Row 0: B/F (Opening Balance) -->
                <tr class="bf-row">
                    <td class="col-date"></td>
                    <td class="col-ref">0</td>
                    <td class="col-desc">: B/F</td>
                    <td class="col-qty"></td>
                    <td class="col-amt"></td>
                    <td class="col-credit"></td>
                    <td class="col-bal">
                        <div class="balance-cell">
                            <span class="drcr">{{ $runningBalance >= 0 ? 'DR' : 'CR' }}</span>
                            <span class="bal-val">{{ number_format(abs($runningBalance), 0) }}</span>
                        </div>
                    </td>
                </tr>

                <!-- Transaction Rows -->
                @foreach($transactions as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $totalDebit += (float)$trx['debit'];
                        $totalCredit += (float)$trx['credit'];
                        $totalQty += (float)($trx['qty'] ?? 0);

                        $qVal = (float)($trx['qty'] ?? 0);
                        $qDisplay = $qVal > 0 ? ((floor($qVal) == $qVal) ? (int)$qVal : number_format($qVal, 1)) : '';
                        $dDisplay = (float)$trx['debit'] > 0 ? number_format($trx['debit'], 0) : '';
                        $cDisplay = (float)$trx['credit'] > 0 ? number_format($trx['credit'], 0) : '';

                        $trxDate = !empty($trx['date']) ? date('d-m-y', strtotime($trx['date'])) : '';
                        $trxRef = trim(($trx['ref'] ?? '') . '   ' . ($trx['inv'] ?? ''));
                    @endphp
                    <tr>
                        <td class="col-date">{{ $trxDate }}</td>
                        <td class="col-ref">{{ $trxRef }}</td>
                        <td class="col-desc" title="{{ $trx['desc'] }}">{{ $trx['desc'] }}</td>
                        <td class="col-qty">{{ $qDisplay }}</td>
                        <td class="col-amt">{{ $dDisplay }}</td>
                        <td class="col-credit">{{ $cDisplay }}</td>
                        <td class="col-bal">
                            <div class="balance-cell">
                                <span class="drcr">{{ $runningBalance >= 0 ? 'DR' : 'CR' }}</span>
                                <span class="bal-val">{{ number_format(abs($runningBalance), 0) }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">{{ $acName }} Total. &nbsp;&gt;&gt;&gt;</td>
                    <td class="col-qty total-val">{{ $totalQty > 0 ? ((floor($totalQty) == $totalQty) ? (int)$totalQty : number_format($totalQty, 1)) : '' }}</td>
                    <td class="col-amt total-val">{{ $totalDebit > 0 ? number_format($totalDebit, 0) : '' }}</td>
                    <td class="col-credit total-val">{{ $totalCredit > 0 ? number_format($totalCredit, 0) : '' }}</td>
                    <td class="col-bal total-val">
                        <div class="balance-cell">
                            <span class="drcr" style="color: #000080;">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</span>
                            <span class="bal-val" style="color: #000080;">{{ number_format(abs($runningBalance), 0) }}</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Template -->
        <div id="rawFooterBox">
            <div class="footer-left">Report Generated: {{ date('g:i:s A | l, F d, Y') }}</div>
            <div class="footer-right">System Generated Report and Required no Signature</div>
        </div>
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("ledgerRawTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "General Ledger Summary"});
            var fileName = "{{ preg_replace('/[^a-zA-Z0-9_-]/', '_', $acCode . '_' . $acName) }}_Summary.xlsx";
            XLSX.writeFile(wb, fileName);
        }

        // 📄 Dynamic A5 Portrait Sheet Paginator (26 rows per page)
        document.addEventListener("DOMContentLoaded", function() {
            var rawTable = document.getElementById("ledgerRawTable");
            var pagesWrapper = document.getElementById("pagesWrapper");
            var badge = document.getElementById("pageCountBadge");
            if (!rawTable || !pagesWrapper) return;

            var rows = Array.from(rawTable.querySelectorAll("tbody tr"));
            var footRow = rawTable.querySelector("tfoot tr");

            if (rows.length === 0) {
                pagesWrapper.innerHTML = '<div class="page-sheet"><p style="text-align:center; padding: 40px; font-size:12px;">No transactions found for the selected period.</p></div>';
                if (badge) badge.innerText = "📄 Total Pages: 0 A5 Pages";
                return;
            }

            var rowsPerPage = 26; // Exact fit for A5 portrait (210mm height)
            var totalPages = Math.ceil(rows.length / rowsPerPage);
            if (totalPages === 0) totalPages = 1;

            if (badge) {
                badge.innerText = "📄 Total Pages: " + totalPages + " A5 " + (totalPages === 1 ? "Page" : "Pages");
            }

            var theadHTML = rawTable.querySelector("thead").outerHTML;
            var headerBoxHTML = document.getElementById("rawHeaderBox").innerHTML;
            var footerHTML = document.getElementById("rawFooterBox").innerHTML;

            for (var p = 0; p < totalPages; p++) {
                var start = p * rowsPerPage;
                var pageRows = rows.slice(start, start + rowsPerPage);

                var sheet = document.createElement("div");
                sheet.className = "page-sheet";

                // Page Number Tag
                var pageNumDiv = document.createElement("div");
                pageNumDiv.className = "page-sheet-number";
                pageNumDiv.innerText = "Page " + (p + 1) + " of " + totalPages;
                sheet.appendChild(pageNumDiv);

                // Top Header Box
                var hBox = document.createElement("div");
                hBox.className = "ledger-header-box";
                hBox.innerHTML = headerBoxHTML;
                sheet.appendChild(hBox);

                // Table Section
                var tableWrap = document.createElement("div");
                tableWrap.className = "ledger-table-wrap";

                var tbodyHTML = pageRows.map(function(r) { return r.outerHTML; }).join("");
                var isLastPage = (p === totalPages - 1);
                var tfootHTML = (isLastPage && footRow) ? ('<tfoot>' + footRow.outerHTML + '</tfoot>') : '';

                tableWrap.innerHTML = '<table class="ledger-table">' + theadHTML + '<tbody>' + tbodyHTML + '</tbody>' + tfootHTML + '</table>';
                sheet.appendChild(tableWrap);

                // Bottom Footer
                var fBox = document.createElement("div");
                fBox.className = "sheet-footer";
                fBox.innerHTML = footerHTML;
                sheet.appendChild(fBox);

                pagesWrapper.appendChild(sheet);
            }
        });
    </script>
</body>
</html>
