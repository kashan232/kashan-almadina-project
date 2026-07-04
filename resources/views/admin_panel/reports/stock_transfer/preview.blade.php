<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Goods Transfer Report</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }
        .date-range { font-size: 12px; font-weight: bold; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .wh-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            font-size: 13px;
            font-weight: bold;
            line-height: 1;
            margin-right: 4px;
            vertical-align: middle;
        }
        .wh-minus { color: #fff; background-color: #c62828; }
        .wh-plus { color: #fff; background-color: #2e7d32; }
        .data-row td { border: 1px solid #999; }
        .trn-cell { font-weight: bold; text-align: center; }
        .item-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .item-brand { color: #555; white-space: nowrap; }
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .total-label { text-align: right; color: #0d47a1; }
        .qty-box {
            background-color: #cfd8dc;
            text-align: center;
            font-weight: bold;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border-top: 2px solid #000;
        }
        .grand-label { text-align: right; color: #0d47a1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
</div>

<div style="text-align:center;margin-bottom:6px;"><x-amt-logo width="110px" style="margin:0 auto;" /></div>

<div class="company-name">Al-Madina Traders</div>

<div class="report-header">
    <div class="generated-date">{{ now()->format('l, F d, Y') }}</div>
    <h1 class="report-title">Goods Transfer Report</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="8%">Trn ID</th>
            <th width="9%">Date</th>
            <th width="16%"><span class="wh-icon wh-minus">−</span> From Warehouse</th>
            <th width="16%"><span class="wh-icon wh-plus">+</span> To Warehouse</th>
            <th width="39%">Item Description</th>
            <th width="12%">Quantity</th>
        </tr>
    </thead>
    <tbody>
        @php $grand_qty = 0; @endphp

        @if($grouped->isEmpty())
            <tr>
                <td colspan="6" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $transferId => $items)
            @php
                $transfer = $items->first()->transfer;
                $fromName = $transfer->from_shop ? 'Shop' : ($transfer->fromWarehouse->warehouse_name ?? 'N/A');
                $toName = $transfer->to_shop ? 'Shop' : ($transfer->toWarehouse->warehouse_name ?? 'N/A');
                $transferDate = \Carbon\Carbon::parse($transfer->entry_date ?? $transfer->created_at)->format('d-m-y');
                $trn_qty = 0;
            @endphp

            @foreach($items as $line)
                @php
                    $qty = (float) $line->quantity;
                    $trn_qty += $qty;
                    $grand_qty += $qty;
                    $brand = $line->product && $line->product->brandRelation
                        ? $line->product->brandRelation->name
                        : '-';
                @endphp
                <tr class="data-row">
                    <td class="trn-cell">{{ $transfer->id }}</td>
                    <td class="text-center">{{ $transferDate }}</td>
                    <td class="text-left">{{ $fromName }}</td>
                    <td class="text-left">{{ $toName }}</td>
                    <td class="text-left">
                        <div class="item-cell">
                            <span>{{ $line->product->name ?? 'N/A' }}</span>
                            <span class="item-brand">{{ $brand }}</span>
                        </div>
                    </td>
                    <td class="text-center">{{ number_format($qty, 0) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="5" class="total-label">Trn ID: {{ $transfer->id }} Total:</td>
                <td class="qty-box">{{ number_format($trn_qty, 0) }}</td>
            </tr>
        @endforeach

        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="5" class="grand-label">Grand Total:</td>
                <td class="qty-box">{{ number_format($grand_qty, 0) }}</td>
            </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    <div>{{ now()->format('l, F d, Y') }}</div>
    <div>Generated by ERP System</div>
</div>

</body>
</html>
