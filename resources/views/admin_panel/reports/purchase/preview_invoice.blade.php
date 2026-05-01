<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Note Report (Invoice Wise)</title>
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

        .party-row {
            background-color: #f1f8e9;
            border-top: 2px solid #000;
        }
        .party-row td {
            color: #2e7d32;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
        }

        .item-row td {
            border-bottom: 1px solid #ccc;
        }
        .bold-val {
            font-weight: bold;
        }

        .subtotal-row td {
            font-weight: bold;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #c8e6c9;
            text-align: center;
            border: 1px solid #000 !important;
        }
        .val-box {
            background-color: #fff;
            text-align: right;
            border: 1px solid #000 !important;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border-top: 2px solid #000;
        }
        .grand-qty-box {
            background-color: #cfd8dc;
            text-align: center;
            border: 1px solid #000 !important;
        }
        .grand-val-box {
            background-color: #bbdefb;
            text-align: right;
            border: 1px solid #000 !important;
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
        <h1 class="report-title">Purchase Note Report (Invoice Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30%">Item Description</th>
                <th width="15%">Sub-Category</th>
                <th width="10%">Qty</th>
                <th width="15%">Unit Price</th>
                <th width="15%">Line Amount</th>
                <th width="15%">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0; 
                $grand_amount = 0; 
            @endphp

            @foreach($grouped as $vendorId => $invoices)
                @foreach($invoices as $invoiceNo => $items)
                    @php
                        $firstItem = $items->first();
                        $purchase = $firstItem->purchase;
                        $vendor = $purchase->vendor;
                        $purchaseDate = \Carbon\Carbon::parse($purchase->created_at)->format('d-m-y');
                        
                        $inv_qty = 0;
                        $inv_amount = 0;
                    @endphp
                    
                    <tr class="party-row">
                        <td colspan="3" class="text-left">
                            PUR No: <b style="color: #000;">{{ $invoiceNo }}</b> &nbsp;&nbsp;&nbsp; 
                            Date: <b style="color: #000;">{{ $purchaseDate }}</b>
                        </td>
                        <td colspan="3" class="text-right">
                            Vendor: <b style="color: #000;">{{ $vendor ? strtoupper($vendor->name) : 'N/A' }}</b>
                        </td>
                    </tr>

                    @foreach($items as $item)
                        @php
                            $qty = $item->qty;
                            $price = $item->price;
                            $amount = $item->line_total;

                            $inv_qty += $qty;
                            $inv_amount += $amount;
                        @endphp
                        <tr class="item-row">
                            <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                            <td class="text-center">{{ $item->product && $item->product->sub_category_relation ? $item->product->sub_category_relation->name : '-' }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($price, 2) }}</td>
                            <td class="text-right">{{ number_format($amount, 2) }}</td>
                            <td class="text-right bold-val">{{ number_format($amount, 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total:</td>
                        <td class="qty-box">{{ number_format($inv_qty) }}</td>
                        <td colspan="2" style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_amount, 2) }}</td>
                    </tr>
                    <tr style="height: 20px;"><td colspan="6" style="border:none;"></td></tr>

                    @php
                        $grand_qty += $inv_qty;
                        $grand_amount += $inv_amount;
                    @endphp
                @endforeach
            @endforeach

            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="grand-qty-box">{{ number_format($grand_qty) }}</td>
                <td colspan="2" style="border:none; background:none;"></td>
                <td class="grand-val-box">{{ number_format($grand_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Generated by ERP System</div>
    </div>

</body>
</html>
