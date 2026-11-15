<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — Hold Qty Only</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-tbl td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .company-title {
            font-size: 11pt;
            font-weight: bold;
            color: #8e24aa;
        }
        .report-subtitle {
            font-size: 9pt;
            font-weight: bold;
            color: #0d47a1;
        }
        .date-range {
            font-size: 8pt;
            font-weight: bold;
            color: #000000;
            text-align: right;
            white-space: nowrap;
        }

        table.hold-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
            margin-bottom: 6px;
        }
        table.hold-table thead {
            display: table-header-group;
        }
        table.hold-table tr {
            page-break-inside: avoid;
        }
        table.hold-table th {
            background: #cfd8dc;
            border: 1px solid #000000;
            padding: 3px 2px;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
        }
        th.hold-col {
            background: #e65100 !important;
            color: #ffffff !important;
            width: 22%;
        }
        table.hold-table td {
            border: 1px solid #666666;
            padding: 2.5px 4px;
            font-size: 8pt;
            vertical-align: middle;
        }
        .sno { text-align: center; width: 8%; }
        .item-name { text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
        .num { text-align: right; }
        td.hold-col {
            background: #fff3e0;
            color: #bf360c;
            font-weight: 600;
            text-align: right;
        }
        .brand-title td {
            background: #eceff1;
            font-weight: bold;
            font-size: 8pt;
            padding: 3px 6px;
            color: #0d47a1;
            border-top: 1.5px solid #000;
            border-bottom: 1px solid #666;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #eceff1;
            border-top: 1px solid #666;
            border-bottom: 1px solid #000;
        }
        .subtotal-row .hold-col {
            background: #ffe0b2 !important;
        }
        .grand-row td {
            font-weight: bold;
            background: #cfd8dc;
            border-top: 2px solid #000000;
            border-bottom: 2px solid #000000;
            font-size: 8.5pt;
        }
        .grand-row .hold-col {
            background: #ffcc80 !important;
        }
        .report-footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .report-footer td {
            border: none;
            padding: 1px 0;
            font-size: 7pt;
            color: #444444;
        }
    </style>
</head>
<body>
    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <table class="header-tbl">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">AL-MADINA TRADERS</div>
                <div class="report-subtitle">Stock Report — Hold Qty Only (Brand Wise)</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="date-range">From: {{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }} &nbsp; To: {{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</div>
                <div style="font-size: 7pt; color: #555;">{{ $generated_at->format('l, M j, Y | g:i A') }}</div>
            </td>
        </tr>
    </table>

    @forelse($groups as $group)
    <table class="hold-table">
        <thead>
            <tr>
                <th class="sno" style="width:8%;">S#</th>
                <th style="width:70%;">Item</th>
                <th class="hold-col" style="width:22%;">Hold Qty</th>
            </tr>
        </thead>
        <tbody>
            <tr class="brand-title">
                <td colspan="3">{{ strtoupper($group['brand_name']) }}</td>
            </tr>
            @foreach($group['rows'] as $i => $row)
            <tr>
                <td class="sno">{{ $i + 1 }}</td>
                <td class="item-name">{{ $row['product_name'] }}</td>
                <td class="hold-col">{{ $fmt($row['hold_qty']) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="2" style="text-align:right;">{{ strtoupper($group['brand_name']) }} Total:</td>
                <td class="hold-col">{{ $fmt($group['totals']['hold_qty']) }}</td>
            </tr>
        </tbody>
    </table>
    @empty
        <p style="text-align:center; padding:20px; font-size:9pt;">No hold stock found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table class="hold-table" style="margin-top:4px;">
        <tbody>
            <tr class="grand-row">
                <td class="sno" style="width:8%;"></td>
                <td style="text-align:right; width:70%;">Grand Total:</td>
                <td class="hold-col" style="width:22%;">{{ $fmt($grand['hold_qty']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <table class="report-footer">
        <tr>
            <td style="text-align: left;">Report Generated: {{ date('g:i:s A | l, F d, Y') }}</td>
            <td style="text-align: right;">System Generated Report and Required no Signature</td>
        </tr>
    </table>
</body>
</html>
