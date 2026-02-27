<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt - {{ $booking->invoice_no }}</title>
    
    <!-- Poppins Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Thermal Printer (80mm) Optimized Design */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: #fff;
            color: #000;
            font-size: 11px;
            line-height: 1.5;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
        }

        .shop-name {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
            color: #000;
        }

        .shop-tagline {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 3px;
            color: #000;
        }

        .shop-address {
            font-size: 9px;
            font-weight: 400;
            line-height: 1.4;
            color: #000;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .divider-solid {
            border-top: 2px solid #000;
            margin: 6px 0;
        }

        .invoice-info {
            margin-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 10px;
            font-weight: 600;
            color: #000;
        }

        .info-label {
            font-weight: 700;
            color: #000;
        }

        .customer-info {
            margin-bottom: 8px;
            font-size: 10px;
            font-weight: 600;
            color: #000;
        }

        .items-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .items-header {
            font-weight: 700;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #000;
        }

        .item-row {
            padding: 4px 0;
            border-bottom: 1px dotted #000;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 2px;
            font-size: 11px;
            color: #000;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: 600;
            color: #000;
        }

        .totals-section {
            margin-top: 8px;
            border-top: 2px solid #000;
            padding-top: 6px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 10px;
            font-weight: 600;
            color: #000;
        }

        .total-row.grand {
            font-size: 14px;
            font-weight: 700;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dashed #000;
            color: #000;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px dashed #000;
            text-align: center;
            font-size: 9px;
            color: #000;
        }

        .thank-you {
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 11px;
            color: #000;
        }

        .developer-info {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dotted #000;
            font-size: 8px;
            line-height: 1.5;
            font-weight: 600;
            color: #000;
        }

        .developer-name {
            font-weight: 700;
            margin-bottom: 2px;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        strong {
            font-weight: 700;
            color: #000;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .print-btn:hover {
            background: #333;
        }
        
        .unposted-watermark {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: #ff0000;
            border: 2px solid #ff0000;
            padding: 5px;
            margin: 10px 0;
            transform: rotate(-5deg);
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print</button>

    <!-- Receipt Content -->
    <div class="receipt-header">
        <div class="shop-name">AL-MADINA BATTERY</div>
        <div class="shop-tagline">Battery & Accessories Dealer</div>
        <div class="shop-address">
            Main Road, City Name<br>
            Ph: +92 XXX XXXXXXX
        </div>
    </div>

    <div class="unposted-watermark">UNPOSTED (BOOKING)</div>

    <!-- Invoice Info -->
    <div class="invoice-info">
        <div class="info-row">
            <span class="info-label">Booking#:</span>
            <span>{{ $booking->invoice_no }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>{{ $booking->created_at ? $booking->created_at->format('d-M-Y h:i A') : now()->format('d-M-Y h:i A') }}</span>
        </div>
        @if($booking->manual_invoice)
        <div class="info-row">
            <span class="info-label">Manual Inv#:</span>
            <span>{{ $booking->manual_invoice }}</span>
        </div>
        @endif
    </div>

    <div class="divider"></div>

    <!-- Customer Info -->
    <div class="customer-info">
        <div class="info-row">
            <span class="info-label">Customer:</span>
            <span>{{ $booking->customer->customer_name ?? $booking->customer_id ?? 'Walk-in' }}</span>
        </div>
        @if($booking->tel)
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span>{{ $booking->tel }}</span>
        </div>
        @endif
        @if($booking->address)
        <div style="font-size: 9px; margin-top: 2px; font-weight: 400;">
            <strong>Address:</strong> {{ Str::limit($booking->address, 50) }}
        </div>
        @endif
    </div>

    <div class="divider-solid"></div>

    <!-- Items Table -->
    <div class="items-table">
        <div class="items-header">
            <span style="width: 50%;">Item</span>
            <span style="width: 15%; text-align: center;">Qty</span>
            <span style="width: 35%; text-align: right;">Amount</span>
        </div>

        @foreach($booking->items as $index => $item)
        <div class="item-row">
            <div class="item-name">{{ $index + 1 }}. {{ $item->product->name ?? 'Product' }}</div>
            <div class="item-details">
                <span>{{ number_format($item->sales_qty) }} × Rs.{{ number_format($item->sales_price, 2) }}</span>
                @if($item->discount_amount > 0)
                <span>-Rs.{{ number_format($item->discount_amount, 2) }}</span>
                @endif
                <span><strong>Rs.{{ number_format($item->amount, 2) }}</strong></span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Totals Section -->
    <div class="totals-section">
        @php
            $subtotal = $booking->items->sum('amount');
            $orderDisc = $booking->discount_amount ?? 0;
            $prevBalance = $booking->previous_balance ?? 0;
        @endphp

        @if($subtotal > 0)
        <div class="total-row">
            <span>Sub Total:</span>
            <span>Rs.{{ number_format($subtotal, 2) }}</span>
        </div>
        @endif

        @if($orderDisc > 0)
        <div class="total-row">
            <span>Order Discount:</span>
            <span>-Rs.{{ number_format($orderDisc, 2) }}</span>
        </div>
        @endif

        @if($prevBalance != 0)
        <div class="total-row">
            <span>Previous Balance:</span>
            <span>Rs.{{ number_format(abs($prevBalance), 2) }}</span>
        </div>
        @endif

        <div class="total-row grand">
            <span>EST. PAYABLE:</span>
            <span>Rs.{{ number_format($booking->total_balance, 2) }}</span>
        </div>
    </div>


    <!-- Developer Info -->
    <div class="footer">
        <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
        <div class="developer-info">
            <div class="developer-name text-center">Develop By: ProWave Software Solutions</div>
            <div class="text-center">
                +92 317 3836 223 | +92 317 3859 647
            </div>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
