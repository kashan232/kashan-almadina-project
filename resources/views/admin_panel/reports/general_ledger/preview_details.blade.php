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
    <title>Ledger - {{ $acCode }} {{ $acName }}</title>
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
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
            font-size: 9px;
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
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
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
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
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
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
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
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .page-sheet-number {
            position: absolute;
            top: 2mm;
            right: 4mm;
            font-size: 8.5px;
            font-weight: bold;
            color: #555;
            background: #f0f0f0;
            padding: 1px 5px;
            border-radius: 3px;
            border: 1px solid #ccc;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }

        /* 🏷️ Header Box (Matching Image Exactly) */
        .ledger-header-box {
            border: 1.5px solid #000;
            padding: 2.5px 6px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            box-sizing: border-box;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .ledger-header-title {
            font-size: 11px;
            font-weight: bold;
            color: #000080; /* Dark Navy Blue */
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .ledger-header-date {
            font-size: 9.5px;
            font-weight: bold;
            color: #000;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
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
            border: 1px solid #000;
            table-layout: fixed;
            background: #fff;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
            font-size: 9px;
        }
        .ledger-table th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 2px 2px;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            text-align: center;
            line-height: 1.15;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .ledger-table td {
            border: 1px solid #777;
            padding: 1.5px 3px;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;
            color: #000;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .col-date   { width: 9%; text-align: center; white-space: nowrap; font-size: 9px; }
        .col-ref    { width: 10%; text-align: center; white-space: nowrap; font-size: 9px; }
        .col-desc   { width: 27%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 9px; }
        .col-price  { width: 8%; text-align: right; white-space: nowrap; font-size: 9px; }
        .col-qty    { width: 6%; text-align: center; white-space: nowrap; font-size: 9px; }
        .col-amt    { width: 11%; text-align: right; white-space: nowrap; font-size: 9px; }
        .col-bal    { width: 12%; text-align: right; white-space: nowrap; font-size: 9px; }

        /* DR / CR Split in Balance Column */
        .balance-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 !important;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .balance-cell .drcr {
            font-weight: bold;
            font-size: 8px;
            margin-right: 2px;
            flex-shrink: 0;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .balance-cell .bal-val {
            font-weight: bold;
            font-size: 9px;
            text-align: right;
            flex-grow: 1;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }

        /* Total Row (Blue text, bold, matches image) */
        .total-row td {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            background: #fff;
            padding: 2.5px 2px;
            font-weight: bold;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .total-label {
            text-align: right;
            color: #000080;
            font-weight: bold;
            padding-right: 4px !important;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .total-val {
            color: #000080;
            font-weight: bold;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }

        /* 📑 Footer */
        .sheet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            color: #000;
            padding-top: 2px;
            margin-top: auto;
            border-top: 1px solid #999;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .sheet-footer .footer-left {
            font-weight: bold;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
        }
        .sheet-footer .footer-right {
            color: #333;
            font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif;
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
                font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif !important;
            }
            .no-print {
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
                font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif !important;
            }
            .page-sheet:last-child {
                page-break-after: auto !important;
            }
            .ledger-table th, .ledger-table td, .total-row td, .balance-cell .bal-val, .total-label, .total-val {
                font-family: Calibri, 'Segoe UI', Candara, Arial, sans-serif !important;
                font-size: 9px !important;
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

    <!-- Hidden Raw Data Container (Used for Pagination & Excel export) -->
    <div id="rawReportContainer" style="display: none;">
        <!-- Header Template -->
        <div id="rawHeaderBox">
            <div class="ledger-header-title">
                Ledger of:- &nbsp; <span>{{ $acCode }}</span> &nbsp;-&nbsp; <span>{{ $acName }}</span>
            </div>
            <div class="ledger-header-date">
                From: {{ $fromLabel }} To: {{ $toLabel }}
            </div>
        </div>

        <!-- Full Table Template -->
        <table id="ledgerRawTable" class="ledger-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-date">Date</th>
                    <th rowspan="2" class="col-ref">Reference</th>
                    <th rowspan="2" class="col-desc">Description</th>
                    <th rowspan="2" class="col-price">Price</th>
                    <th colspan="2" class="col-debit-group">Debit</th>
                    <th colspan="2" class="col-credit-group">Credit</th>
                    <th rowspan="2" class="col-bal">Balance</th>
                </tr>
                <tr>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Amount</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $runningBalance = (float)$openingBalance;
                    $totalDebitAmount = 0;
                    $totalCreditAmount = 0;
                    $totalDebitQty = 0;
                    $totalCreditQty = 0;

                    $fmtQty = function($q) {
                        $val = (float)$q;
                        if (abs($val) < 0.0001) return '-';
                        return (floor($val) == $val) ? ((int)$val . ' -') : number_format($val, 1);
                    };
                @endphp

                <!-- Row 0: B/F (Opening Balance) -->
                <tr class="bf-row">
                    <td class="col-date"></td>
                    <td class="col-ref"></td>
                    <td class="col-desc" style="text-align: center; font-weight: bold;">B/F:</td>
                    <td class="col-price"></td>
                    <td class="col-qty">-</td>
                    <td class="col-amt">-</td>
                    <td class="col-qty">-</td>
                    <td class="col-amt">-</td>
                    <td class="col-bal">
                        <div class="balance-cell">
                            <span class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</span>
                            <span class="bal-val">{{ number_format(abs($runningBalance), 0) }}</span>
                        </div>
                    </td>
                </tr>

                <!-- Transaction Rows -->
                @foreach($transactions as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $totalDebitAmount += $trx['debit'];
                        $totalCreditAmount += $trx['credit'];
                        if ($trx['debit'] > 0) {
                            $totalDebitQty += (float)$trx['qty'];
                        }
                        if ($trx['credit'] > 0) {
                            $totalCreditQty += (float)$trx['qty'];
                        }

                        $dQty = ($trx['debit'] > 0 && $trx['qty'] > 0) ? $fmtQty($trx['qty']) : '-';
                        $dAmt = $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-';
                        $cQty = ($trx['credit'] > 0 && $trx['qty'] > 0) ? $fmtQty($trx['qty']) : '-';
                        $cAmt = $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-';
                        $priceDisplay = $trx['price'] > 0 ? number_format($trx['price'], 0) : '';
                    @endphp
                    <tr>
                        <td class="col-date">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td class="col-ref">{{ $trx['ref'] }} {{ $trx['inv'] }}</td>
                        <td class="col-desc" title="{{ $trx['desc'] }}">{{ $trx['desc'] }}</td>
                        <td class="col-price">{{ $priceDisplay }}</td>
                        <td class="col-qty">{{ $dQty }}</td>
                        <td class="col-amt">{{ $dAmt }}</td>
                        <td class="col-qty">{{ $cQty }}</td>
                        <td class="col-amt">{{ $cAmt }}</td>
                        <td class="col-bal">
                            <div class="balance-cell">
                                <span class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</span>
                                <span class="bal-val">{{ number_format(abs($runningBalance), 0) }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">{{ $acName }} Total. &gt;&gt;&gt;</td>
                    <td class="col-price"></td>
                    <td class="col-qty total-val">{{ $totalDebitQty > 0 ? $fmtQty($totalDebitQty) : '-' }}</td>
                    <td class="col-amt total-val">{{ number_format($totalDebitAmount, 0) }}</td>
                    <td class="col-qty total-val">{{ $totalCreditQty > 0 ? $fmtQty($totalCreditQty) : '-' }}</td>
                    <td class="col-amt total-val">{{ $totalCreditAmount > 0 ? number_format($totalCreditAmount, 0) : '-' }}</td>
                    <td class="col-bal total-val">
                        <div class="balance-cell">
                            <span class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</span>
                            <span class="bal-val">{{ number_format(abs($runningBalance), 0) }}</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Template -->
        <div id="rawFooterBox">
            <div class="footer-left">Report Generated: {{ date('h:i:s A | l, F d, Y') }}</div>
            <div class="footer-right">System Generated Report and Required no Signature</div>
        </div>
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("ledgerRawTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "General Ledger"});
            var fileName = "{{ preg_replace('/[^a-zA-Z0-9_-]/', '_', $acCode . '_' . $acName) }}_Ledger.xlsx";
            XLSX.writeFile(wb, fileName);
        }

        // 📄 Dynamic A5 Portrait Sheet Paginator (Fills page completely before spilling to next page)
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

            pagesWrapper.innerHTML = "";

            var theadHTML = rawTable.querySelector("thead").outerHTML;
            var headerBoxHTML = document.getElementById("rawHeaderBox").innerHTML;
            var footerHTML = document.getElementById("rawFooterBox").innerHTML;

            function createNewSheet(pageIdx) {
                var sheet = document.createElement("div");
                sheet.className = "page-sheet";

                var pageNumDiv = document.createElement("div");
                pageNumDiv.className = "page-sheet-number";
                pageNumDiv.innerText = "Page " + (pageIdx + 1);
                sheet.appendChild(pageNumDiv);

                var hBox = document.createElement("div");
                hBox.className = "ledger-header-box";
                hBox.innerHTML = headerBoxHTML;
                sheet.appendChild(hBox);

                var tableWrap = document.createElement("div");
                tableWrap.className = "ledger-table-wrap";
                tableWrap.innerHTML = '<table class="ledger-table">' + theadHTML + '<tbody></tbody></table>';
                sheet.appendChild(tableWrap);

                var fBox = document.createElement("div");
                fBox.className = "sheet-footer";
                fBox.innerHTML = footerHTML;
                sheet.appendChild(fBox);

                return sheet;
            }

            function getMaxTableHeight(sheet) {
                var sheetH = sheet.clientHeight || 764;
                var hBox = sheet.querySelector(".ledger-header-box");
                var fBox = sheet.querySelector(".sheet-footer");
                var hBoxH = hBox ? hBox.offsetHeight : 22;
                var fBoxH = fBox ? fBox.offsetHeight : 14;
                // Safety buffer to prevent any print overflow
                return (sheetH - hBoxH - fBoxH - 10);
            }

            var sheetIndex = 0;
            var currentSheet = createNewSheet(sheetIndex);
            pagesWrapper.appendChild(currentSheet);

            var currentTbody = currentSheet.querySelector("tbody");
            var maxTableHeight = getMaxTableHeight(currentSheet);

            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                currentTbody.appendChild(row.cloneNode(true));

                var isLastRow = (i === rows.length - 1);
                var currentTable = currentSheet.querySelector(".ledger-table");
                var currentTableHeight = currentTable.offsetHeight;

                var neededHeight = currentTableHeight;
                if (isLastRow && footRow) {
                    neededHeight += (footRow.offsetHeight || 20);
                }

                // If adding this row exceeds sheet capacity, move it to next sheet
                if (neededHeight > maxTableHeight && currentTbody.children.length > 1) {
                    currentTbody.removeChild(currentTbody.lastChild);

                    sheetIndex++;
                    currentSheet = createNewSheet(sheetIndex);
                    pagesWrapper.appendChild(currentSheet);
                    currentTbody = currentSheet.querySelector("tbody");
                    maxTableHeight = getMaxTableHeight(currentSheet);

                    currentTbody.appendChild(row.cloneNode(true));
                }
            }

            // Append footer row to the last sheet
            var finalSheet = pagesWrapper.lastElementChild;
            if (finalSheet && footRow) {
                var finalTable = finalSheet.querySelector(".ledger-table");
                if (finalTable && !finalTable.querySelector("tfoot")) {
                    var tfoot = document.createElement("tfoot");
                    tfoot.innerHTML = footRow.outerHTML;
                    finalTable.appendChild(tfoot);
                }
            }

            // Update page numbers: Page X of Y
            var allSheets = Array.from(pagesWrapper.querySelectorAll(".page-sheet"));
            var totalPages = allSheets.length;

            allSheets.forEach(function(s, idx) {
                var pNumEl = s.querySelector(".page-sheet-number");
                if (pNumEl) {
                    pNumEl.innerText = "Page " + (idx + 1) + " of " + totalPages;
                }
            });

            if (badge) {
                badge.innerText = "📄 Total Pages: " + totalPages + " A5 " + (totalPages === 1 ? "Page" : "Pages");
            }
        });
    </script>
</body>
</html>
