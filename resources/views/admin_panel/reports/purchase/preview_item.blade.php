<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Note Report (Item Wise)</title>
    <style>
        @page {
            size: A4 portrait;
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
            color: #0d47a1;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 5px;
            font-size: 12px;
            font-weight: bold;
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

        .item-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 6px 5px 6px;
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
        }

        .data-row td {
            border: 1px solid #999;
        }
        .bold-val { font-weight: bold; }

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
            background-color: #fff;
            text-align: right;
        }

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
        <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <h1 class="report-title">Purchase Note Report (Item Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">PUR No.</th>
                <th width="15%">Date</th>
                <th width="30%" class="text-left">Vendor Name</th>
                <th width="10%">Qty</th>
                <th width="15%">Price</th>
                <th width="15%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0; 
                $grand_amount = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="6" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $productId => $items)
                @php
                    $product = $items->first()->product;
                    $item_qty = 0;
                    $item_amount = 0;
                @endphp
                
                <tr class="item-heading-row">
                    <td colspan="6" class="text-left" style="border-top: 2px solid #000;">
                        ITEM: {{ $product ? strtoupper($product->name) : 'N/A' }} 
                        <span class="ms-3" style="color: #666;">( {{ $product && $product->sub_category_relation ? strtoupper($product->sub_category_relation->name) : '-' }} )</span>
                    </td>
                </tr>

                @foreach($items as $item)
                    @php
                        $qty = $item->qty;
                        $amount = $item->line_total;

                        $item_qty += $qty;
                        $item_amount += $amount;
                    @endphp
                    <tr class="data-row">
                        <td class="text-center">{{ $item->purchase->invoice_no }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($item->purchase->created_at)->format('d-m-y') }}</td>
                        <td class="text-left">{{ $item->purchase->vendor ? strtoupper($item->purchase->vendor->name) : 'N/A' }}</td>
                        <td class="text-center">{{ number_format($qty) }}</td>
                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right bold-val">{{ number_format($amount, 2) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="3" class="text-right">Total:</td>
                    <td class="qty-box">{{ number_format($item_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($item_amount, 2) }}</td>
                </tr>
                <tr style="height: 25px;"><td colspan="6" style="border:none;"></td></tr>

                @php
                    $grand_qty += $item_qty;
                    $grand_amount += $item_amount;
                @endphp
            @endforeach

            <tr class="grand-total-row">
                <td colspan="3" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Generated by ERP System</div>
    </div>

</body>
</html>
