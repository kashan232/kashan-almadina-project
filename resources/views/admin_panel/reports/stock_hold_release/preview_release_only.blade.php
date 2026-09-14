<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Release Report</title>
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
            padding: 5px 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 11px;
        }
        .party-name {
            font-weight: bold;
            font-size: 13px;
            color: #000;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        .num { text-align: right; }
        .center { text-align: center; }
        .empty-msg {
            text-align: center;
            padding: 30px;
            font-size: 14px;
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
        <div class="report-title">Stock Release Report</div>
        <div class="header-meta-row">
            <div class="date-range">
                From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '01-01-01' }}</span>
                To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
            </div>
            <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        </div>
    </div>

    @forelse($parties as $party)
        <div class="party-name">{{ $party['party_name'] }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 6%;">S#</th>
                    <th style="width: 14%;">Release ID</th>
                    <th style="width: 16%;">Release Date</th>
                    <th style="width: 14%;">Hold ID</th>
                    <th style="width: 40%; text-align: left;">Item Description</th>
                    <th style="width: 10%;" class="num">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($party['rows'] as $r)
                    <tr>
                        <td class="center">{{ $r['sno'] }}</td>
                        <td class="center">{{ $r['release_id'] }}</td>
                        <td class="center">{{ $r['release_date'] }}</td>
                        <td class="center">{{ $r['hold_id'] }}</td>
                        <td>{{ $r['product_name'] }}</td>
                        <td class="num">{{ number_format($r['qty'], 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="empty-msg">No stock release records found for selected filters.</p>
    @endforelse
</body>
</html>
