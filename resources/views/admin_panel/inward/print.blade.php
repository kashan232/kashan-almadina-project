<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Inward Gatepass - {{ $gatepass->invoice_no }}</title>
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
        .header h1 { margin: 0; font-size: 24px; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item { display: flex; margin-bottom: 5px; }
        .info-label { width: 120px; font-weight: 600; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: 600; }
        .total-row td { font-weight: 700; background-color: #f9f9f9; }
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
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: {{ $gatepass->status === 'Posted' ? '#d4edda' : '#fff3cd' }};
            color: {{ $gatepass->status === 'Posted' ? '#155724' : '#856404' }};
            border: 1px solid {{ $gatepass->status === 'Posted' ? '#c3e6cb' : '#ffc107' }};
        }
        @media print {
            body { padding: 0; }
            .container { border: none; }
            @page {
                size: A4;
                margin: 10mm;
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
                <p style="margin:2px 0; color:#555;">Inward Gatepass Voucher</p>
            </div>
            <div style="text-align: right;">
                <p style="margin:0;"><strong>Invoice#:</strong> {{ $gatepass->invoice_no }}</p>
                <p style="margin:4px 0;"><span class="status-badge">{{ strtoupper($gatepass->status) }}</span></p>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span>{{ \Carbon\Carbon::parse($gatepass->gatepass_date)->format('d-M-Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Branch:</span>
                    <span>{{ $gatepass->branch->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Warehouse:</span>
                    <span>{{ $gatepass->warehouse->warehouse_name ?? '-' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Vendor:</span>
                    <span>{{ $gatepass->vendor->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Transport:</span>
                    <span>{{ $gatepass->transport_name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Bilty / GP#:</span>
                    <span>{{ $gatepass->gatepass_no ?? '-' }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:40px;">S#</th>
                    <th>Product Description</th>
                    <th style="width:110px;">Brand</th>
                    <th style="width:70px; text-align:center;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($gatepass->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->brand ?? '-' }}</td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                </tr>
                @php $totalQty += $item->qty; @endphp
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">Total Qty:</td>
                    <td style="text-align:center;">{{ $totalQty }}</td>
                </tr>
            </tbody>
        </table>

        @if($gatepass->remarks)
        <p><strong>Note:</strong> {{ $gatepass->remarks }}</p>
        @endif

        <div class="footer">
            <div class="signature">Prepared By</div>
            <div class="signature">Checked By</div>
            <div class="signature">Authorized By</div>
        </div>
    </div>
</body>
</html>
