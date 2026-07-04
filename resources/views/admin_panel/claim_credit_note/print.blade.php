<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Claim Credit Note - {{ $voucher->voucher_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 10px; background: #fff; font-size: 11px; }
        .container { width: 100%; max-width: 800px; margin: auto; border: 1px solid #eee; padding: 15px; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .info-item { display: flex; margin-bottom: 3px; }
        .info-label { width: 100px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: 600; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature { border-top: 1px solid #000; width: 130px; text-align: center; padding-top: 3px; font-size: 10px; }
        .summary-table { width: 250px; margin-left: auto; border: none; }
        .summary-table td { border: none; padding: 3px 6px; }
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
            <x-amt-print-brand doc-subtitle="CLAIM CREDIT NOTE VOUCHER" />
            <div style="text-align: right; flex-shrink:0;">
                <p style="margin:0;"><strong>Voucher:</strong> {{ $voucher->voucher_no }}</p>
                <p style="margin:0;"><strong>Status:</strong> {{ strtoupper($voucher->status) }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
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
                    <span class="info-label">Source Wh:</span>
                    <span>{{ $voucher->fromWarehouse->warehouse_name ?? 'Shop' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Target Wh:</span>
                    <span>{{ $voucher->toWarehouse->warehouse_name ?? 'Shop' }}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">S#</th>
                    <th style="width: 80px;">BTR#</th>
                    <th>Product Description</th>
                    <th style="width: 65px;">Price</th>
                    <th style="width: 65px;">Retail</th>
                    <th style="width: 40px;">Qty</th>
                    <th style="width: 70px;">Amount</th>
                    <th style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item->btr_no ?? '-' }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->retail_price, 2) }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between;">
            <div style="width: 60%;">
                @if($voucher->remarks)
                <p><strong>Remarks:</strong> {{ $voucher->remarks }}</p>
                @endif
            </div>
            <div style="width: 40%;">
                <table class="summary-table">
                    <tr>
                        <td class="text-right font-weight-bold">Subtotal:</td>
                        <td class="text-right">{{ number_format($voucher->subtotal, 2) }}</td>
                    </tr>

                    <tr>
                        <td class="text-right font-weight-bold">WHT ({{ number_format($voucher->wht_percent, 1) }}%):</td>
                        <td class="text-right">{{ number_format($voucher->wht_amount, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #000;">
                        <td class="text-right" style="font-size: 14px; font-weight: bold;">Net Total:</td>
                        <td class="text-right" style="font-size: 14px; font-weight: bold;">{{ number_format($voucher->net_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="signature">Prepared By</div>
            <div class="signature">Receiver's Signature</div>
            <div class="signature">Authorized Signature</div>
        </div>
    </div>
</body>
</html>
