<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background: #fff;
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
            margin-bottom: 16px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 5px 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 3px 4px;
            vertical-align: middle;
        }
        .item-name { text-align: left; font-weight: 600; }
        .num { text-align: center; }
        .opening-col, .closing-col { font-weight: bold; text-align: center; }
        .wh-title td {
            background: #eceff1;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border-top: 2px solid #000;
        }
        .subtotal-row td {
            background: #f5f5f5;
            font-weight: bold;
            border-top: 1px solid #000;
        }
        .grand-total-row td {
            background: #cfd8dc;
            font-weight: bold;
            border-top: 2px solid #000;
        }
        .empty { color: transparent; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ now()->format('d-M-Y') }}</div>
        <h1 class="report-title">Stock Report (Qty Only)</h1>
        <div class="date-range">
            From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
            &nbsp;&nbsp;To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
        </div>
    </div>

    @php
        $fmt = fn($v) => abs($v) < 0.0001 ? '' : number_format($v, 0);
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:14%;">Item</th>
                <th style="width:6%;">{{ $opening_label }}</th>
                <th style="width:6%;"><b>{{ $closing_label }}</b></th>
                <th style="width:5%;">Pur.</th>
                <th style="width:5%;">Pur. Ret</th>
                <th style="width:5%;">Sales</th>
                <th style="width:5%;">Sales Return</th>
                <th style="width:5%;">Claim IN</th>
                <th style="width:5%;">Claim Out</th>
                <th style="width:5%;">Trf In</th>
                <th style="width:5%;">Trf Out</th>
                <th style="width:5%;">Waste</th>
                <th style="width:5%;">Hold</th>
                <th style="width:5%;">Release</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $warehouseId => $rows)
                @php $whTotal = [
                    'opening'=>0,'closing'=>0,'pur'=>0,'pur_ret'=>0,'sales'=>0,'sales_ret'=>0,
                    'claim_in'=>0,'claim_out'=>0,'trf_in'=>0,'trf_out'=>0,'waste'=>0,'hold'=>0,'release'=>0
                ]; @endphp
                <tr class="wh-title">
                    <td colspan="14">{{ $rows->first()['warehouse_label'] ?? ('Location #' . $warehouseId) }}</td>
                </tr>
                @foreach($rows as $row)
                    @php
                        foreach ($whTotal as $k => $_) {
                            $whTotal[$k] += $row[$k] ?? 0;
                        }
                    @endphp
                    <tr>
                        <td class="item-name">{{ $row['product_name'] }}</td>
                        <td class="opening-col num">{{ $fmt($row['opening']) }}</td>
                        <td class="closing-col num">{{ $fmt($row['closing']) }}</td>
                        <td class="num">{{ $fmt($row['pur']) }}</td>
                        <td class="num">{{ $fmt($row['pur_ret']) }}</td>
                        <td class="num">{{ $fmt($row['sales']) }}</td>
                        <td class="num">{{ $fmt($row['sales_ret']) }}</td>
                        <td class="num">{{ $fmt($row['claim_in']) }}</td>
                        <td class="num">{{ $fmt($row['claim_out']) }}</td>
                        <td class="num">{{ $fmt($row['trf_in']) }}</td>
                        <td class="num">{{ $fmt($row['trf_out']) }}</td>
                        <td class="num">{{ $fmt($row['waste']) }}</td>
                        <td class="num">{{ $fmt($row['hold']) }}</td>
                        <td class="num">{{ $fmt($row['release']) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td class="text-right" style="text-align:right;">{{ $warehouseId }} Total:</td>
                    <td class="num">{{ $fmt($whTotal['opening']) }}</td>
                    <td class="num">{{ $fmt($whTotal['closing']) }}</td>
                    <td class="num">{{ $fmt($whTotal['pur']) }}</td>
                    <td class="num">{{ $fmt($whTotal['pur_ret']) }}</td>
                    <td class="num">{{ $fmt($whTotal['sales']) }}</td>
                    <td class="num">{{ $fmt($whTotal['sales_ret']) }}</td>
                    <td class="num">{{ $fmt($whTotal['claim_in']) }}</td>
                    <td class="num">{{ $fmt($whTotal['claim_out']) }}</td>
                    <td class="num">{{ $fmt($whTotal['trf_in']) }}</td>
                    <td class="num">{{ $fmt($whTotal['trf_out']) }}</td>
                    <td class="num">{{ $fmt($whTotal['waste']) }}</td>
                    <td class="num">{{ $fmt($whTotal['hold']) }}</td>
                    <td class="num">{{ $fmt($whTotal['release']) }}</td>
                </tr>
                <tr style="height:12px;"><td colspan="14" style="border:none;"></td></tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align:center;padding:20px;">No stock movement found for selected filters.</td>
                </tr>
            @endforelse

            @if($grouped->isNotEmpty())
            <tr class="grand-total-row">
                <td style="text-align:right;">Grand Total:</td>
                <td class="num">{{ $fmt($grand['opening']) }}</td>
                <td class="num">{{ $fmt($grand['closing']) }}</td>
                <td class="num">{{ $fmt($grand['pur']) }}</td>
                <td class="num">{{ $fmt($grand['pur_ret']) }}</td>
                <td class="num">{{ $fmt($grand['sales']) }}</td>
                <td class="num">{{ $fmt($grand['sales_ret']) }}</td>
                <td class="num">{{ $fmt($grand['claim_in']) }}</td>
                <td class="num">{{ $fmt($grand['claim_out']) }}</td>
                <td class="num">{{ $fmt($grand['trf_in']) }}</td>
                <td class="num">{{ $fmt($grand['trf_out']) }}</td>
                <td class="num">{{ $fmt($grand['waste']) }}</td>
                <td class="num">{{ $fmt($grand['hold']) }}</td>
                <td class="num">{{ $fmt($grand['release']) }}</td>
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
