<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — With Retail</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 6mm 8mm 6mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
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

        table.retail-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
            margin-bottom: 6px;
        }
        table.retail-table thead {
            display: table-header-group;
        }
        table.retail-table tr {
            page-break-inside: avoid;
        }
        table.retail-table th {
            background: #d9d9d9;
            border: 1px solid #000000;
            padding: 3px 2px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
        }
        th.physical-col { background: #1565c0 !important; color: #ffffff !important; }
        th.hold-col { background: #e65100 !important; color: #ffffff !important; }
        table.retail-table td {
            border: 1px solid #666666;
            padding: 2.5px 3px;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        .sno { text-align: center; width: 6%; }
        .item { text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
        .num { text-align: right; }
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
        .section-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0d47a1;
            margin: 6px 0 2px;
        }
        .brand-title-row td {
            background: #eceff1;
            font-weight: bold;
            color: #0d47a1;
            padding: 2.5px 4px;
            font-size: 7.5pt;
            text-align: left;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #777777;
        }
        .brand-total-row td {
            font-weight: bold;
            background: #f5f5f5;
            border-top: 1px solid #777777;
            border-bottom: 1px solid #000000;
        }
        .total-row td {
            font-weight: bold;
            background: #eceff1;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
        }
        .grand-row td {
            font-weight: bold;
            background: #cfd8dc;
            border-top: 2px solid #000000;
            border-bottom: 2px solid #000000;
            font-size: 8pt;
        }
        .total-label { text-align: right; color: #1565c0; font-weight: bold; }
        .total-val { text-align: right; background: #e3f2fd !important; }
        .total-val-hold { text-align: right; background: #fff3e0 !important; color: #bf360c; }
        .total-val-amt { text-align: right; background: #f3e5f5 !important; color: #6a1b9a; }
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
        $fmtPrice = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <table class="header-tbl">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">AL-MADINA TRADERS</div>
                <div class="report-subtitle">Stock Report — With Retail (Physical &amp; Hold Stock)</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="date-range">From: {{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }} &nbsp; To: {{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</div>
                <div style="font-size: 7pt; color: #555;">{{ $generated_at->format('l, F j, Y | g:i A') }}</div>
            </td>
        </tr>
    </table>

    @forelse($groups as $group)
        <div class="section-title">{{ $group['warehouse_label'] }}</div>
        <table class="retail-table">
            <thead>
                <tr>
                    <th class="sno" style="width:6%;">S#</th>
                    <th style="width:44%;">Items</th>
                    <th class="physical-col" style="width:12%;">Physical Qty</th>
                    <th class="hold-col" style="width:12%;">Hold Qty</th>
                    <th style="width:12%;">Retail Price</th>
                    <th style="width:14%;">Retail Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $sno = 1; @endphp
                @if(!empty($group['brand_groups']))
                    @foreach($group['brand_groups'] as $brand)
                    <tr class="brand-title-row">
                        <td colspan="6">{{ strtoupper($brand['brand_name']) }}</td>
                    </tr>
                    @foreach($brand['rows'] as $row)
                    <tr>
                        <td class="sno">{{ $sno++ }}</td>
                        <td class="item">{{ $row['product_name'] }}</td>
                        <td class="physical-col num">{{ $fmt($row['physical_qty']) }}</td>
                        <td class="hold-col num">{{ $fmt($row['hold_qty']) }}</td>
                        <td class="num">{{ $fmtPrice($row['retail_price']) }}</td>
                        <td class="num">{{ $fmt($row['retail_amount']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="brand-total-row">
                        <td colspan="2" class="total-label">{{ strtoupper($brand['brand_name']) }} Total:</td>
                        <td class="total-val num">{{ $fmt($brand['totals']['physical_qty']) }}</td>
                        <td class="total-val-hold num">{{ $fmt($brand['totals']['hold_qty']) }}</td>
                        <td></td>
                        <td class="total-val-amt num">{{ $fmt($brand['totals']['retail_amount']) }}</td>
                    </tr>
                    @endforeach
                @else
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
                @endif
                <tr class="total-row">
                    <td colspan="2" class="total-label">{{ $group['warehouse_label'] }} Total:</td>
                    <td class="total-val num">{{ $fmt($group['totals']['physical_qty']) }}</td>
                    <td class="total-val-hold num">{{ $fmt($group['totals']['hold_qty']) }}</td>
                    <td></td>
                    <td class="total-val-amt num">{{ $fmt($group['totals']['retail_amount']) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p style="text-align:center; padding:20px; font-size:9pt;">No physical or hold stock found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table class="retail-table" style="margin-top:4px;">
        <tbody>
            <tr class="grand-row">
                <td class="sno" style="width:6%;"></td>
                <td class="total-label" style="width:44%;">Grand Total:</td>
                <td class="total-val num" style="width:12%;">{{ $fmt($grand['physical_qty']) }}</td>
                <td class="total-val-hold num" style="width:12%;">{{ $fmt($grand['hold_qty']) }}</td>
                <td style="width:12%;"></td>
                <td class="total-val-amt num" style="width:14%;">{{ $fmt($grand['retail_amount']) }}</td>
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
