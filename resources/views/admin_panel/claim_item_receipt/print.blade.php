<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Claim Receipt - {{ $voucher->voucher_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 10px; background: #fff; font-size: 12px; }
        .container { width: 100%; max-width: 800px; margin: auto; border: 1px solid #eee; padding: 15px; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .info-item { display: flex; margin-bottom: 3px; }
        .info-label { width: 110px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: 600; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature { border-top: 1px solid #000; width: 130px; text-align: center; padding-top: 3px; font-size: 11px; }
        @media print {
            body { padding: 0; }
            .container { border: none; }
            @page { size: A4; margin: 5mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header" style="align-items:flex-start;">
            <x-amt-print-brand doc-subtitle="CLAIM ITEM RECEIPT VOUCHER" />
            <div style="text-align: right; flex-shrink:0;">
                <p style="margin:0;"><strong>Voucher:</strong> {{ $voucher->voucher_no }}</p>
                <p style="margin:0;"><strong>Status:</strong> {{ strtoupper($voucher->status) }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Receipt Date:</span>
                    <span>{{ date('d-M-Y', strtotime($voucher->date)) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Party Name:</span>
                    <span>
                        @if($voucher->party_type == 'vendor')
                            {{ $voucher->vendor->name ?? '-' }}
                        @else
                            {{ $voucher->customer->customer_name ?? '-' }}
                        @endif
                    </span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Source Stock:</span>
                    <span>{{ $voucher->fromWarehouse->warehouse_name ?? 'Shop Stock' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Receipt Into:</span>
                    <span>{{ $voucher->toWarehouse->warehouse_name ?? 'Shop Stock' }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">S#</th>
                    <th style="width: 100px; text-align: center;">BTR#</th>
                    <th style="width: 80px;">Item ID</th>
                    <th>Product Description</th>
                    <th style="width: 100px; text-align: center;">Qty Receipt</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($voucher->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">{{ $item->btr_no ?? '-' }}</td>
                    <td>{{ $item->product_id }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                </tr>
                @php $totalQty += $item->quantity; @endphp
                @endforeach
                <tr style="font-weight: bold; background: #f9f9f9;">
                    <td colspan="4" style="text-align: right;">Total Receipt Items:</td>
                    <td style="text-align: center;">{{ number_format($totalQty, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($voucher->remarks)
        <p style="margin-top: 5px;"><strong>Remarks:</strong> {{ $voucher->remarks }}</p>
        @endif

        <div class="footer">
            <div class="signature">Prepared By</div>
            <div class="signature">Receiver's Signature</div>
            <div class="signature">Authorized Signature</div>
        </div>
    </div>
</body>
</html>
