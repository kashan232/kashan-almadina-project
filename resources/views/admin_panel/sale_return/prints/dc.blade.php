<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return DC - {{ $ret->invoice_no }}</title>
    <style>
        @media print { @page { size: 80mm auto; margin: 4mm; } .no-print { display: none !important; } }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; width: 80mm; margin: 0 auto; padding: 6mm 4mm; font-size: 11px; line-height: 1.35; }
        .header { text-align: center; margin-bottom: 8px; }
        .shop-name { font-size: 16px; font-weight: 800; text-transform: uppercase; }
        .shop-address { font-size: 9px; font-style: italic; margin-top: 2px; }
        .divider { border-top: 2px solid #000; margin: 8px 0; }
        .title { text-align: center; font-size: 13px; font-weight: 700; text-decoration: underline; margin-bottom: 8px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 10px; }
        .customer-box { border: 1px solid #000; padding: 6px 8px; margin-bottom: 8px; text-align: center; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #000; padding: 4px 5px; font-size: 10px; }
        th { background: #000; color: #fff; text-align: center; }
        .signature { margin-top: 28px; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 70%; margin: 0 auto 4px; }
        .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 16px; background: #000; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
@php
    $whId = optional($ret->items->first())->warehouse_id;
    $location = $whId == 0 ? 'Shop' : (\App\Models\Warehouse::find($whId)->warehouse_name ?? 'Warehouse');
    $qtyTotal = $ret->items->sum('sales_qty');
    $retDate = \Carbon\Carbon::parse($ret->current_date)->format('d-m-y');
@endphp
<button class="print-btn no-print" onclick="window.print()">Print DC</button>
<div class="header">
    <div class="shop-name">AL-MADINA TRADERS</div>
    <div class="shop-address">Shop# 2, United Hotel, Qazi Qayoom Road, Hyd</div>
    <div class="shop-address">0313-2836294, 0312-0252899</div>
</div>
<div class="divider"></div>
<div class="title">Return Delivery Challan</div>
<div class="meta-row">
    <span><strong>DC#:</strong> {{ $ret->invoice_no }}</span>
    <span><strong>Date:</strong> {{ $retDate }}</span>
</div>
<div class="customer-box">
    <div style="font-size:9px;direction:rtl;margin-bottom:4px;">کسٹمر کا نام اور پتہ</div>
    <strong>{{ $ret->party_name }}</strong>
    @if($ret->sale)
        <div style="font-size:9px;margin-top:2px;">Orig. Inv: {{ $ret->sale->invoice_no }}</div>
    @endif
</div>
<table>
    <thead>
        <tr>
            <th style="width:55%;">Description</th>
            <th style="width:15%;">Qty</th>
            <th style="width:30%;">Location</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ret->items as $item)
        <tr>
            <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
            <td style="text-align:center;">{{ number_format($item->sales_qty, 0) }}</td>
            <td style="text-align:center;">{{ $location }}</td>
        </tr>
        @endforeach
        <tr>
            <td><strong>Quantity Total: &gt;&gt;</strong></td>
            <td style="text-align:center;"><strong>{{ number_format($qtyTotal, 0) }}</strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
@if($ret->remarks)
<div style="margin-top:8px;font-size:10px;"><em>Remarks:</em> {{ $ret->remarks }}</div>
@endif
<div class="signature">
    <div class="signature-line"></div>
    <strong>Receiver Signature</strong>
</div>
</body>
</html>
