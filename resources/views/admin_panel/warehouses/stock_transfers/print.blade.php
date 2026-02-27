<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Stock Transfer #{{ $transfer->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; background: #fff; font-size: 13px; }
        .container { width: 100%; max-width: 800px; margin: auto; border: 1px solid #eee; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-item { display: flex; margin-bottom: 5px; }
        .info-label { width: 130px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: 600; }
        .total-row td { font-weight: 700; background-color: #f9f9f9; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature { border-top: 1px solid #000; width: 150px; text-align: center; padding-top: 5px; }
        .status-badge {
            display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
            background: {{ $transfer->status === 'Posted' ? '#d4edda' : '#fff3cd' }};
            color: {{ $transfer->status === 'Posted' ? '#155724' : '#856404' }};
            border: 1px solid {{ $transfer->status === 'Posted' ? '#c3e6cb' : '#ffc107' }};
        }
        .arrow { font-size: 20px; font-weight: 700; color: #333; }
        @media print {
            body { padding: 0; }
            .container { border: none; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <div>
                <h1>Al-Madina Traders</h1>
                <p style="margin:2px 0; color:#555;">Stock Transfer Voucher</p>
            </div>
            <div style="text-align:right;">
                <p style="margin:0;"><strong>Transfer #:</strong> {{ $transfer->id }}</p>
                <p style="margin:4px 0;"><span class="status-badge">{{ strtoupper($transfer->status) }}</span></p>
                <p style="margin:0; font-size:11px; color:#666;">{{ \Carbon\Carbon::parse($transfer->created_at)->format('d-M-Y') }}</p>
            </div>
        </div>

        {{-- Warehouse Flow --}}
        <div style="text-align:center; margin-bottom:20px; padding:10px; background:#f8f9fa; border-radius:8px;">
            @if($transfer->from_shop)
                <span style="font-size:15px; font-weight:600;">Shop</span>
            @else
                <span style="font-size:15px; font-weight:600;">{{ $transfer->fromWarehouse->warehouse_name ?? '-' }}</span>
            @endif
            <span class="arrow" style="margin:0 16px;">→</span>
            <span style="font-size:15px; font-weight:600;">{{ $transfer->toWarehouse->warehouse_name ?? '-' }}</span>
            @if($transfer->to_shop)
                <span style="margin-left:10px; font-size:11px; background:#e3f2fd; color:#1565c0; padding:2px 8px; border-radius:10px;">To Shop</span>
            @endif
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">From Location:</span>
                    <span>
                        @if($transfer->from_shop)
                            Shop
                        @else
                            {{ $transfer->fromWarehouse->warehouse_name ?? '-' }}
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Prepared By:</span>
                    <span>{{ $transfer->creator->name ?? '-' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">To Warehouse:</span>
                    <span>{{ $transfer->toWarehouse->warehouse_name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d-M-Y') }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:40px;">S#</th>
                    <th style="width:100px;">Item ID</th>
                    <th>Product Description</th>
                    <th style="width:80px; text-align:center;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($transfer->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_id }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                </tr>
                @php $totalQty += $item->quantity; @endphp
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">Total Qty:</td>
                    <td style="text-align:center;">{{ $totalQty }}</td>
                </tr>
            </tbody>
        </table>

        @if($transfer->remarks)
        <p><strong>Remarks:</strong> {{ $transfer->remarks }}</p>
        @endif

        <div class="footer">
            <div class="signature">Prepared By</div>
            <div class="signature">Checked By</div>
            <div class="signature">Authorized By</div>
        </div>
    </div>
</body>
</html>
