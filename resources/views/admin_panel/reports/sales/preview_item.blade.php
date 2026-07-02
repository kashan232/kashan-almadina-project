<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Note Report (Item Wise)</title>
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

        /* Item Heading Row */
        .item-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 6px 5px 6px;
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
        }

        .party-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 6px 5px 6px;
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
        }

        .inv-meta-row td {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 6px 6px 4px 6px;
            font-weight: bold;
            font-size: 11px;
        }
        .party-name {
            color: #0d47a1;
            text-decoration: underline;
        }

        /* Data Row */
        .data-row td {
            border: 1px solid #999;
        }
        .bold-val {
            font-weight: bold;
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
            background-color: #fff;
            text-align: right;
        }
        .sales-amt-box {
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
        @include('admin_panel.reports.sales.partials.report_line_styles')
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <h1 class="report-title">Sales Note Report (Item Wise)</h1>
        <div class="date-range">
            From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
            To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="14%" class="text-left">Customer</th>
                <th width="8%">Type</th>
                <th width="5%">Qty</th>
                <th width="9%">Retail Price</th>
                <th width="11%">Retail Amount</th>
                <th width="10%">Rate</th>
                <th width="12%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0; 
                $grand_retail_amt = 0; 
                $grand_sales_amt = 0; 
                $grand_invoice_amt = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="7" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $productId => $items)
                @php
                    $product = $items->first()->product;
                    $item_qty = 0;
                    $item_retail_amt = 0;
                    $item_sales_amt = 0;
                    $item_invoice_amt = 0;
                @endphp
                
                <!-- Item Heading -->
                <tr class="item-heading-row">
                    <td colspan="4" class="text-left">{{ $product ? strtoupper($product->name) : 'N/A' }}</td>
                    <td colspan="3" class="text-left">{{ $product && $product->brandRelation ? strtoupper($product->brandRelation->name) : '-' }}</td>
                </tr>

                @foreach($items->groupBy(fn ($row) => $row->sale->invoice_no) as $invoiceNo => $invoiceItems)
                    @php
                        $saleDate = \Carbon\Carbon::parse($invoiceItems->first()->sale->created_at)->format('d-m-y');
                        $invCustomer = $invoiceItems->first()->sale->customer;
                        $customerName = $invCustomer ? strtoupper($invCustomer->customer_name) : 'CASH CUSTOMER';
                        $customerCnic = $invCustomer?->cnic ?? '';
                    @endphp
                    @include('admin_panel.reports.sales.partials.heading_inv_date', [
                        'colspan' => 7,
                        'invoiceNo' => $invoiceNo,
                        'saleDate' => $saleDate,
                    ])
                    @include('admin_panel.reports.sales.partials.heading_customer', [
                        'colspan' => 7,
                        'customerName' => $customerName,
                        'customerCnic' => $customerCnic,
                    ])

                @foreach($invoiceItems as $item)
                    @php
                        $qty = $item->sales_qty;
                        $retail_p = $item->retail_price ?? 0;
                        $retail_a = $retail_p * $qty;
                        $sales_p = $item->sales_rate > 0 ? $item->sales_rate : ($item->sales_qty > 0 ? ($item->sales_price - ($item->discount_amount / $item->sales_qty)) : $item->sales_price);
                        $sales_a = $item->amount;
                        $add_disc = $item->discount_amount ?? 0;
                        $invoice_a = $sales_a - $add_disc;

                        $item_qty += $qty;
                        $item_retail_amt += $retail_a;
                        $item_sales_amt += $sales_a;
                        $item_invoice_amt += $invoice_a;
                    @endphp
                    <tr class="data-row {{ ($item->entry_type ?? 'sale') === 'sale_return' ? 'return-row' : '' }}">
                        @include('admin_panel.reports.sales.partials.customer_cell', ['item' => $item])
                        @include('admin_panel.reports.sales.partials.type_cell', ['item' => $item])
                        <td class="text-center">{{ number_format($qty) }}</td>
                        <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                        <td class="text-right bold-val">{{ number_format($retail_a, 0) }}</td>
                        <td class="text-right">{{ number_format($sales_p, 0) }}</td>
                        <td class="text-right bold-val">{{ number_format($sales_a, 0) }}</td>
                    </tr>
                @endforeach
                @endforeach

                <!-- Item Total Row -->
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total:</td>
                    <td class="qty-box">{{ number_format($item_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($item_retail_amt, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="sales-amt-box">{{ number_format($item_sales_amt, 0) }}</td>
                </tr>
                <tr style="height: 25px;"><td colspan="7" style="border:none;"></td></tr>

                @php
                    $grand_qty += $item_qty;
                    $grand_retail_amt += $item_retail_amt;
                    $grand_sales_amt += $item_sales_amt;
                    $grand_invoice_amt += $item_invoice_amt;
                @endphp
            @endforeach

            <!-- Grand Total -->
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_retail_amt, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="sales-amt-box" style="background-color: #bbdefb;">{{ number_format($grand_sales_amt, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>{{ now()->format('l, F d, Y') }}</div>
        <div>Page 1 of 1</div>
    </div>

</body>
</html>
