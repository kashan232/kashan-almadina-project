<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Report — Without Values</title>
    <style>
        @page { size: A4 landscape; margin: 2mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            color: #000;
            margin: 0;
            padding: 1mm;
            background: #fff;
        }
        .no-print {
            padding: 8px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 8px;
        }
        .print-sheet {
            width: 297mm;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
        }
        .sheet-blank { width: 50%; flex: 0 0 50%; }
        .report-sheet {
            width: 50%;
            flex: 0 0 50%;
            padding: 0 0.5mm;
            overflow: hidden;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1px;
            line-height: 1.1;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 2px;
            line-height: 1.15;
        }
        .report-title {
            color: #0d47a1;
            font-size: 9px;
            font-weight: bold;
            margin: 0;
        }
        .report-type {
            font-size: 7px;
            font-weight: bold;
            text-decoration: underline;
        }
        .date-range { font-size: 7px; font-weight: bold; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 6px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }
        th {
            background: #cfd8dc;
            border: 1px solid #000;
            padding: 1px;
            font-size: 6px;
            font-weight: bold;
            text-align: center;
            line-height: 1.1;
            word-wrap: break-word;
        }
        th.col-out { background: #e65100 !important; color: #fff !important; }
        th.col-hold { background: #e65100 !important; color: #fff !important; }
        td {
            border: 1px solid #666;
            padding: 1px 2px;
            vertical-align: middle;
            font-size: 6.5px;
            line-height: 1.15;
            overflow: hidden;
        }
        .item-name {
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
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
        .opening-col, .closing-col { font-weight: bold; text-align: center; font-size: 6px; }
        .wh-title td {
            background: #eceff1 !important;
            font-weight: bold;
            text-align: left;
            padding: 1px 3px;
            font-size: 6.5px;
            border-top: 1px solid #000;
        }
        .subtotal-row td {
            background: #e3f2fd !important;
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 6.5px;
        }
        .grand-total-row td {
            background: #cfd8dc !important;
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 6.5px;
        }
        @media print {
            body { padding: 1mm; }
            .no-print { display: none !important; }
            .print-sheet { width: 100%; margin: 0; }
            .sheet-blank { width: 50%; }
            .report-sheet { width: 50%; padding: 0 0.5mm; }
        }
        @media screen and (max-width: 900px) {
            .print-sheet { flex-direction: column; width: 100%; }
            .sheet-blank { display: none; }
            .report-sheet { width: 100%; flex: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
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
            ['key' => 'opening', 'head' => $opening_label, 'th' => '', 'w' => '7%'],
            ['key' => 'closing', 'head' => $closing_label, 'th' => '', 'w' => '7%'],
            ['key' => 'pur', 'head' => 'PJ', 'th' => '', 'w' => '5%'],
            ['key' => 'pur_ret', 'head' => 'PRJ', 'th' => 'col-out', 'w' => '5%'],
            ['key' => 'sales', 'head' => 'SJ', 'th' => 'col-out', 'w' => '5%'],
            ['key' => 'sales_ret', 'head' => 'SRJ', 'th' => '', 'w' => '5%'],
            ['key' => 'claim_in', 'head' => 'CLM IN', 'th' => '', 'w' => '5%'],
            ['key' => 'claim_out', 'head' => 'CLM Out', 'th' => 'col-out', 'w' => '5%'],
            ['key' => 'trf_in', 'head' => 'TOG In', 'th' => '', 'w' => '5%'],
            ['key' => 'trf_out', 'head' => 'TOG Out', 'th' => 'col-out', 'w' => '5%'],
            ['key' => 'waste', 'head' => 'WOG', 'th' => 'col-out', 'w' => '5%'],
            ['key' => 'hold', 'head' => 'SH', 'th' => 'col-hold', 'w' => '5%'],
            ['key' => 'release', 'head' => 'SR', 'th' => 'col-out', 'w' => '5%'],
        ];
    @endphp

    <div class="print-sheet">
        <div class="sheet-blank"></div>
        <div class="report-sheet">
            <div class="company-name">AL-MADINA TRADERS</div>
            <div class="report-header">
                <div class="generated-date">{{ now()->format('l, M j, Y') }}</div>
                <div class="report-title">Stock Report</div>
                <div class="report-type">Without Values</div>
                <div class="date-range">
                    From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
                    To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:18%;">Item</th>
                        @foreach($cols as $col)
                        <th class="{{ $col['th'] }}" style="width:{{ $col['w'] }};">{{ $col['head'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($grouped as $warehouseId => $rows)
                        @php $whTotal = array_fill_keys(array_column($cols, 'key'), 0.0); @endphp
                        <tr class="wh-title">
                            <td colspan="{{ count($cols) + 1 }}">{{ $rows->first()['warehouse_label'] ?? ('WH #' . $warehouseId) }}</td>
                        </tr>
                        @foreach($rows as $row)
                            @php foreach ($whTotal as $k => $_) { $whTotal[$k] += $row[$k] ?? 0; } @endphp
                            <tr>
                                <td class="item-name" title="{{ $row['product_name'] }}">{{ $row['product_name'] }}</td>
                                @foreach($cols as $col)
                                <td class="{{ $cellClass($col['key'], $row[$col['key']] ?? 0) }} {{ in_array($col['key'], ['opening','closing']) ? 'opening-col' : '' }}">
                                    {{ $fmt($row[$col['key']] ?? 0) }}
                                </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td style="text-align:right;">{{ $warehouseId }} Total:</td>
                            @foreach($cols as $col)
                            <td class="{{ $cellClass($col['key'], $whTotal[$col['key']]) }}">{{ $fmt($whTotal[$col['key']]) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($cols) + 1 }}" style="text-align:center;padding:8px;">No data.</td></tr>
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
        </div>
    </div>
</body>
</html>
