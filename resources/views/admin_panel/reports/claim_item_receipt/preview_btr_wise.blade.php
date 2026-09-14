<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Claim BTR Wise Report</title>
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
            background-color: #fff59d;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            color: #000;
        }
        td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
            font-size: 11px;
        }
        .btr-header-row td {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .total-row td {
            font-weight: bold;
            background-color: #fffde7;
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .btn-print {
            background-color: #0d47a1;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Print Report</button>
    </div>

    <div class="report-header">
        <div class="generated-date">Date: {{ date('d-m-Y') }}</div>
        <h1 class="report-title">Al Madina Traders</h1>
        <div class="date-range">
            From: <span>{{ $from_date ? date('d-m-Y', strtotime($from_date)) : 'Start' }}</span> 
            To: <span>{{ $to_date ? date('d-m-Y', strtotime($to_date)) : 'End' }}</span>
        </div>
        <div style="font-weight: bold; margin-top: 5px; font-size: 13px; text-decoration: underline;">
            Claim BTR Wise Report
        </div>
    </div>

    @forelse($btrGroups as $btrNo => $items)
        @php
            $totClmAcp = 0;
            $totCir = 0;
            $totBal = 0;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 18%; text-align: left;">Brand</th>
                    <th style="width: 40%; text-align: left;">Item</th>
                    <th style="width: 14%; text-align: right;">Clm Acp</th>
                    <th style="width: 14%; text-align: right;">CIR</th>
                    <th style="width: 14%; text-align: right;">Balance</th>
                </tr>
                <tr class="btr-header-row">
                    <td colspan="5">BTR # {{ $btrNo }}</td>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @php
                        $bal = $item['clm_acp'] - $item['cir'];
                        $totClmAcp += $item['clm_acp'];
                        $totCir += $item['cir'];
                        $totBal += $bal;
                    @endphp
                    <tr>
                        <td>{{ $item['brand_name'] }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td style="text-align: right;">{{ number_format($item['clm_acp'], 0) }}</td>
                        <td style="text-align: right;">{{ number_format($item['cir'], 0) }}</td>
                        <td style="text-align: right;">{{ number_format($bal, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">Total Bal.</td>
                    <td style="text-align: right;">{{ number_format($totClmAcp, 0) }}</td>
                    <td style="text-align: right;">{{ number_format($totCir, 0) }}</td>
                    <td style="text-align: right;">{{ number_format($totBal, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <table style="text-align: center;">
            <tr>
                <td colspan="5" style="padding: 20px; color: #777;">No records found for the selected criteria.</td>
            </tr>
        </table>
    @endforelse

</body>
</html>
