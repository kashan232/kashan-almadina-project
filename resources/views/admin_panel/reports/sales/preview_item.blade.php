<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Item Report - Al-Madina Battery</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Montserrat', sans-serif; font-size: 11px; margin: 0; padding: 15mm; color: #333; background: #fff; }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #1a237e; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 28px; color: #1a237e; }
        .header .meta { text-align: right; color: #666; font-size: 10px; }

        .report-title { background: #e91e63; color: #fff; display: inline-block; padding: 5px 20px; border-radius: 20px; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1a237e; color: #fff; padding: 10px 5px; font-size: 10px; text-transform: uppercase; border: none; text-align: center; }
        td { padding: 8px 5px; border-bottom: 1px solid #eee; }
        
        .item-row { background: #f0f2f5; font-weight: 700; color: #1a237e; font-size: 13px; }
        .item-row td { border-bottom: 2px solid #1a237e; }
        
        .subtotal-row { background: #fff9c4; font-weight: 700; font-size: 12px; }
        .grand-total { background: #1a237e; color: #fff; font-weight: 700; font-size: 14px; }
        .grand-total td { padding: 15px 10px; }

        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-bold { font-weight: 700; }
        .text-pink { color: #e91e63; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <div>
            <h1>AL-MADINA</h1>
            <p style="margin:0; font-weight:600; color:#1a237e;">BATTERY SOLUTIONS & SERVICES</p>
        </div>
        <div class="meta">
            <strong>REPORT:</strong> ITEM WISE SALES NOTE<br>
            <strong>DATE:</strong> {{ $from_date ?? 'ALL' }} - {{ $to_date ?? 'ALL' }}<br>
            <strong>PRINTED:</strong> {{ now()->format('d-M-Y H:i') }}
        </div>
    </div>

    <div class="report-title">Sales Note Report (Item Wise)</div>

    <table>
        <thead>
            <tr>
                <th width="10%">Inv No.</th>
                <th width="10%">Date</th>
                <th width="35%" class="text-left">Party Name</th>
                <th width="10%">Qty</th>
                <th width="15%" class="text-right">Price</th>
                <th width="20%" class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $g_qty = 0; $g_amount = 0; @endphp

            @foreach($grouped as $productId => $items)
                @php
                    $product = $items->first()->product;
                    $s_qty = 0; $s_amount = 0;
                @endphp
                <tr class="item-row">
                    <td colspan="3" class="text-left">ITEM: {{ $product ? $product->name : 'N/A' }}</td>
                    <td colspan="3" class="text-right">BRAND: {{ $product && $product->brandRelation ? $product->brandRelation->name : '-' }}</td>
                </tr>
                @foreach($items as $item)
                    @php
                        $qty = $item->sales_qty;
                        $amount = $item->amount;
                        $s_qty += $qty; $s_amount += $amount;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $item->sale->invoice_no }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($item->sale->created_at)->format('d-m-y') }}</td>
                        <td class="text-left fw-bold">{{ $item->sale->customer ? $item->sale->customer->customer_name : 'CASH CUSTOMER' }}</td>
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-right">{{ number_format($item->sales_price, 0) }}</td>
                        <td class="text-right fw-bold">{{ number_format($amount, 0) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="3" class="text-right">ITEM TOTAL:</td>
                    <td class="text-center">{{ number_format($s_qty) }}</td>
                    <td></td>
                    <td class="text-right text-pink">Rs. {{ number_format($s_amount, 0) }}</td>
                </tr>
                @php $g_qty += $s_qty; $g_amount += $s_amount; @endphp
            @endforeach

            <tr class="grand-total">
                <td colspan="3" class="text-right">GRAND TOTAL SUMMARY:</td>
                <td class="text-center">{{ number_format($g_qty) }}</td>
                <td></td>
                <td class="text-right">Rs. {{ number_format($g_amount, 0) }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
