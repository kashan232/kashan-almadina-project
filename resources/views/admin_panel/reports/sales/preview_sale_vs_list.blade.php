<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Sale vs List Comparison Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
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
            margin-bottom: 20px;
        }
        .report-title {
            color: #c2185b;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 5px;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
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

        .item-heading-row td {
            background-color: #e3f2fd;
            font-weight: bold;
            color: #0d47a1;
            font-size: 11px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .half-page {
            width: 50%;
            border-right: 1px dashed #ccc;
            min-height: 100vh;
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
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
    </div>

    <div class="report-header">
        <h1 class="report-title">Sale vs List Comparison Report</h1>
            <div class="date-range">
                From: {{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }} To: {{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="20%">Inv No.</th>
                    <th width="30%" class="text-left">Party Name</th>
                    <th width="12%">List Price</th>
                    <th width="12%">Sale Price</th>
                    <th width="12%">Diff</th>
                    <th width="14%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grouped as $productId => $items)
                    @php
                        $product = $items->first()->product;
                    @endphp
                    <tr class="item-heading-row">
                        <td colspan="6" class="text-left">ITEM: {{ $product ? $product->name : 'N/A' }}</td>
                    </tr>
                    @foreach($items as $item)
                        @php
                            $list_p = $item->retail_price ?? 0;
                            $sale_p = $item->sales_price;
                            $diff = $sale_p - $list_p;
                            $percent = $list_p > 0 ? ($diff / $list_p) * 100 : 0;
                            
                            $bgColor = '#fff';
                            $textColor = '#000';
                            
                            if ($diff > 0) {
                                // Positive (Profit/Premium) - GREEN
                                if ($percent > 10) { $bgColor = '#1b5e20'; $textColor = '#fff'; }
                                elseif ($percent > 5) { $bgColor = '#43a047'; $textColor = '#fff'; }
                                else { $bgColor = '#c8e6c9'; $textColor = '#000'; }
                            } elseif ($diff < 0) {
                                // Negative (Discount/Loss) - RED
                                $absP = abs($percent);
                                if ($absP > 10) { $bgColor = '#b71c1c'; $textColor = '#fff'; }
                                elseif ($absP > 5) { $bgColor = '#e53935'; $textColor = '#fff'; }
                                else { $bgColor = '#ffcdd2'; $textColor = '#000'; }
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $item->sale->invoice_no }}</td>
                            <td class="text-left">{{ $item->sale->customer ? $item->sale->customer->customer_name : 'CASH CUSTOMER' }}</td>
                            <td class="text-right">{{ number_format($list_p, 0) }}</td>
                            <td class="text-right">{{ number_format($sale_p, 0) }}</td>
                            <td class="text-right" style="background-color: {{ $bgColor }}; color: {{ $textColor }}; font-weight: bold;">
                                {{ number_format($diff, 0) }}
                            </td>
                            <td class="text-center" style="background-color: {{ $bgColor }}; color: {{ $textColor }}; font-weight: bold; font-size: 9px;">
                                {{ $diff >= 0 ? '+' : '' }}{{ number_format($percent, 1) }}%
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

</body>
</html>
