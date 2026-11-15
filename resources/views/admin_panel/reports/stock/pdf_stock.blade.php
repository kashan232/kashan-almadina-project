<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report — {{ !empty($is_merged) ? 'Merged' : 'Without Values' }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 5mm 8mm 5mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 6.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-tbl td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .company-title {
            font-size: 10pt;
            font-weight: bold;
            color: #8e24aa;
            text-align: left;
        }
        .report-subtitle {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0d47a1;
        }
        .date-range {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000000;
            text-align: right;
            white-space: nowrap;
        }

        table.stock-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
        }
        table.stock-table thead {
            display: table-header-group;
        }
        table.stock-table tr {
            page-break-inside: avoid;
        }
        table.stock-table th {
            background: #ffffff;
            border: 1px solid #777777;
            padding: 2px 1px;
            font-size: 6.5pt;
            font-weight: bold;
            color: #000000;
            text-align: center;
            line-height: 1.15;
        }
        table.stock-table td {
            border: 1px solid #777777;
            padding: 1.5px 2px;
            font-size: 6.5pt;
            line-height: 1.15;
            vertical-align: middle;
            color: #000000;
        }
        .item-col {
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .num { text-align: right; }
        .col-out {
            background-color: #ffebee;
            color: #c62828;
            font-weight: 600;
        }
        .col-hold {
            background-color: #fff3e0;
            color: #e65100;
            font-weight: 600;
        }
        .wh-title td {
            background: #dbeafe;
            font-weight: bold;
            font-size: 7pt;
            color: #1e40af;
            border-top: 1.5px solid #000;
            border-bottom: 1px solid #000;
            padding: 2.5px 4px;
        }
        .brand-heading-row td {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 6.8pt;
            color: #0f172a;
            padding: 2px 4px;
        }
        .brand-total-row td {
            font-weight: bold;
            background: #f8fafc;
            border-top: 1px solid #777;
            border-bottom: 1px solid #777;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #e2e8f0;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            background: #cbd5e1;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 7pt;
        }
        .report-footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .report-footer td {
            border: none;
            padding: 1px 0;
            font-size: 6.5pt;
            color: #444444;
        }
    </style>
</head>
<body>
    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
        $outCols = ['pur_ret', 'sales', 'clm_rep', 'clm_out', 'cla_out', 'cli_out', 'trf_out', 'waste', 'release'];
        $cellClass = function ($key, $value) use ($outCols) {
            if ($key === 'hold' && abs((float)$value) >= 0.0001) return 'num col-hold';
            if (in_array($key, $outCols, true) && abs((float)$value) >= 0.0001) return 'num col-out';
            if (in_array($key, ['opening', 'closing'], true) && (float)$value < 0) return 'num col-out';
            return 'num';
        };

        $reqClaimTypes = request('claim_type', []);
        if (!is_array($reqClaimTypes)) $reqClaimTypes = array_filter([$reqClaimTypes]);

        $hasNormalType = in_array('none', $reqClaimTypes, true);
        $hasClaimType = in_array('customer', $reqClaimTypes, true) || in_array('company', $reqClaimTypes, true);

        $whMap = \App\Models\Warehouse::withoutGlobalScopes()->pluck('claim_type', 'id')->all();
        $whMap[0] = 'none';

        $selectedWhs = request('warehouse', []);
        if (!is_array($selectedWhs)) $selectedWhs = array_filter([$selectedWhs]);

        $hasSelectedWhNormal = false;
        $hasSelectedWhClaim = false;
        if (!empty($selectedWhs)) {
            foreach ($selectedWhs as $id) {
                if (($whMap[$id] ?? 'none') === 'none') {
                    $hasSelectedWhNormal = true;
                } else {
                    $hasSelectedWhClaim = true;
                }
            }
        }

        if (($hasNormalType && $hasClaimType) || ($hasSelectedWhNormal && $hasSelectedWhClaim)) {
            $reportMode = 'all';
        } elseif ($hasClaimType || ($hasSelectedWhClaim && !$hasSelectedWhNormal)) {
            $reportMode = 'claim';
        } else {
            $reportMode = 'normal';
        }

        if ($reportMode === 'normal') {
            $cols = [
                ['key' => 'opening',   'head' => 'Start Date'],
                ['key' => 'closing',   'head' => 'End Date'],
                ['key' => 'pur',       'head' => 'PJ'],
                ['key' => 'pur_ret',   'head' => 'PRJ'],
                ['key' => 'sales',     'head' => 'SJ'],
                ['key' => 'sales_ret', 'head' => 'SRJ'],
                ['key' => 'clm_rep',   'head' => 'Clm-Rep'],
                ['key' => 'clm_out',   'head' => 'Clm-Out'],
                ['key' => 'cli_in',    'head' => 'Clm-In'],
                ['key' => 'trf_in',    'head' => 'TOG In'],
                ['key' => 'trf_out',   'head' => 'TOG Out'],
                ['key' => 'waste',     'head' => 'WOG'],
                ['key' => 'hold',      'head' => 'SH'],
                ['key' => 'release',   'head' => 'SR'],
            ];
            $itemWidth = '22%';
        } elseif ($reportMode === 'claim') {
            $cols = [
                ['key' => 'opening', 'head' => 'Start Date'],
                ['key' => 'closing', 'head' => 'End Date'],
                ['key' => 'clm_in',  'head' => 'Clm In'],
                ['key' => 'clm_out', 'head' => 'Clm-Out'],
                ['key' => 'cla_out', 'head' => 'Clm-Out'],
                ['key' => 'cla_in',  'head' => 'Clm-In'],
                ['key' => 'cli_out', 'head' => 'Clm-Out'],
                ['key' => 'cli_in',  'head' => 'Clm-In'],
                ['key' => 'trf_in',  'head' => 'TOG In'],
                ['key' => 'trf_out', 'head' => 'TOG Out'],
                ['key' => 'waste',   'head' => 'WOG'],
            ];
            $itemWidth = '26%';
        } else {
            $cols = [
                ['key' => 'opening',   'head' => 'Start Date'],
                ['key' => 'closing',   'head' => 'End Date'],
                ['key' => 'pur',       'head' => 'PJ'],
                ['key' => 'pur_ret',   'head' => 'PRJ'],
                ['key' => 'sales',     'head' => 'SJ'],
                ['key' => 'sales_ret', 'head' => 'SRJ'],
                ['key' => 'clm_rep',   'head' => 'Clm-Rep'],
                ['key' => 'clm_in',    'head' => 'Clm In'],
                ['key' => 'clm_out',   'head' => 'Clm-Out'],
                ['key' => 'cla_out',   'head' => 'Clm-Out'],
                ['key' => 'cla_in',    'head' => 'Clm-In'],
                ['key' => 'cli_out',   'head' => 'Clm-Out'],
                ['key' => 'cli_in',    'head' => 'Clm-In'],
                ['key' => 'trf_in',    'head' => 'TOG In'],
                ['key' => 'trf_out',   'head' => 'TOG Out'],
                ['key' => 'waste',     'head' => 'WOG'],
                ['key' => 'hold',      'head' => 'SH'],
                ['key' => 'release',   'head' => 'SR'],
            ];
            $itemWidth = '18%';
        }
    @endphp

    <table class="header-tbl">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">AL-MADINA TRADERS</div>
                <div class="report-subtitle">Stock Report — {{ !empty($is_merged) ? 'Merged (Shop + Warehouses)' : 'Without Values (Qty Movement)' }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="date-range">From: {{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }} &nbsp; To: {{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</div>
                <div style="font-size: 6.5pt; color: #555;">Generated: {{ date('d-m-y g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="stock-table">
        <thead>
            @if($reportMode === 'normal')
            <tr>
                <th rowspan="2" style="width:{{ $itemWidth }};">Item</th>
                <th colspan="2">Date Range</th>
                <th colspan="4">Purchase &amp; Sales (Returns)</th>
                <th colspan="2">Customer Claim</th>
                <th colspan="1">CIR</th>
                <th colspan="5">Generals</th>
            </tr>
            <tr>
                <th>Start Date<br>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : $opening_label }}</th>
                <th>End Date<br>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : $closing_label }}</th>
                <th>PJ</th>
                <th class="col-out">PRJ</th>
                <th class="col-out">SJ</th>
                <th>SRJ</th>
                <th class="col-out">Clm-Rep</th>
                <th class="col-out">Clm-Out</th>
                <th>Clm-In</th>
                <th>TOG In</th>
                <th class="col-out">TOG Out</th>
                <th class="col-out">WOG</th>
                <th class="col-hold">SH</th>
                <th class="col-out">SR</th>
            </tr>
            @elseif($reportMode === 'claim')
            <tr>
                <th rowspan="2" style="width:{{ $itemWidth }};">Item</th>
                <th colspan="2">Date Range</th>
                <th colspan="2">Customer Claim</th>
                <th colspan="2">Claim Acceptance</th>
                <th colspan="2">CIR</th>
                <th colspan="3">Generals</th>
            </tr>
            <tr>
                <th>Start Date<br>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : $opening_label }}</th>
                <th>End Date<br>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : $closing_label }}</th>
                <th>Clm In</th>
                <th class="col-out">Clm-Out</th>
                <th class="col-out">Clm-Out</th>
                <th>Clm-In</th>
                <th class="col-out">Clm-Out</th>
                <th>Clm-In</th>
                <th>TOG In</th>
                <th class="col-out">TOG Out</th>
                <th class="col-out">WOG</th>
            </tr>
            @else
            <tr>
                <th rowspan="2" style="width:{{ $itemWidth }};">Item</th>
                <th colspan="2">Date Range</th>
                <th colspan="4">Purchase &amp; Sales (Returns)</th>
                <th colspan="3">Customer Claim</th>
                <th colspan="2">Claim Acceptance</th>
                <th colspan="2">CIR</th>
                <th colspan="5">Generals</th>
            </tr>
            <tr>
                <th>Start Date<br>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : $opening_label }}</th>
                <th>End Date<br>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : $closing_label }}</th>
                <th>PJ</th>
                <th class="col-out">PRJ</th>
                <th class="col-out">SJ</th>
                <th>SRJ</th>
                <th class="col-out">Clm-Rep</th>
                <th>Clm In</th>
                <th class="col-out">Clm-Out</th>
                <th class="col-out">Clm-Out</th>
                <th>Clm-In</th>
                <th class="col-out">Clm-Out</th>
                <th>Clm-In</th>
                <th>TOG In</th>
                <th class="col-out">TOG Out</th>
                <th class="col-out">WOG</th>
                <th class="col-hold">SH</th>
                <th class="col-out">SR</th>
            </tr>
            @endif
        </thead>
        <tbody>
            @forelse($grouped as $warehouseId => $rows)
                @php
                    $whTotal = array_fill_keys(array_column($cols, 'key'), 0.0);
                    $whRows = collect($rows);
                    $brandGroups = $whRows->groupBy(fn($item) => (int)($item['brand_id'] ?? 0))->sortKeys();
                @endphp
                <tr class="wh-title">
                    <td colspan="{{ count($cols) + 1 }}">{{ $rows->first()['warehouse_label'] ?? ('WH #' . $warehouseId) }}</td>
                </tr>
                @foreach($brandGroups as $bId => $bRows)
                    @php
                        $sortedBRows = $bRows->sort(function($a, $b) {
                            $subCmp = ((int)($a['sub_category_id'] ?? 0)) <=> ((int)($b['sub_category_id'] ?? 0));
                            if ($subCmp !== 0) return $subCmp;
                            return ((int)($a['product_id'] ?? 0)) <=> ((int)($b['product_id'] ?? 0));
                        });
                        $brandName = $sortedBRows->first()['brand_name'] ?? 'Other';
                        $brandTotal = array_fill_keys(array_column($cols, 'key'), 0.0);
                    @endphp
                    <tr class="brand-heading-row">
                        <td colspan="{{ count($cols) + 1 }}">{{ strtoupper($brandName) }}</td>
                    </tr>
                    @foreach($sortedBRows as $row)
                        @php
                            foreach ($cols as $col) {
                                $val = (float)($row[$col['key']] ?? 0);
                                $brandTotal[$col['key']] += $val;
                                $whTotal[$col['key']] += $val;
                            }
                        @endphp
                        <tr>
                            <td class="item-col">{{ $row['product_name'] }}</td>
                            @foreach($cols as $col)
                            <td class="{{ $cellClass($col['key'], $row[$col['key']] ?? 0) }}">
                                {{ $fmt($row[$col['key']] ?? 0) }}
                            </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="brand-total-row">
                        <td style="text-align:right;">{{ strtoupper($brandName) }} Total:</td>
                        @foreach($cols as $col)
                        <td class="{{ $cellClass($col['key'], $brandTotal[$col['key']]) }}">
                            {{ $fmt($brandTotal[$col['key']]) }}
                        </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td style="text-align:right;">{{ $rows->first()['warehouse_label'] ?? ('WH #' . $warehouseId) }} Total:</td>
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

    <table class="report-footer">
        <tr>
            <td style="text-align: left;">Report Generated: {{ date('g:i:s A | l, F d, Y') }}</td>
            <td style="text-align: right;">System Generated Report and Required no Signature</td>
        </tr>
    </table>
</body>
</html>
