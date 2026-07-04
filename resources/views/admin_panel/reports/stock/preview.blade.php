<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — Without Values</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 6mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 12px;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 10px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 2px 0;
        }
        .report-type {
            color: #333;
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .date-range { font-size: 11px; font-weight: bold; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 10px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background: #cfd8dc;
            border: 1px solid #000;
            padding: 4px 2px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        th.col-out { background: #e65100 !important; color: #fff !important; }
        th.col-hold { background: #e65100 !important; color: #fff !important; }
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
            font-size: 9px;
        }
        .item-name { text-align: left; font-weight: 600; }
        .num { text-align: center; }
        .col-out-cell {
            background: #fff3e0 !important;
            color: #bf360c !important;
            font-weight: 600;
            text-align: center;
        }
        .col-hold-cell {
            background: #fff3e0 !important;
            color: #bf360c !important;
            font-weight: 600;
            text-align: center;
        }
        .opening-col, .closing-col { font-weight: bold; text-align: center; }
        .wh-title td {
            background: #eceff1 !important;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border-top: 2px solid #000;
        }
        .subtotal-row td {
            background: #f5f5f5 !important;
            font-weight: bold;
            border-top: 1px solid #000;
        }
        .grand-total-row td {
            background: #cfd8dc !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }
        @media print {
            body { padding: 4mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ now()->format('d-m-y g:i A') }}</div>
        <h1 class="report-title">Stock Report</h1>
        <div class="report-type">Without Values (Qty Movement)</div>
        <div class="date-range">
            From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
            &nbsp;&nbsp;To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
        </div>
    </div>

    @php
        $fmt = fn($v) => abs($v) < 0.0001 ? '' : number_format($v, 0);
        $outCols = ['pur_ret', 'sales', 'claim_out', 'trf_out', 'waste', 'release'];
        $cellClass = function ($key, $value) use ($outCols) {
            if ($key === 'hold' && abs((float)$value) >= 0.0001) {
                return 'col-hold-cell';
            }
            if (in_array($key, $outCols, true) && abs((float)$value) >= 0.0001) {
                return 'col-out-cell';
            }
            if (in_array($key, ['opening', 'closing'], true) && (float)$value < 0) {
                return 'col-out-cell';
            }
            return 'num';
        };
        $cols = [
            ['key' => 'opening', 'head' => $opening_label, 'th' => ''],
            ['key' => 'closing', 'head' => $closing_label, 'th' => ''],
            ['key' => 'pur', 'head' => 'PJ', 'th' => ''],
            ['key' => 'pur_ret', 'head' => 'PRJ', 'th' => 'col-out'],
            ['key' => 'sales', 'head' => 'SJ', 'th' => 'col-out'],
            ['key' => 'sales_ret', 'head' => 'SRJ', 'th' => ''],
            ['key' => 'claim_in', 'head' => 'CLIN', 'th' => ''],
            ['key' => 'claim_out', 'head' => 'CLO', 'th' => 'col-out'],
            ['key' => 'trf_in', 'head' => 'TIN', 'th' => ''],
            ['key' => 'trf_out', 'head' => 'TOUT', 'th' => 'col-out'],
            ['key' => 'waste', 'head' => 'WST', 'th' => 'col-out'],
            ['key' => 'hold', 'head' => 'Hold Qty', 'th' => 'col-hold'],
            ['key' => 'release', 'head' => 'REL', 'th' => 'col-out'],
        ];
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:14%;">Item</th>
                @foreach($cols as $col)
                <th class="{{ $col['th'] }}">{{ $col['head'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $warehouseId => $rows)
                @php $whTotal = array_fill_keys(array_column($cols, 'key'), 0.0); @endphp
                <tr class="wh-title">
                    <td colspan="{{ count($cols) + 1 }}">{{ $rows->first()['warehouse_label'] ?? ('Location #' . $warehouseId) }}</td>
                </tr>
                @foreach($rows as $row)
                    @php
                        foreach ($whTotal as $k => $_) {
                            $whTotal[$k] += $row[$k] ?? 0;
                        }
                    @endphp
                    <tr>
                        <td class="item-name">{{ $row['product_name'] }}</td>
                        @foreach($cols as $col)
                        <td class="{{ $cellClass($col['key'], $row[$col['key']] ?? 0) }} {{ in_array($col['key'], ['opening','closing']) ? 'opening-col' : '' }}">
                            {{ $fmt($row[$col['key']] ?? 0) }}
                        </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td style="text-align:right;">Total:</td>
                    @foreach($cols as $col)
                    <td class="{{ $cellClass($col['key'], $whTotal[$col['key']]) }}">{{ $fmt($whTotal[$col['key']]) }}</td>
                    @endforeach
                </tr>
                <tr style="height:8px;"><td colspan="{{ count($cols) + 1 }}" style="border:none;"></td></tr>
            @empty
                <tr>
                    <td colspan="{{ count($cols) + 1 }}" style="text-align:center;padding:20px;">No stock movement found for selected filters.</td>
                </tr>
            @endforelse

            @if($grouped->isNotEmpty())
            <tr class="grand-total-row">
                <td style="text-align:right;">Grand Total:</td>
                @foreach($cols as $col)
                <td class="{{ $cellClass($col['key'], $grand[$col['key']] ?? 0) }}">{{ $fmt($grand[$col['key']] ?? 0) }}</td>
                @endforeach
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
