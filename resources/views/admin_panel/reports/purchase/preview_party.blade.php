<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Note Report (Party Wise)</title>
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

        .party-heading-row td {
            background-color: #f1f8e9;
            border: 1px solid #000;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 13px;
            color: #2e7d32;
        }

        .data-row td { border: 1px solid #999; }
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
        <h1 class="report-title">Purchase Note Report (Party Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">PUR No.</th>
                <th width="8%">Date</th>
                <th width="18%" class="text-left">Item Description</th>
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
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="9" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $vendorId => $items)
                @php
                    $vendor = $items->first()->purchase->vendor;
                    $party_qty = 0;
                    $party_purchase_amt = 0;
                    $party_retail_amt = 0;
                    $party_disc_amt = 0;
                @endphp
                
                <tr class="party-heading-row">
                    <td colspan="9" class="text-left">
                        VENDOR: {{ $vendor ? strtoupper($vendor->name) : 'N/A' }}
                    </td>
                </tr>

                @foreach($items as $item)
                    @php
                        $qty = $item->qty;
                        $purchase_p = $item->price;
                        $purchase_a = $item->line_total;
                        $add_disc = $item->item_discount ?? 0;

                        $latestPrice = $item->product->latestPrice;
                        $retail_p = $latestPrice ? $latestPrice->sale_retail_price : 0;
                        $retail_a = $retail_p * $qty;

                        $party_qty += $qty;
                        $party_purchase_amt += $purchase_a;
                        $party_retail_amt += $retail_a;
                        $party_disc_amt += $add_disc;
                    @endphp
                    <tr class="data-row">
                        <td class="text-center">{{ $item->purchase->invoice_no }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($item->purchase->created_at)->format('d-m-y') }}</td>
                        <td class="text-left">{{ $item->product ? $item->product->name : 'N/A' }}</td>
                        <td class="text-center">{{ number_format($qty) }}</td>
                        <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                        <td class="text-right">{{ number_format($retail_a, 0) }}</td>
                        <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                        <td class="text-right">{{ $add_disc > 0 ? number_format($add_disc, 0) : '' }}</td>
                        <td class="text-right bold-val">{{ number_format($purchase_a, 0) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="3" class="text-right">Party Total:</td>
                    <td class="qty-box">{{ number_format($party_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($party_retail_amt, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($party_disc_amt, 0) }}</td>
                    <td class="val-box">{{ number_format($party_purchase_amt, 0) }}</td>
                </tr>
                <tr style="height: 25px;"><td colspan="9" style="border:none;"></td></tr>

                @php
                    $grand_qty += $party_qty;
                    $grand_purchase_amt += $party_purchase_amt;
                    $grand_retail_amt += $party_retail_amt;
                    $grand_disc_amt += $party_disc_amt;
                @endphp
            @endforeach

            <tr class="grand-total-row">
                <td colspan="3" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_retail_amt, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_disc_amt, 0) }}</td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_purchase_amt, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Generated by ERP System</div>
    </div>

</body>
</html>
