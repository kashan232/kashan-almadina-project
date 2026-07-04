<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Challan - {{ $sale->invoice_no }}</title>
    <style>
        @media print {
            @page { size: 80mm auto; margin: 4mm; }
            .no-print { display: none !important; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            width: 80mm;
            margin: 0 auto;
            padding: 6mm 4mm;
            color: #000;
            font-size: 11px;
            line-height: 1.35;
        }
        .header { text-align: center; margin-bottom: 8px; }
        .shop-name {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .shop-address { font-size: 9px; font-style: italic; margin-top: 2px; }
        .shop-phone { font-size: 9px; margin-top: 2px; }
        .divider { border-top: 2px solid #000; margin: 8px 0; }
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 8px;
        }
        .meta { margin-bottom: 8px; font-size: 10px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .customer-box {
            border: 1px solid #000;
            padding: 6px 8px;
            margin-bottom: 8px;
            min-height: 42px;
            text-align: center;
            font-size: 10px;
        }
        .customer-box .urdu { font-size: 9px; margin-bottom: 4px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #000; padding: 4px 5px; font-size: 10px; }
        th { background: #000; color: #fff; font-weight: 700; text-align: center; }
        td.desc { text-align: left; }
        td.num { text-align: center; }
        .total-row td { font-weight: 700; }
        .remarks { margin-top: 10px; font-size: 10px; font-style: italic; text-decoration: underline; }
        .remarks-line { border-bottom: 1px solid #000; min-height: 18px; margin-top: 4px; }
        .signature { margin-top: 28px; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 70%; margin: 0 auto 4px; }
        .print-btn {
            position: fixed; top: 10px; right: 10px;
            padding: 8px 16px; background: #000; color: #fff;
            border: none; border-radius: 4px; cursor: pointer;
        }
    </style>
</head>
<body>
@php
    $pType = strtolower($sale->partyType ?? 'customer');
    if ($pType === 'vendor') {
        $partyName = $sale->vendor->name ?? 'N/A';
    } else {
        $partyName = $sale->customer->customer_name ?? 'Walk-in Customer';
    }
    $partyAddress = trim($sale->address ?? ($sale->customer->address ?? ''));
    $saleDate = $sale->entry_date
        ? \Carbon\Carbon::parse($sale->entry_date)->format('d-m-y')
        : ($sale->created_at ? $sale->created_at->format('d-m-y') : now()->format('d-m-y'));
    $dcNo = 'AMT-' . ($sale->entry_date ? \Carbon\Carbon::parse($sale->entry_date)->format('Y') : date('Y')) . '-' . $sale->id;
    $qtyTotal = $sale->items->sum('sales_qty');
@endphp

<button class="print-btn no-print" onclick="window.print()">Print DC</button>

<div class="header">
    <div class="shop-name">AL-MADINA TRADERS</div>
    <div class="shop-address">Shop# 2, United Hotel, Qazi Qayoom Road, Hyd</div>
    <div class="shop-phone">0313-2836294, 0313-2331182, 0312-0252899</div>
</div>

<div class="divider"></div>
<div class="title">Delivery Challan</div>

<div class="meta">
    <div class="meta-row">
        <span><strong>D.C#:</strong> {{ $dcNo }}</span>
        <span><strong>Date:</strong> {{ $saleDate }}</span>
    </div>
</div>

<div class="customer-box">
    <div class="urdu">کسٹمر کا نام اور پتہ</div>
    <strong>{{ $partyName }}</strong>
    @if($partyAddress)
        <div style="font-size:9px;margin-top:2px;">{{ $partyAddress }}</div>
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
        @foreach($sale->items as $item)
        @php
            $location = ($item->warehouse_id == 0)
                ? 'Shop'
                : ($item->warehouse->warehouse_name ?? 'Warehouse');
        @endphp
        <tr>
            <td class="desc">{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
            <td class="num">{{ number_format($item->sales_qty, 0) }}</td>
            <td class="num">{{ $location }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td><strong>Quantity Total: &gt;&gt;</strong></td>
            <td class="num"><strong>{{ number_format($qtyTotal, 0) }}</strong></td>
            <td></td>
        </tr>
    </tbody>
</table>

<div class="remarks">Remarks If any:</div>
<div class="remarks-line">{{ $sale->remarks }}</div>

<div class="signature">
    <div class="signature-line"></div>
    <strong>Receiver Signature</strong>
</div>
</body>
</html>
