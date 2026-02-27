<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Stock Wastage - GWN: {{ $wastage->gwn_id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            font-size: 13px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: auto;
            border: 1px solid #eee;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            width: 120px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .total-row td {
            font-weight: 700;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            border-top: 1px solid #000;
            width: 150px;
            text-align: center;
            padding-top: 5px;
        }
        @media print {
            body { padding: 0; }
            .container { border: none; }
            @page {
                size: A4;
                margin: 0;
            }
            .half-page {
                height: 50vh;
                padding: 20px;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container half-page">
        <div class="header">
            <div>
                <h1>Al-Madina Traders</h1>
                <p style="margin:2px 0;">Stock Wastage Voucher</p>
            </div>
            <div style="text-align: right;">
                <p style="margin:0;"><strong>GWN ID:</strong> {{ $wastage->gwn_id }}</p>
                <p style="margin:0;"><strong>Status:</strong> {{ strtoupper($wastage->status) }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span>{{ date('d-m-Y', strtotime($wastage->date)) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Warehouse:</span>
                    <span>{{ $wastage->warehouse->warehouse_name ?? '-' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Expense Head:</span>
                    <span>{{ $wastage->accountHead->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Account:</span>
                    <span>{{ $wastage->account->title ?? '-' }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">S#</th>
                    <th style="width: 80px;">Item ID</th>
                    <th>Product</th>
                    <th style="width: 80px; text-align: center;">Qty</th>
                    <th style="width: 100px; text-align: right;">Price</th>
                    <th style="width: 120px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($wastage->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_id }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ number_format($item->qty, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @php $totalQty += $item->qty; @endphp
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Total:</td>
                    <td style="text-align: center;">{{ number_format($totalQty, 2) }}</td>
                    <td></td>
                    <td style="text-align: right;">{{ number_format($wastage->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($wastage->remarks)
        <p><strong>Remarks:</strong> {{ $wastage->remarks }}</p>
        @endif

        <div class="footer">
            <div class="signature">Prepared By</div>
            <div class="signature">Approved By</div>
        </div>
    </div>
</body>
</html>
