<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Daily Activity Report — Form Wise</title>
    <style>
        @page { size: A4 landscape; margin: 3mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 4mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 10px;
        }
        .company-name {
            text-align: center;
            color: #0d47a1;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 8px;
        }
        .report-title {
            color: #1a237e;
            font-size: 14px;
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
            margin-bottom: 12px;
            table-layout: fixed;
        }
        th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            font-size: 9px;
        }
        .col-date { width: 8%; text-align: center; }
        .col-ref { width: 10%; text-align: center; }
        .col-desc { width: 42%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .col-price { width: 8%; text-align: right; }
        .col-qty { width: 7%; text-align: center; }
        .col-amt { width: 12%; text-align: right; }
        
        .form-header-row td {
            background: #e0f2fe;
            border-top: 2px solid #0284c7;
            font-weight: bold;
            font-size: 11px;
            color: #0369a1;
        }
        .subtotal-row td {
            background: #f1f5f9;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            color: #0f172a;
        }
        .grand-row td {
            background: #cbd5e1;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 10px;
        }
        .num { text-align: right; white-space: nowrap; }
        .empty-msg { text-align: center; padding: 24px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;background:#0284c7;color:#fff;border:none;border-radius:4px;">Print Report</button>
        <button id="btnExportExcel" onclick="exportReportToExcel()" style="padding:8px 20px;font-weight:bold;cursor:pointer;background:#2e7d32;color:#fff;border:none;border-radius:4px;margin-left:8px;">
            Export to Excel
        </button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmtNum = function($val) {
            $v = (float)$val;
            return abs($v) < 0.001 ? '' : number_format($v, 0);
        };
        $fromLabel = $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '';
        $toLabel = $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '';
    @endphp

    <div id="reportContainer">
        <div class="company-name">AL-MADINA TRADERS</div>
        <div class="report-header">
            <div class="generated-date">{{ $generated_at->format('l, M j, Y h:i A') }}</div>
            <div class="report-title">Daily Activity Report (Form Wise)</div>
            <div class="report-sub">From: {{ $fromLabel }} To: {{ $toLabel }}</div>
        </div>

        @if(!empty($sections))
        <table id="dailyReportTable">
            <thead>
                <tr>
                    <th class="col-date" rowspan="2">Date</th>
                    <th class="col-ref" rowspan="2">Reference</th>
                    <th class="col-desc" rowspan="2">Description / Particulars</th>
                    <th class="col-price" rowspan="2">Price</th>
                    <th colspan="2">Debit</th>
                    <th colspan="2">Credit</th>
                </tr>
                <tr>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Amount</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sections as $sec)
                    {{-- Form Type Header --}}
                    <tr class="form-header-row">
                        <td colspan="8">
                            Form / Module: {{ $sec['title'] }}
                        </td>
                    </tr>

                    {{-- Transactions --}}
                    @foreach($sec['transactions'] as $txn)
                    <tr>
                        <td class="col-date">{{ $txn['date'] }}</td>
                        <td class="col-ref">{{ $txn['ref'] }} {{ $txn['inv_no'] }}</td>
                        <td class="col-desc" title="{{ $txn['desc'] }}">{{ $txn['desc'] }}</td>
                        <td class="col-price num">{{ $fmtNum($txn['price']) }}</td>
                        <td class="col-qty num">{{ $fmtNum($txn['debit_qty']) }}</td>
                        <td class="col-amt num">{{ $fmtNum($txn['debit_amt']) }}</td>
                        <td class="col-qty num">{{ $fmtNum($txn['credit_qty']) }}</td>
                        <td class="col-amt num">{{ $fmtNum($txn['credit_amt']) }}</td>
                    </tr>
                    @endforeach

                    {{-- Section Subtotal Row --}}
                    <tr class="subtotal-row">
                        <td colspan="4" style="text-align:right;">{{ $sec['title'] }} Sub Total >>></td>
                        <td class="col-qty num">{{ $fmtNum($sec['subtotal']['debit_qty']) }}</td>
                        <td class="col-amt num">{{ $fmtNum($sec['subtotal']['debit_amt']) }}</td>
                        <td class="col-qty num">{{ $fmtNum($sec['subtotal']['credit_qty']) }}</td>
                        <td class="col-amt num">{{ $fmtNum($sec['subtotal']['credit_amt']) }}</td>
                    </tr>
                @endforeach

                {{-- Grand Total --}}
                <tr class="grand-row">
                    <td colspan="4" style="text-align:right;">Grand Total Amount >>></td>
                    <td class="col-qty num">{{ $fmtNum($grand_total['debit_qty']) }}</td>
                    <td class="col-amt num">{{ $fmtNum($grand_total['debit_amt']) }}</td>
                    <td class="col-qty num">{{ $fmtNum($grand_total['credit_qty']) }}</td>
                    <td class="col-amt num">{{ $fmtNum($grand_total['credit_amt']) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p class="empty-msg">No transactions found for the selected date range.</p>
        @endif
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("dailyReportTable");
            if (!table) return;
            var wb = XLSX.utils.table_to_book(table, {sheet: "Daily Activity"});
            XLSX.writeFile(wb, "Daily_Activity_Report.xlsx");
        }
    </script>
</body>
</html>
