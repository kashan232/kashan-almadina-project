<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report only Retail Value</title>
    <style>
        @page {
            size: A4;
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 15px;
        }
        .report-title {
            color: #c2185b;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        .date-range span {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        /* Brand Heading Row */
        .brand-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 0 5px 0;
            font-weight: bold;
            font-size: 13px;
            color: #0d47a1;
        }

        /* Data Row */
        .item-row td {
            border: 1px solid #999;
        }

        /* Total Row */
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #c8e6c9;
            text-align: center;
        }
        .val-box {
            background-color: #cfd8dc;
            text-align: right;
        }

        /* Grand Total Row */
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 10px 6px;
            border-top: 2px solid #000;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
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
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <h1 class="report-title">Sales Report only Retail Value</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Category</th>
                <th width="45%" class="text-left">Item Name</th>
                <th width="10%">Qty</th>
                <th width="15%">Retail Price</th>
                <th width="15%">Retail Value</th>
            </tr>
        </thead>
        <tbody>
            @php $g_qty = 0; $g_retail_val = 0; @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="5" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $brandName => $items)
                @php
                    $b_qty = 0; $b_retail_val = 0;
                @endphp
                
                <!-- Brand Heading -->
                <tr class="brand-heading-row">
                    <td colspan="5" class="text-left">{{ strtoupper($brandName) }}</td>
                </tr>

                @foreach($items as $item)
                    @php
                        $qty = $item->sales_qty;
                        $price = $item->retail_price ?? 0;
                        $value = $qty * $price;

                        $b_qty += $qty;
                        $b_retail_val += $value;
                    @endphp
                    <tr class="item-row">
                        <td class="text-left">{{ $item->product && $item->product->category ? $item->product->category->name : 'N/A' }}</td>
                        <td class="text-left">{{ $item->product ? $item->product->name : 'N/A' }}</td>
                        <td class="text-center">{{ number_format($qty) }}</td>
                        <td class="text-right">{{ number_format($price, 0) }}</td>
                        <td class="text-right"><b>{{ number_format($value, 0) }}</b></td>
                    </tr>
                @endforeach

                <!-- Brand Total Row -->
                <tr class="total-row">
                    <td colspan="2" class="text-right">{{ $brandName }} Total:</td>
                    <td class="qty-box">{{ number_format($b_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($b_retail_val, 0) }}</td>
                </tr>

                @php $g_qty += $b_qty; $g_retail_val += $b_retail_val; @endphp
            @endforeach

            <!-- Grand Total -->
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($g_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($g_retail_val, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Page 1 of 1</div>
    </div>

</body>
</html>
