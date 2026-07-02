<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Acceptance Report</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 15px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            left: 0;
            top: 5px;
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
            padding: 6px 2px;
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
        .voucher-cell { font-weight: bold; text-align: center; }
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
            flex-shrink: 0;
        }
        .wh-minus {
            color: #fff;
            background-color: #c62828;
        }
        .wh-plus {
            color: #fff;
            background-color: #2e7d32;
        }
        .wh-cell {
            display: flex;
            align-items: center;
        }
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #bbdefb;
            text-align: right;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 10px 6px;
            border-top: 2px solid #000;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 40px;
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

<div class="report-header">
    <div class="generated-date">{{ now()->format('l, F d, Y') }}</div>
    <h1 class="report-title">Claim Acceptance Report</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="8%">Voucher</th>
            <th width="12%"><span class="wh-icon wh-minus">−</span> Claim From</th>
            <th width="12%"><span class="wh-icon wh-plus">+</span> Accept In</th>
            <th width="9%">BTR #</th>
            <th width="9%">Date</th>
            <th width="20%">Supplier</th>
            <th width="22%">Item</th>
            <th width="8%">Qty</th>
        </tr>
    </thead>
    <tbody>
        @php $grand_qty = 0; @endphp

        @if($grouped->isEmpty())
            <tr>
                <td colspan="8" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $acceptanceId => $items)
            @php
                $voucher = $items->first()->voucher;
                $displayVoucher = preg_replace('/[^0-9]/', '', $voucher->voucher_no) ?: $voucher->voucher_no;
                $claimFrom = $voucher->fromWarehouse->warehouse_name ?? 'N/A';
                $acceptIn = $voucher->toWarehouse->warehouse_name ?? 'N/A';
                $partyName = $voucher->partyName();
                $voucher_qty = 0;
            @endphp

            @foreach($items as $item)
                @php
                    $qty = (float) $item->quantity;
                    $voucher_qty += $qty;
                    $grand_qty += $qty;
                    $itemDate = \Carbon\Carbon::parse($voucher->date)->format('d-m-y');
                @endphp
                <tr class="data-row">
                    <td class="voucher-cell">{{ $displayVoucher }}</td>
                    <td class="text-left"><span class="wh-cell"><span class="wh-icon wh-minus">−</span>{{ $claimFrom }}</span></td>
                    <td class="text-left"><span class="wh-cell"><span class="wh-icon wh-plus">+</span>{{ $acceptIn }}</span></td>
                    <td class="text-center">{{ $item->btr_no ?: '-' }}</td>
                    <td class="text-center">{{ $itemDate }}</td>
                    <td class="text-left">{{ $partyName }}</td>
                    <td class="text-left">{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($qty, 2) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="7" class="text-right">Total:</td>
                <td class="qty-box">{{ number_format($voucher_qty, 2) }}</td>
            </tr>
        @endforeach

        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="7" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty, 2) }}</td>
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
