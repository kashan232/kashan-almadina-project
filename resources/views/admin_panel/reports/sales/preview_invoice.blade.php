<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Sales Note - Al-Madina Battery</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        .print-area {
            width: 210mm;
            margin: 0 auto;
        }
        .half-page {
            width: 100%;
            height: 148.5mm; /* Exact half A4 */
            background-color: #fff;
            padding: 15mm;
            box-sizing: border-box;
            position: relative;
            border-bottom: 1px dashed #bbb;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* Modern Header */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .logo-section h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1a237e;
            letter-spacing: 1px;
        }
        .logo-section p {
            margin: 2px 0;
            font-size: 10px;
            color: #666;
            font-weight: 600;
        }
        .invoice-title-box {
            text-align: right;
        }
        .invoice-title-box h2 {
            margin: 0;
            font-size: 22px;
            color: #e91e63;
            text-transform: uppercase;
        }
        .invoice-title-box span {
            font-size: 12px;
            color: #777;
        }

        /* Customer Info Strip */
        .info-strip {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #1a237e;
        }
        .info-block label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            color: #888;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .info-block div {
            font-size: 13px;
            font-weight: 600;
            color: #222;
        }

        /* Table Design */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            text-align: left;
            padding: 10px 8px;
            background: #1a237e;
            color: #fff;
            font-size: 10px;
            text-transform: uppercase;
            border: none;
        }
        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .data-table .text-right { text-align: right; }
        .data-table .text-center { text-align: center; }

        /* Summary Section */
        .summary-container {
            display: flex;
            justify-content: flex-end;
            margin-top: auto;
        }
        .summary-box {
            width: 250px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            border-bottom: none;
            padding-top: 10px;
        }
        .summary-row.total .val {
            font-size: 18px;
            font-weight: 700;
            color: #e91e63;
        }

        /* Footer / Signatures */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .sig-box {
            width: 150px;
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }
        .sig-text {
            font-size: 9px;
            font-weight: 600;
            color: #666;
        }

        @media print {
            body { background: none; }
            .half-page { page-break-after: always; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="print-area">
        @php $grand_total_qty = 0; $grand_total_amount = 0; @endphp

        @foreach($grouped as $customerId => $invoices)
            @foreach($invoices as $invoiceNo => $items)
                @php
                    $firstItem = $items->first();
                    $customer = $firstItem->sale->customer;
                    $saleDate = \Carbon\Carbon::parse($firstItem->sale->created_at)->format('d-M-Y');
                    $sub_qty = 0; $sub_invoice = 0;
                @endphp

                <div class="half-page">
                    <!-- Header -->
                    <div class="report-header">
                        <div class="logo-section">
                            <h1>AL-MADINA</h1>
                            <p>BATTERY SOLUTIONS & AUTO SERVICES</p>
                        </div>
                        <div class="invoice-title-box">
                            <h2>SALES NOTE</h2>
                            <span>No: <strong>{{ $invoiceNo }}</strong></span>
                        </div>
                    </div>

                    <!-- Info Strip -->
                    <div class="info-strip">
                        <div class="info-block">
                            <label>Billed To</label>
                            <div>{{ $customer ? $customer->customer_name : 'Cash Customer' }}</div>
                            <span style="font-size: 10px; color: #666;">{{ $customer ? $customer->mobile : '-' }}</span>
                        </div>
                        <div class="info-block" style="text-align: right;">
                            <label>Date of Issue</label>
                            <div>{{ $saleDate }}</div>
                            <span style="font-size: 10px; color: #666;">Time: {{ \Carbon\Carbon::parse($firstItem->sale->created_at)->format('h:i A') }}</span>
                        </div>
                    </div>

                    <!-- Table -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="45%">Description</th>
                                <th width="15%" class="text-center">Brand</th>
                                <th width="10%" class="text-center">Qty</th>
                                <th width="15%" class="text-right">Price</th>
                                <th width="15%" class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                @php
                                    $qty = $item->sales_qty;
                                    $price = $item->sales_price;
                                    $amount = $item->amount;
                                    $sub_qty += $qty;
                                    $sub_invoice += $amount;
                                @endphp
                                <tr>
                                    <td><strong>{{ $item->product ? $item->product->name : 'N/A' }}</strong></td>
                                    <td class="text-center">{{ $item->product && $item->product->brandRelation ? $item->product->brandRelation->name : '-' }}</td>
                                    <td class="text-center">{{ number_format($qty) }}</td>
                                    <td class="text-right">{{ number_format($price, 0) }}</td>
                                    <td class="text-right fw-bold">{{ number_format($amount, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Summary -->
                    <div class="summary-container">
                        <div class="summary-box">
                            <div class="summary-row">
                                <span>Total Quantity</span>
                                <span class="fw-bold">{{ number_format($sub_qty) }}</span>
                            </div>
                            <div class="summary-row total">
                                <span class="fw-bold" style="font-size: 14px;">NET TOTAL</span>
                                <span class="val">Rs. {{ number_format($sub_invoice, 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Signatures -->
                    <div class="signature-section">
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-text">Prepared By</div>
                        </div>
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <div class="sig-text">Authorized Signature</div>
                        </div>
                    </div>
                </div>

                @php $grand_total_qty += $sub_qty; $grand_total_amount += $sub_invoice; @endphp
            @endforeach
        @endforeach
    </div>

</body>
</html>
