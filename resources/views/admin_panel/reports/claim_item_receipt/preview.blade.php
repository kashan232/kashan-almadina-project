<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Claim Receipt / Credit Report</title>
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
        .wh-minus { color: #fff; background-color: #c62828; }
        .wh-plus { color: #fff; background-color: #2e7d32; }
        .wh-cell { display: flex; align-items: center; }
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box, .val-box {
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
        .bold-val { font-weight: bold; }
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
        @include('admin_panel.reports.claim_item_receipt.partials.report_line_styles')
    </style>
</head>
<body>

@php
    $showAmounts = in_array($transaction_type, ['credit_note', 'all'], true);
    $showType = in_array($transaction_type, ['all'], true);
    $baseCols = 8 + ($showType ? 1 : 0);
    $totalCols = $baseCols + ($showAmounts ? 4 : 0);
@endphp

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
</div>

<div class="report-header">
    <div class="generated-date">{{ now()->format('l, F d, Y') }}</div>
    <h1 class="report-title">Claim Receipt / Credit Report</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="7%">Voucher</th>
            @if($showType)
                <th width="9%">Type</th>
            @endif
            <th width="11%"><span class="wh-icon wh-minus">−</span> Deduct From</th>
            <th width="11%"><span class="wh-icon wh-plus">+</span> Add To</th>
            <th width="8%">BTR #</th>
            <th width="8%">Date</th>
            <th width="16%">Supplier</th>
            <th width="16%">Item</th>
            <th width="7%">Qty</th>
            @if($showAmounts)
                <th width="8%">Retail Price</th>
                <th width="8%">Retail Value</th>
                <th width="8%">Purchase Rate</th>
                <th width="9%">Net Amount</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @php
            $grand_qty = 0;
            $grand_retail = 0;
            $grand_net = 0;
        @endphp

        @if($grouped->isEmpty())
            <tr>
                <td colspan="{{ $totalCols }}" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $groupKey => $items)
            @php
                $first = $items->first();
                $displayVoucher = preg_replace('/[^0-9]/', '', $first->voucher_no) ?: $first->voucher_no;
                $voucher_qty = 0;
                $voucher_retail = 0;
                $voucher_net = 0;
            @endphp

            @foreach($items as $item)
                @php
                    $qty = (float) $item->quantity;
                    $voucher_qty += $qty;
                    $grand_qty += $qty;
                    $itemDate = \Carbon\Carbon::parse($item->date)->format('d-m-y');
                    $isCredit = ($item->entry_type ?? '') === 'credit_note';
                    if ($isCredit) {
                        $voucher_retail += (float) ($item->retail_value ?? 0);
                        $voucher_net += (float) ($item->form_line_total ?? 0);
                        $grand_retail += (float) ($item->retail_value ?? 0);
                        $grand_net += (float) ($item->form_line_total ?? 0);
                    }
                @endphp
                <tr class="data-row {{ $isCredit ? 'credit-row' : '' }}">
                    <td class="voucher-cell">{{ $displayVoucher }}</td>
                    @if($showType)
                        @include('admin_panel.reports.claim_item_receipt.partials.type_cell', ['item' => $item])
                    @endif
                    <td class="text-left"><span class="wh-cell"><span class="wh-icon wh-minus">−</span>{{ $item->from_warehouse_name }}</span></td>
                    <td class="text-left"><span class="wh-cell"><span class="wh-icon wh-plus">+</span>{{ $item->to_warehouse_name ?: '-' }}</span></td>
                    <td class="text-center">{{ $item->btr_no ?: '-' }}</td>
                    <td class="text-center">{{ $itemDate }}</td>
                    <td class="text-left">{{ $item->party_name }}</td>
                    <td class="text-left">{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($qty, 2) }}</td>
                    @if($showAmounts)
                        <td class="text-right">{{ $isCredit ? number_format($item->retail_price ?? 0, 0) : '-' }}</td>
                        <td class="text-right">{{ $isCredit ? number_format($item->retail_value ?? 0, 0) : '-' }}</td>
                        <td class="text-right">{{ $isCredit ? number_format($item->form_rate ?? 0, 0) : '-' }}</td>
                        <td class="text-right bold-val">{{ $isCredit ? number_format($item->form_line_total ?? 0, 0) : '-' }}</td>
                    @endif
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="{{ $baseCols - 1 }}" class="text-right">Total:</td>
                <td class="qty-box">{{ number_format($voucher_qty, 2) }}</td>
                @if($showAmounts)
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($voucher_retail, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box">{{ number_format($voucher_net, 0) }}</td>
                @endif
            </tr>
        @endforeach

        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="{{ $baseCols - 1 }}" class="text-right">Grand Total:</td>
                <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty, 2) }}</td>
                @if($showAmounts)
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_retail, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_net, 0) }}</td>
                @endif
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
