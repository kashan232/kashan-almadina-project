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
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #000;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .date-range {
            font-size: 12px;
            font-weight: bold;
            text-align: left;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 0;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 4px;
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
        .party-title td {
            font-weight: bold;
            font-size: 13px;
            color: #0d47a1;
            border: none;
            padding: 10px 4px 4px;
            background: #fff;
        }
        .item-desc { text-align: left; }
        .num { text-align: center; font-weight: bold; }
        .sno { text-align: center; width: 40px; }
        .subtotal-row td,
        .grand-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .grand-row td {
            background: #cfd8dc;
            border-top: 2px solid #000;
        }
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

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        <div class="report-title">
            Stock Hold Balance Only Report
            <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">Customer / Party Wise</div>
        </div>
        <div class="date-range">
            From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
            To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
        </div>
    </div>

    @forelse($groups as $group)
    <table>
        <thead>
            <tr>
                <th class="sno">S#</th>
                <th style="width: 70%;">Item Description</th>
                <th style="width: 30%;">Payable</th>
            </tr>
        </thead>
        <tbody>
            <tr class="party-title">
                <td colspan="3">{{ $group['party_name'] }}</td>
            </tr>
            @foreach($group['rows'] as $i => $row)
            <tr>
                <td class="sno">{{ $i + 1 }}</td>
                <td class="item-desc">{{ $row['product_name'] }}</td>
                <td class="num">{{ $fmt($row['payable']) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="2" style="text-align:right;">Sub Total.</td>
                <td class="num">{{ $fmt($group['total_payable']) }}</td>
            </tr>
        </tbody>
    </table>
    @empty
    <p class="empty-msg">No active hold balance found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table>
        <tbody>
            <tr class="grand-row">
                <td class="sno"></td>
                <td style="text-align:right; width: 70%;">Grand Total</td>
                <td class="num" style="width: 30%;">{{ $fmt($grand_payable) }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</body>
</html>
