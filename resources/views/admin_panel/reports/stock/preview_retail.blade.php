<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Report — With Retail</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
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
            margin-bottom: 16px;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .report-type {
            color: #333;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
        }
        .date-range {
            font-size: 11px;
            font-weight: bold;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 10px;
            color: #555;
        }
        .dt-line {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 6px;
            color: #0d47a1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        th.physical-col { background: #1565c0; color: #fff; }
        th.hold-col { background: #e65100; color: #fff; }
        td {
            border: 1px solid #666;
            padding: 3px 5px;
            font-size: 10px;
        }
        td.physical-col {
            background: #e3f2fd;
            color: #0d47a1;
            font-weight: 600;
        }
        td.hold-col {
            background: #fff3e0;
            color: #bf360c;
            font-weight: 600;
        }
        .sno { text-align: center; width: 5%; }
        .item { text-align: left; }
        .num { text-align: right; }
        .total-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .total-label { text-align: right; color: #1565c0; }
        .total-val { text-align: right; background: #e3f2fd !important; }
        .total-val-hold { text-align: right; background: #fff3e0 !important; color: #bf360c; }
        .total-val-amt { text-align: right; background: #f3e5f5 !important; color: #6a1b9a; }
        .grand-row td {
            font-weight: bold;
            background: #cfd8dc;
            border-top: 2px solid #000;
        }
        .empty-msg {
            text-align: center;
            padding: 30px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
        $fmtPrice = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        <h1 class="report-title">Stock Report</h1>
        <div class="report-type">With Retail (Physical &amp; Hold Stock)</div>
        <div class="date-range">
            From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
            &nbsp;&nbsp;To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
        </div>
    </div>
    <div class="dt-line">Date and Time: {{ $generated_at->format('d-m-y g:i:s A') }}</div>

    @forelse($groups as $group)
        <div class="section-title">{{ $group['warehouse_label'] }}</div>

        <table>
            <thead>
                <tr>
                    <th class="sno">S#</th>
                    <th>Items</th>
                    <th class="physical-col" style="width:9%;">Physical Qty</th>
                    <th class="hold-col" style="width:9%;">Hold Qty</th>
                    <th style="width:12%;">Retail Price</th>
                    <th style="width:14%;">Retail Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['rows'] as $i => $row)
                <tr>
                    <td class="sno">{{ $i + 1 }}</td>
                    <td class="item">{{ $row['product_name'] }}</td>
                    <td class="physical-col num">{{ $fmt($row['physical_qty']) }}</td>
                    <td class="hold-col num">{{ $fmt($row['hold_qty']) }}</td>
                    <td class="num">{{ $fmtPrice($row['retail_price']) }}</td>
                    <td class="num">{{ $fmt($row['retail_amount']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="total-label">Total:</td>
                    <td class="total-val num">{{ $fmt($group['totals']['physical_qty']) }}</td>
                    <td class="total-val-hold num">{{ $fmt($group['totals']['hold_qty']) }}</td>
                    <td></td>
                    <td class="total-val-amt num">{{ $fmt($group['totals']['retail_amount']) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="empty-msg">No physical or hold stock found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table>
        <tbody>
            <tr class="grand-row">
                <td class="sno"></td>
                <td class="total-label" style="width:42%;">Grand Total:</td>
                <td class="total-val num" style="width:9%;">{{ $fmt($grand['physical_qty']) }}</td>
                <td class="total-val-hold num" style="width:9%;">{{ $fmt($grand['hold_qty']) }}</td>
                <td style="width:12%;"></td>
                <td class="total-val-amt num" style="width:14%;">{{ $fmt($grand['retail_amount']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</body>
</html>
