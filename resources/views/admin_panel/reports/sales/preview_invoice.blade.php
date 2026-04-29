<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Note Report (Invoice Wise)</title>
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

        /* Customer Row */
        .customer-row {
            background-color: #e3f2fd;
            border-top: 2px solid #000;
        }
        .customer-row td {
            color: #0d47a1;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
        }

        /* Invoice Meta Row (Date/InvNo) */
        .inv-meta-row td {
            background-color: #fff;
            border-bottom: none;
            padding: 2px 6px;
            font-weight: bold;
        }

        /* Data Row */
        .item-row td {
            border-bottom: 1px solid #ccc;
        }
        .item-row .bold-val {
            font-weight: bold;
        }

        /* Subtotal Row */
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
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

        /* Grand Total Row */
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
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <h1 class="report-title">Sales Note Report (Invoice Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30%">Item Description</th>
                <th width="10%">Brand</th>
                <th width="5%">Qty</th>
                <th width="8%">Retail Price</th>
                <th width="10%">Retail Amount</th>
                <th width="8%">Sales Price</th>
                <th width="10%">Sales Amount</th>
                <th width="8%">Add. Disc</th>
                <th width="11%">Invoice Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0; 
                $grand_retail_amt = 0; 
                $grand_sales_amt = 0; 
                $grand_invoice_amt = 0; 
            @endphp

            @foreach($grouped as $customerId => $invoices)
                @php
                    $firstInvoice = $invoices->first();
                    $customer = $firstInvoice->first()->sale->customer;
                @endphp
                
                <!-- Customer Name Row -->
                <tr class="customer-row">
                    <td colspan="9">
                        {{ $customer ? strtoupper($customer->customer_name) : 'CASH CUSTOMER' }} - 
                        {{ $customer ? $customer->cnic : '' }}
                    </td>
                </tr>

                @foreach($invoices as $invoiceNo => $items)
                    @php
                        $firstItem = $items->first();
                        $saleDate = \Carbon\Carbon::parse($firstItem->sale->created_at)->format('d-m-y');
                        $inv_qty = 0;
                        $inv_retail_amt = 0;
                        $inv_sales_amt = 0;
                        $inv_invoice_amt = 0;
                    @endphp
                    
                    <!-- Invoice Info Strip -->
                    <tr class="inv-meta-row">
                        <td colspan="5" style="border:none;"></td>
                        <td colspan="2" class="text-left" style="border:none;">Date. {{ $saleDate }}</td>
                        <td colspan="2" class="text-left" style="border:none;">Inv.No. {{ $invoiceNo }}</td>
                    </tr>

                    <!-- Data Rows -->
                    @foreach($items as $item)
                        @php
                            $qty = $item->sales_qty;
                            $retail_p = $item->retail_price ?? 0;
                            $retail_a = $retail_p * $qty;
                            $sales_p = $item->sales_price;
                            $sales_a = $item->amount;
                            $add_disc = $item->discount_amount ?? 0;
                            $invoice_a = $sales_a - $add_disc;

                            $inv_qty += $qty;
                            $inv_retail_amt += $retail_a;
                            $inv_sales_amt += $sales_a;
                            $inv_invoice_amt += $invoice_a;
                        @endphp
                        <tr class="item-row">
                            <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                            <td class="text-center">{{ $item->product && $item->product->brandRelation ? $item->product->brandRelation->name : '-' }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                            <td class="text-right">{{ number_format($retail_a, 0) }}</td>
                            <td class="text-right">{{ number_format($sales_p, 0) }}</td>
                            <td class="text-right bold-val">{{ number_format($sales_a, 0) }}</td>
                            <td class="text-right">{{ $add_disc > 0 ? number_format($add_disc, 0) : '' }}</td>
                            <td class="text-right bold-val">{{ number_format($invoice_a, 0) }}</td>
                        </tr>
                    @endforeach

                    <!-- Invoice Total Row -->
                    <tr class="subtotal-row">
                        <td class="text-left">{{ $saleDate }}</td>
                        <td class="text-right">Total:</td>
                        <td class="qty-box">{{ number_format($inv_qty) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_retail_amt, 0) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_sales_amt, 0) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_invoice_amt, 0) }}</td>
                    </tr>

                    @php
                        $grand_qty += $inv_qty;
                        $grand_retail_amt += $inv_retail_amt;
                        $grand_sales_amt += $inv_sales_amt;
                        $grand_invoice_amt += $inv_invoice_amt;
                    @endphp
                @endforeach
            @endforeach

            <!-- Grand Total -->
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="grand-qty-box">{{ number_format($grand_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="grand-val-box">{{ number_format($grand_retail_amt, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="grand-val-box">{{ number_format($grand_sales_amt, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="grand-val-box">{{ number_format($grand_invoice_amt, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Page 1 of 1</div>
    </div>

</body>
</html>
