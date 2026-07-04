<!DOCTYPE html>
<html lang="en">
<head>
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
        .dt-line {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 6px;
            color: #0d47a1;
        }
        .sub-title {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 4px;
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
        td {
            border: 1px solid #666;
            padding: 3px 5px;
            font-size: 10px;
        }
        .sno { text-align: center; width: 6%; }
        .item { text-align: left; }
        .num { text-align: right; }
        .total-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .total-label { text-align: right; color: #1565c0; }
        .total-val { text-align: right; background: #e3f2fd !important; }
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

    <div class="dt-line">Date and Time. {{ $generated_at->format('d-m-y g:i:s A') }}</div>

    @forelse($groups as $group)
        <div class="section-title">{{ $group['warehouse_label'] }}</div>

        @if(!empty($group['physical']))
        <div class="sub-title">Physical Stock</div>
        <table>
            <thead>
                <tr>
                    <th class="sno">S#</th>
                    <th>Items</th>
                    <th style="width:10%;">Qty</th>
                    <th style="width:14%;">Retail Price</th>
                    <th style="width:16%;">Retail Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['physical'] as $i => $row)
                <tr>
                    <td class="sno">{{ $i + 1 }}</td>
                    <td class="item">{{ $row['product_name'] }}</td>
                    <td class="num">{{ $fmt($row['qty']) }}</td>
                    <td class="num">{{ $fmtPrice($row['retail_price']) }}</td>
                    <td class="num">{{ $fmt($row['retail_amount']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="total-label">Total:</td>
                    <td class="total-val num">{{ $fmt($group['totals']['physical_qty']) }}</td>
                    <td></td>
                    <td class="total-val num">{{ $fmt($group['totals']['physical_amount']) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        @if(!empty($group['hold']))
        <div class="sub-title">Hold Stock</div>
        <table>
            <thead>
                <tr>
                    <th class="sno">S#</th>
                    <th>Items</th>
                    <th style="width:10%;">Qty</th>
                    <th style="width:14%;">Retail Price</th>
                    <th style="width:16%;">Retail Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['hold'] as $i => $row)
                <tr>
                    <td class="sno">{{ $i + 1 }}</td>
                    <td class="item">{{ $row['product_name'] }}</td>
                    <td class="num">{{ $fmt($row['qty']) }}</td>
                    <td class="num">{{ $fmtPrice($row['retail_price']) }}</td>
                    <td class="num">{{ $fmt($row['retail_amount']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="total-label">Total:</td>
                    <td class="total-val num">{{ $fmt($group['totals']['hold_qty']) }}</td>
                    <td></td>
                    <td class="total-val num">{{ $fmt($group['totals']['hold_amount']) }}</td>
                </tr>
            </tbody>
        </table>
        @endif
    @empty
        <p class="empty-msg">No physical or hold stock found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table>
        <tbody>
            <tr class="grand-row">
                <td class="sno"></td>
                <td class="total-label" style="width:50%;">Grand Total:</td>
                <td class="num" style="width:10%;">{{ $fmt($grand['physical_qty'] + $grand['hold_qty']) }}</td>
                <td style="width:14%;"></td>
                <td class="num" style="width:16%;">{{ $fmt($grand['physical_amount'] + $grand['hold_amount']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</body>
</html>
