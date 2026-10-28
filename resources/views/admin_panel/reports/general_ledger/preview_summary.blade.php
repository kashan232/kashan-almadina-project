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
        $isLandscape = ($orientation ?? 'portrait') === 'landscape';
        $sheetW = $isLandscape ? '297mm' : '210mm';
        $sheetH = $isLandscape ? '210mm' : '297mm';
    @endphp
    <title>Ledger Summary - {{ $acCode }} {{ $acName }}</title>
    <style>
        @page {
            size: A4 {{ $orientation ?? 'portrait' }};
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 0;
            background: #525659;
        }

        /* 🎛️ Sticky Top Control Bar */
        .no-print {
            position: sticky;
            top: 0;
            z-index: 9999;
            padding: 8px 15px;
            background: #2a2d32;
            color: #fff;
            border-bottom: 1px solid #1a1c1e;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .no-print .btn-group {
            display: flex;
            gap: 8px;
        }
        .no-print .btn-group button {
            padding: 6px 16px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
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
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* 📄 A4 Portrait Page Sheet (210mm x 297mm) */
        .page-sheet {
            width: {{ $sheetW }};
            min-height: {{ $sheetH }};
            height: {{ $sheetH }};
            margin: 15px auto;
            background: #ffffff;
            padding: 6mm 8mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* 🏷️ Header Box (Matching Image Exactly: solid border, dark blue title, bold dates) */
        .ledger-header-box {
            border: 1.5px solid #000;
            padding: 3px 6px;
            margin-bottom: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            box-sizing: border-box;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .ledger-header-title {
            font-size: 13px;
            font-weight: bold;
            color: #000080; /* Dark Navy Blue */
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .ledger-header-date {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* 📊 Table Styling */
        .ledger-table-wrap {
            flex: 0 0 auto !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #777;
            table-layout: fixed;
            background: #fff;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-size: 9px;
        }
        .ledger-table th {
            background: #e0e0e0;
            border: 1px solid #777;
            padding: 2px 2px;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            text-align: center;
            line-height: 1.15;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .ledger-table td {
            border: 1px solid #777;
            padding: 1.5px 3px;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;
            color: #000;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .col-date   { width: 5.5%; text-align: center; white-space: nowrap; font-size: 9px; font-family: Calibri, 'Segoe UI', Arial, sans-serif; }
        .col-ref    { width: 6.5%; text-align: center; white-space: nowrap; font-size: 9px; font-family: Calibri, 'Segoe UI', Arial, sans-serif; }
        .col-desc   { width: 42%;  text-align: left;   white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 9px; font-family: Calibri, 'Segoe UI', Arial, sans-serif; }
        .col-qty    { width: 3.2%; text-align: center; white-space: nowrap; font-size: 9px; padding: 1.5px 1px !important; }
        .col-amt    { width: 14.4%; text-align: right;  white-space: nowrap; font-size: 9px; }
        .col-credit { width: 14.4%; text-align: right;  white-space: nowrap; font-size: 9px; }
        .col-bal    { width: 14%;  text-align: right;  white-space: nowrap; padding: 1px 3px !important; font-size: 9px; }

        /* 🚫 Remove vertical borders between Date, Reference, and Description */
        .ledger-table th.col-date,
        .ledger-table td.col-date {
            border-right: none !important;
        }
        .ledger-table th.col-ref,
        .ledger-table td.col-ref {
            border-left: none !important;
            border-right: none !important;
        }
        .ledger-table th.col-desc,
        .ledger-table td.col-desc {
            border-left: none !important;
            border-right: 1px solid #777 !important;
        }

        /* DR / CR Split in Balance Column */
        .balance-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 !important;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .balance-cell .drcr {
            font-weight: bold;
            font-size: 9px;
            margin-right: 2px;
            flex-shrink: 0;
            text-align: left;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .balance-cell .bal-val {
            font-size: 9px;
            text-align: right;
            flex-grow: 1;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* Total Row (Navy blue text, bold, matches image) */
        .total-row td {
            border-top: 1.5px solid #000 !important;
            border-bottom: 1.5px solid #000 !important;
            background: #fff;
            padding: 2.5px 3px;
            font-weight: bold;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .total-label {
            text-align: right;
            color: #000080 !important;
            font-weight: bold;
            padding-right: 6px !important;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .total-val {
            color: #000080 !important;
            font-weight: bold;
            font-size: 9px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* 📑 Footer — positioned immediately below the table on the last page */
        .sheet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5px;
            color: #000;
            padding-top: 4px;
            margin-top: 4px;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            flex-shrink: 0;
            width: 100%;
            box-sizing: border-box;
        }
        .sheet-footer .footer-left {
            font-weight: bold;
            color: #000;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }
        .sheet-footer .footer-right {
            color: #000;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
        }

        /* 📄 Page Number at the end of the page */
        .page-sheet-number {
            position: absolute;
            bottom: 2.5mm;
            right: 8mm;
            font-size: 8.5px;
            color: #444;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-weight: normal;
        }

        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
            }
            .no-print {
                display: none !important;
            }
            .ledger-table-wrap {
                flex: 0 0 auto !important;
            }
            .page-sheet {
                width: 100% !important;
                max-width: {{ $sheetW }} !important;
                height: auto !important;
                min-height: auto !important;
                max-height: calc({{ $sheetH }} - 8mm) !important;
                margin: 0 auto !important;
                padding: 4mm 8mm !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
                justify-content: flex-start !important;
            }
            .page-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .sheet-footer {
                display: flex !important;
                font-size: 8.5px !important;
                margin-top: 4px !important;
                padding-top: 4px !important;
            }
            .page-sheet-number {
                display: block !important;
                position: absolute !important;
                bottom: 2.5mm !important;
                right: 8mm !important;
                font-size: 8.5px !important;
                color: #000 !important;
                font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
            }
            .ledger-table th, .ledger-table td, .total-row td, .balance-cell .bal-val, .total-label, .total-val {
                font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
                font-size: 9px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Navigation Controls -->
    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="page-badge" id="pageCountBadge">📄 Total Pages: Computing...</div>
            <span style="font-size: 11.5px; color: #ffeb3b; font-weight: bold;">
                ⚠️ Note: Print preview me "More settings" > "Headers and footers" ko uncheck karein taakay URL aur Time print nah ho.
            </span>
        </div>
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
            <colgroup>
                <col class="col-date">
                <col class="col-ref">
                <col class="col-desc">
                <col class="col-qty">
                <col class="col-amt">
                <col class="col-credit">
                <col class="col-bal">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-ref">Reference</th>
                    <th class="col-desc">Description</th>
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
                    <td class="col-ref"></td>
                    <td class="col-desc" style="text-align: center; font-weight: bold;">B/F:</td>
                    <td class="col-qty"></td>
                    <td class="col-amt" style="text-align: center;">-</td>
                    <td class="col-credit" style="text-align: center;">-</td>
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
                        $totalDebit += (float)$trx['debit'];
                        $totalCredit += (float)$trx['credit'];
                        $totalQty += (float)($trx['qty'] ?? 0);

                        $qVal = (float)($trx['qty'] ?? 0);
                        $qDisplay = $qVal > 0 ? ((floor($qVal) == $qVal) ? (int)$qVal : number_format($qVal, 1)) : '';
                        $dDisplay = (float)$trx['debit'] > 0 ? number_format($trx['debit'], 0) : '';
                        $cDisplay = (float)$trx['credit'] > 0 ? number_format($trx['credit'], 0) : '';

                        $trxDate = !empty($trx['date']) ? date('d-m-y', strtotime($trx['date'])) : '';
                        $trxRef = trim(($trx['ref'] ?? '') . ' ' . ($trx['inv'] ?? ''));
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

            var colgroupHTML = rawTable.querySelector("colgroup") ? rawTable.querySelector("colgroup").outerHTML : "";
            var theadHTML = rawTable.querySelector("thead").outerHTML;
            var headerBoxHTML = document.getElementById("rawHeaderBox").innerHTML;
            var footerHTML = document.getElementById("rawFooterBox").innerHTML;

            var FOOTER_HEIGHT = 22;
            var PAGE_NUM_HEIGHT = 16;

            function createNewSheet(pageIdx) {
                var sheet = document.createElement("div");
                sheet.className = "page-sheet";

                var hBox = document.createElement("div");
                hBox.className = "ledger-header-box";
                hBox.innerHTML = headerBoxHTML;
                sheet.appendChild(hBox);

                var tableWrap = document.createElement("div");
                tableWrap.className = "ledger-table-wrap";
                tableWrap.innerHTML = '<table class="ledger-table">' + colgroupHTML + theadHTML + '<tbody></tbody></table>';
                sheet.appendChild(tableWrap);

                var pNum = document.createElement("div");
                pNum.className = "page-sheet-number";
                pNum.innerText = "Page " + (pageIdx + 1);
                sheet.appendChild(pNum);

                return sheet;
            }

            function getMaxTableHeight(sheet, isLast) {
                var isLandscape = {{ $isLandscape ? 'true' : 'false' }};
                var sheetH = sheet.clientHeight || (isLandscape ? 764 : 1080);
                var hBox = sheet.querySelector(".ledger-header-box");
                var hBoxH = hBox ? hBox.offsetHeight : 28;
                var fH = isLast ? FOOTER_HEIGHT : 0;
                return (sheetH - hBoxH - fH - PAGE_NUM_HEIGHT - 30);
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

            // Append footer ONLY on the finalSheet, directly below the table!
            if (finalSheet) {
                var fBox = document.createElement("div");
                fBox.className = "sheet-footer";
                fBox.innerHTML = footerHTML;
                finalSheet.appendChild(fBox);
            }

            var allSheets = Array.from(pagesWrapper.querySelectorAll(".page-sheet"));
            var totalPages = allSheets.length;

            allSheets.forEach(function(s, idx) {
                var pNumEl = s.querySelector(".page-sheet-number");
                if (pNumEl) {
                    pNumEl.innerText = "Page " + (idx + 1) + " of " + totalPages;
                }
            });

            if (badge) {
                badge.innerText = "📄 Total Pages: " + totalPages + " A4 " + (totalPages === 1 ? "Page" : "Pages");
            }
        });
    </script>
</body>
</html>
