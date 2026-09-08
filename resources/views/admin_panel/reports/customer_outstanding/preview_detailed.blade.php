<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Customer Outstanding Balance — Detailed</title>
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
            padding: 5px 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            line-height: 1.15;
        }
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            font-size: 9px;
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
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;background:#c2185b;color:#fff;border:none;border-radius:4px;">Print Report</button>
        <button id="btnExportExcel" onclick="exportReportToExcel()" style="padding:8px 20px;font-weight:bold;cursor:pointer;background:#2e7d32;color:#fff;border:none;border-radius:4px;margin-left:8px;">
            Export to Excel
        </button>
        <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = function($v) {
            $val = (float)$v;
            if (abs($val) < 0.0001) return '';
            return ($val < 0 ? '-' : '') . number_format(abs($val), 0);
        };
        $fromLabel = $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '';
        $toLabel = $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '';
        $periodCols = [
            ['key' => 'sales', 'head' => 'Sales', 'class' => ''],
            ['key' => 'c_rep', 'head' => 'C. Rep', 'class' => ''],
            ['key' => 'payment', 'head' => 'Payment', 'class' => 'col-green'],
            ['key' => 'income', 'head' => 'Income', 'class' => 'col-green'],
            ['key' => 'jv_dr', 'head' => 'JV-DR.', 'class' => ''],
            ['key' => 'cir', 'head' => 'Claim CN', 'class' => 'col-green'],
            ['key' => 'purchase', 'head' => 'Purchase', 'class' => 'col-red'],
            ['key' => 'pur_ret', 'head' => 'Pur Ret', 'class' => 'col-green'],
            ['key' => 's_ret', 'head' => 'S. Ret', 'class' => 'col-red'],
            ['key' => 'clm_cn', 'head' => 'CLM CN', 'class' => 'col-red'],
            ['key' => 'receipts', 'head' => 'Receipts', 'class' => 'col-red'],
            ['key' => 'exp_dis', 'head' => 'Exp / Dis', 'class' => 'col-green'],
            ['key' => 'jv_cr', 'head' => 'JV-CR.', 'class' => 'col-green'],
        ];
    @endphp

    <div id="reportContainer">
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
                    <td class="num {{ $col['class'] }}">{{ $fmt($row[$col['key']] ?? 0) }}</td>
                    @endforeach
                    <td class="num">{{ $fmt($row['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="grand-row">
                    <td colspan="3" class="grand-label">Grand Total Amount</td>
                    <td class="num">{{ $fmt($grand['opening']) }}</td>
                    @foreach($periodCols as $col)
                    <td class="num {{ $col['class'] }}">{{ $fmt($grand[$col['key']] ?? 0) }}</td>
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
    </script>
</body>
</html>
