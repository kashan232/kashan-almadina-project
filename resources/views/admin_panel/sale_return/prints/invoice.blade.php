<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale Return Invoice - {{ $ret->invoice_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @media print { body { background: #fff; } .no-print { display: none !important; } .page { box-shadow: none; margin: 0; width: 100%; } }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', Arial, sans-serif; background: #f4f4f4; color: #000; font-size: 10pt; }
        .page { width: 960px; max-width: 100%; margin: 16px auto; padding: 24px 28px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.08); position: relative; }
        #watermark { position: absolute; left: 50%; top: 46%; transform: translate(-50%, -50%) rotate(-18deg); width: 680px; opacity: 0.07; pointer-events: none; }
        .content { position: relative; z-index: 1; }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .brand h1 { font-size: 34px; font-weight: 700; margin-bottom: 4px; }
        .brand p { font-size: 9pt; line-height: 1.45; }
        .doc-box { border: 2px solid #222; padding: 6px 14px; text-align: center; font-weight: 700; margin-top: 8px; display: inline-block; color: #dc3545; }
        .doc-box small { display: block; font-weight: 400; font-size: 8pt; margin-top: 2px; color: #000; }
        hr.sep { border: none; border-top: 2px solid #000; margin: 12px 0 14px; }
        .info-row { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; font-size: 9.5pt; }
        .inv-meta { min-width: 210px; border-left: 1px solid #000; padding-left: 14px; }
        .inv-meta div { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #000; padding: 5px 6px; font-size: 9pt; }
        table.items th { background: #d9d9d9; font-weight: 700; text-align: center; }
        table.items td.num { text-align: right; }
        table.items td.center { text-align: center; }
        .summary { width: 52%; margin-left: auto; font-size: 9pt; line-height: 1.6; margin-top: 8px; }
        .summary-line { display: flex; justify-content: space-between; gap: 10px; }
        .summary-line.bold { font-weight: 700; margin-top: 4px; }
        .summary-line .red { color: #dc3545; font-weight: 700; }
        .footer { margin-top: 18px; display: flex; justify-content: space-between; font-size: 9pt; }
        .signature { width: 220px; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-bottom: 4px; }
        .print-btn { position: fixed; top: 12px; right: 12px; z-index: 99; padding: 10px 18px; background: #000; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
@php
    $retDate = \Carbon\Carbon::parse($ret->current_date)->format('d-M-y');
    $qtyTotal = $ret->items->sum('sales_qty');
    $subtotal = $ret->items->sum('amount');
@endphp
<button class="print-btn no-print" onclick="window.print()">Print Invoice</button>
<div class="page">
    <x-amt-watermark />
    <div class="content">
        <header>
            <div class="brand">
                <div class="brand-name" style="font-family:'Times New Roman',serif;font-size:28px;font-weight:700;">Al-Madina Traders</div>
                <p>Shop#2, United Hotel, Qazi Qayoom Road, Hyderabad</p>
                <p>Mob / Whatsapp: 0312-0252899, Tel: 022-2780942</p>
            </div>
            <div style="text-align:right;">
                <x-amt-logo width="170px" style="display:block;margin-left:auto;" />
                <div class="doc-box">
                    Sale Return
                    <small>Credit Note</small>
                </div>
            </div>
        </header>
        <hr class="sep">
        <div class="info-row">
            <div>
                <div><strong>Party:</strong> {{ $ret->party_name }}</div>
                <div><strong>Type:</strong> {{ ucfirst($ret->party_type) }}</div>
                @if($ret->sale)
                    <div><strong>Original Sale Inv:</strong> {{ $ret->sale->invoice_no }}</div>
                @endif
            </div>
            <div class="inv-meta">
                <div><strong>Return No.</strong> <span>{{ $ret->invoice_no }}</span></div>
                <div><strong>Date</strong> <span>{{ $retDate }}</span></div>
                <div><strong>Status</strong> <span>{{ $ret->status }}</span></div>
            </div>
        </div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:6%;">S no.</th>
                    <th style="width:8%;">Qty</th>
                    <th style="width:46%;">Description</th>
                    <th style="width:20%;">Rate</th>
                    <th style="width:20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ret->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ number_format($item->sales_qty, 0) }}</td>
                    <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
                    <td class="num">{{ number_format($item->sales_price, 2) }}</td>
                    <td class="num">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="summary">
            <div class="summary-line"><span>Sub Total:</span><span>{{ number_format($subtotal, 2) }}</span></div>
            @if($ret->discount_amount > 0)
            <div class="summary-line"><span>Less: Discount:</span><span>{{ number_format($ret->discount_amount, 2) }}</span></div>
            @endif
            <div class="summary-line bold"><span>NET RETURN:</span><span class="red">{{ number_format($ret->total_balance, 2) }}</span></div>
        </div>
        @if($ret->remarks)
        <div style="margin-top:12px;font-size:9pt;"><strong>Remarks:</strong> {{ $ret->remarks }}</div>
        @endif
        <div class="footer">
            <div>Thank You for Business with us.</div>
            <div class="signature"><div class="signature-line"></div>Authorized Signature</div>
        </div>
    </div>
</div>
</body>
</html>
