<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Adjustment Print - {{ $adjustment->adj_id }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .items-table th { background-color: #f2f2f2; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
        .posted-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 128, 0, 0.1);
            z-index: -1;
            font-weight: bold;
            text-transform: uppercase;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px;">🖨️ Print Now</button>
    </div>

    @if($adjustment->status == 'Posted')
        <div class="posted-watermark">POSTED</div>
    @endif

    <div class="header">
        <h2>Warehouse Stock Adjustment</h2>
        <div style="margin-top: 5px;">Al-Madina Batteries</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Adjustment ID:</strong></td>
            <td style="width: 35%;">{{ $adjustment->adj_id }}</td>
            <td style="width: 15%;"><strong>Date:</strong></td>
            <td style="width: 35%;">{{ \Carbon\Carbon::parse($adjustment->date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td><strong>Warehouse:</strong></td>
            <td>{{ $adjustment->warehouse->warehouse_name ?? '-' }}</td>
            <td><strong>Status:</strong></td>
            <td><strong style="color: {{ $adjustment->status == 'Posted' ? 'green' : 'orange' }}">{{ strtoupper($adjustment->status) }}</strong></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">S.No</th>
                <th>Product Name</th>
                <th style="width: 20%; text-align: center;">Updated Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustment->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->name ?? 'N/A' }}</td>
                <td style="text-align: center; font-weight: bold;">{{ number_format($item->qty, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($adjustment->remarks)
    <div style="margin-top: 20px;">
        <strong>Remarks:</strong> {{ $adjustment->remarks }}
    </div>
    @endif

    <div class="footer">
        <div class="signature">Prepared By</div>
        <div class="signature">Verified By</div>
        <div class="signature">Store Keeper</div>
    </div>

    <script>
        // Auto-print if needed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
