<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Sales Report Month Wise with Claim Ratio</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
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
            margin-bottom: 15px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        .report-subtitle {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background-color: #ffff00; /* Yellow header from image */
            border: 1px solid #000;
            padding: 8px 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .total-row {
            font-weight: bold;
            background-color: #fff;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <div class="report-title">Sales Report Month Wise with Claim Ratio</div>
        <div class="report-subtitle">Al-Madina Battery</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30%">Month</th>
                <th width="10%">Qty</th>
                <th width="15%">Retail Amount</th>
                <th width="15%">Sales Amount</th>
                <th width="15%">Claim Qty</th>
                <th width="15%">Claim Percentage</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $t_qty = 0; 
                $t_retail = 0; 
                $t_sales = 0; 
                $t_claims = 0; 
            @endphp

            @foreach($data as $month => $vals)
                @php
                    $t_qty += $vals['qty'];
                    $t_retail += $vals['retail_amount'];
                    $t_sales += $vals['sales_amount'];
                    $t_claims += $vals['claim_qty'];
                @endphp
                <tr>
                    <td class="text-left">{{ $month }}</td>
                    <td class="text-right">{{ number_format($vals['qty']) }}</td>
                    <td class="text-right">{{ number_format($vals['retail_amount'], 0) }}</td>
                    <td class="text-right">{{ number_format($vals['sales_amount'], 0) }}</td>
                    <td class="text-right">{{ number_format($vals['claim_qty']) }}</td>
                    <td class="text-right">{{ number_format($vals['claim_percentage'], 2) }}%</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td class="text-center">Total.</td>
                <td class="text-right">{{ number_format($t_qty) }}</td>
                <td class="text-right">{{ number_format($t_retail, 0) }}</td>
                <td class="text-right">{{ number_format($t_sales, 0) }}</td>
                <td class="text-right">{{ number_format($t_claims) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
