<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — Hold Qty Only</title>
    <style>
        @page { size: A4 landscape; margin: 2mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #000;
            margin: 0;
            padding: 1mm;
            background: #fff;
        }
        .no-print {
            padding: 8px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 8px;
        }
        .print-sheet {
            width: 297mm;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
        }
        .sheet-blank { width: 50%; flex: 0 0 50%; }
        .report-sheet {
            width: 50%;
            flex: 0 0 50%;
            padding: 0 0.5mm;
            overflow: hidden;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 1px;
            line-height: 1.1;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 2px;
            line-height: 1.15;
        }
        .report-title {
            color: #0d47a1;
            font-size: 10px;
            font-weight: bold;
            margin: 0;
        }
        .report-type {
            font-size: 8px;
            font-weight: bold;
            text-decoration: underline;
            color: #bf360c;
        }
        .date-range { font-size: 7px; font-weight: bold; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 6px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
            margin-bottom: 4px;
        }
        th {
            background: #cfd8dc;
            border: 1px solid #000;
            padding: 2px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        th.hold-col {
            background: #e65100 !important;
            color: #fff !important;
            width: 18%;
        }
        td {
            border: 1px solid #666;
            padding: 1px 3px;
            font-size: 8px;
        }
        td.hold-col {
            background: #fff3e0;
            color: #bf360c;
            font-weight: 600;
            text-align: right;
        }
        .item-name {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sno { text-align: center; width: 8%; }
        .brand-title td {
            background: #eceff1;
            font-weight: bold;
            font-size: 8px;
            padding: 2px 4px;
            color: #0d47a1;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .subtotal-row .hold-col {
            background: #ffe0b2 !important;
        }
        .grand-row td {
            font-weight: bold;
            background: #cfd8dc;
            border-top: 2px solid #000;
        }
        .grand-row .hold-col {
            background: #ffcc80 !important;
        }
        .empty-msg {
            text-align: center;
            padding: 20px;
            font-size: 10px;
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
    @endphp

    <div class="print-sheet">
        <div class="sheet-blank"></div>
        <div class="report-sheet">
            <div class="company-name">AL-MADINA TRADERS</div>
            <div class="report-header">
                <div class="generated-date">{{ $generated_at->format('l, M j, Y') }}</div>
                <div class="report-title">Stock Report</div>
                <div class="report-type">Hold Qty Only (Brand Wise)</div>
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
                        <th style="width:64%;">Item</th>
                        <th class="hold-col">Hold Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="brand-title">
                        <td colspan="3">{{ $group['brand_name'] }}</td>
                    </tr>
                    @foreach($group['rows'] as $i => $row)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="item-name" title="{{ $row['product_name'] }}">{{ $row['product_name'] }}</td>
                        <td class="hold-col">{{ $fmt($row['hold_qty']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:right;">Brand Total:</td>
                        <td class="hold-col">{{ $fmt($group['totals']['hold_qty']) }}</td>
                    </tr>
                </tbody>
            </table>
            @empty
            <p class="empty-msg">No hold stock found for selected filters.</p>
            @endforelse

            @if(!empty($groups))
            <table>
                <tbody>
                    <tr class="grand-row">
                        <td class="sno"></td>
                        <td style="text-align:right;">Grand Total:</td>
                        <td class="hold-col">{{ $fmt($grand['hold_qty']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif
        </div>
    </div>
</body>
</html>
