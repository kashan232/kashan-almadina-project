<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Hold Balance Only Report</title>
    <style>
        @page { size: A4 portrait; margin: 6mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            text-align: center;
            color: #000;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 16px;
        }
        .report-title {
            color: #000;
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .header-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .date-range {
            text-align: left;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            text-align: right;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 14px;
        }
        th {
            background-color: #dcdcdc;
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
            font-size: 11px;
        }
        .num { text-align: right; font-weight: bold; }
        .center { text-align: center; }
        .empty-msg {
            text-align: center;
            padding: 30px;
            font-size: 14px;
        }
        .grand-row td {
            background: #cfd8dc;
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #000;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 25px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:10px 25px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    <div class="company-name">Al-Madina Traders</div>
    <div class="report-header">
        <div class="report-title">Stock Hold Balance Only Report</div>
        <div class="header-meta-row">
            <div class="date-range">
                From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '01-01-01' }}</span>
                To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
            </div>
            <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        </div>
    </div>

    @if(!empty($customers))
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">S#</th>
                    <th style="width: 52%; text-align: left;">Customer Name</th>
                    <th style="width: 22%;">Last Date / Time</th>
                    <th style="width: 18%;" class="num">Hold Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $idx => $cust)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td style="font-weight: bold;">{{ $cust['party_name'] }}</td>
                        <td class="center">{{ $cust['last_time'] ?: '-' }}</td>
                        <td class="num">{{ number_format($cust['total_payable'], 0) }}</td>
                    </tr>
                @endforeach
                <tr class="grand-row">
                    <td colspan="3" style="text-align: right;">Grand Total Hold Balance:</td>
                    <td class="num">{{ number_format($grand_payable, 0) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="empty-msg">No active stock hold balance records found for selected filters.</p>
    @endif
</body>
</html>
