<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Goods Wastage Report</title>
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
        .date-range {
            font-size: 12px;
            font-weight: bold;
        }
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
        .data-row td { border: 1px solid #999; }
        .wst-cell { font-weight: bold; text-align: center; }
        .item-id {
            display: inline-block;
            min-width: 28px;
            color: #666;
            margin-right: 8px;
            text-align: center;
        }
        .amount-cell { font-weight: bold; text-align: right; }
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border-top: 2px solid #000;
        }
        .grand-label { color: #0d47a1; text-align: right; }
        .qty-box {
            background-color: #cfd8dc;
            text-align: center;
            font-weight: bold;
        }
        .amount-box {
            background-color: #cfd8dc;
            text-align: right;
            font-weight: bold;
        }
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
    <h1 class="report-title">Goods Wastage Report</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="7%">WST ID</th>
            <th width="8%">Date</th>
            <th width="12%">Warehouse</th>
            <th width="24%">Item Description</th>
            <th width="20%">Exp a/c</th>
            <th width="7%">Qty</th>
            <th width="10%">Price</th>
            <th width="12%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grand_qty = 0;
            $grand_amount = 0;
        @endphp

        @if($lines->isEmpty())
            <tr>
                <td colspan="8" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($lines as $line)
            @php
                $wastage = $line->wastage;
                $displayWst = preg_replace('/[^0-9]/', '', $wastage->gwn_id) ?: $wastage->gwn_id;
                $warehouseName = ($wastage->warehouse_id === 0 || $wastage->warehouse_id === null)
                    ? 'Shop Stock'
                    : ($wastage->warehouse->warehouse_name ?? 'N/A');
                $qty = (float) $line->qty;
                $price = (float) $line->price;
                $amount = (float) $line->amount;
                $grand_qty += $qty;
                $grand_amount += $amount;
            @endphp
            <tr class="data-row">
                <td class="wst-cell">{{ $displayWst }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($wastage->date)->format('d-m-y') }}</td>
                <td class="text-left">{{ $warehouseName }}</td>
                <td class="text-left">
                    <span class="item-id">{{ $line->product_id }}</span>
                    {{ $line->product->name ?? 'N/A' }}
                </td>
                <td class="text-left">{{ $wastage->account->title ?? 'N/A' }}</td>
                <td class="text-center">{{ number_format($qty, 0) }}</td>
                <td class="text-right">{{ number_format($price, 0) }}</td>
                <td class="amount-cell">{{ number_format($amount, 0) }}</td>
            </tr>
        @endforeach

        @if(!$lines->isEmpty())
            <tr class="grand-total-row">
                <td colspan="5" class="grand-label">Grand Total:</td>
                <td class="qty-box">{{ number_format($grand_qty, 0) }}</td>
                <td style="border:none; background:none;"></td>
                <td class="amount-box">{{ number_format($grand_amount, 0) }}</td>
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
