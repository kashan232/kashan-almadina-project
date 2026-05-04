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
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .customer-row td {
            background-color: #f1f8e9;
            border: 1px solid #000;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 11px;
        }

        .item-row td { border: 1px solid #999; }
        .bold-val { font-weight: bold; }

        .subtotal-row td {
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
        <h1 class="report-title">Purchase Note Report (Invoice Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="22%">Item Description</th>
                <th width="10%">Sub-Category</th>
                <th width="5%">Qty</th>
                <th width="10%">Retail Price</th>
                <th width="12%">Retail Value</th>
                <th width="10%">Purchase Price</th>
                <th width="10%">Add. Disc</th>
                <th width="12%">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0; 
                $grand_purchase_amt = 0; 
                $grand_retail_amt = 0;
                $grand_disc_amt = 0;
                $grand_net_amt = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="8" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $vendorId => $invoices)
                @foreach($invoices as $invoiceNo => $items)
                    @php
                        $firstItem = $items->first();
                        $purchase = $firstItem->purchase;
                        $vendor = $purchase->vendor;
                        $purchaseDate = \Carbon\Carbon::parse($purchase->created_at)->format('d-m-y');
                        
                        $inv_qty = 0;
                        $inv_purchase_amt = 0;
                        $inv_retail_amt = 0;
                        $inv_disc_amt = 0;
                        $inv_net_amt = 0;
                    @endphp
                    
                    <!-- Invoice Heading Row -->
                    <tr class="customer-row">
                        <td colspan="4" class="text-left">
                            PUR No: <b style="color: #000;">{{ $invoiceNo }}</b> &nbsp;&nbsp;&nbsp; 
                            Date: <b style="color: #000;">{{ $purchaseDate }}</b>
                        </td>
                        <td colspan="4" class="text-right">
                            Vendor: <b style="color: #000;">{{ $vendor ? strtoupper($vendor->name) : 'N/A' }}</b>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    @foreach($items as $item)
                        @php
                            $qty = $item->qty;
                            $purchase_p = $item->price;
                            
                            $latestPrice = $item->product->latestPrice;
                            $retail_p = $latestPrice ? $latestPrice->sale_retail_price : 0;
                            $retail_a = $retail_p * $qty;

                            $add_disc = $item->item_discount ?? 0;
                            $net_a = $item->line_total;

                            $inv_qty += $qty;
                            $inv_retail_amt += $retail_a;
                            $inv_disc_amt += $add_disc;
                            $inv_net_amt += $net_a;
                        @endphp
                        <tr class="item-row">
                            <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                            <td class="text-center">{{ $item->product && $item->product->sub_category_relation ? $item->product->sub_category_relation->name : '-' }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                            <td class="text-right">{{ number_format($retail_a, 0) }}</td>
                            <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                            <td class="text-right">{{ $add_disc > 0 ? number_format($add_disc, 0) : '' }}</td>
                            <td class="text-right bold-val">{{ number_format($net_a, 0) }}</td>
                        </tr>
                    @endforeach

                    <!-- Invoice Total Row -->
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total:</td>
                        <td class="qty-box">{{ number_format($inv_qty) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_retail_amt, 0) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_disc_amt, 0) }}</td>
                        <td class="val-box">{{ number_format($inv_net_amt, 0) }}</td>
                    </tr>
                    <tr style="height: 15px;"><td colspan="8" style="border:none;"></td></tr>

                    @php
                        $grand_qty += $inv_qty;
                        $grand_retail_amt += $inv_retail_amt;
                        $grand_disc_amt += $inv_disc_amt;
                        $grand_net_amt += $inv_net_amt;
                    @endphp
                @endforeach
            @endforeach

            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_retail_amt, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_disc_amt, 0) }}</td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_net_amt, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Generated by ERP System</div>
    </div>

</body>
</html>
