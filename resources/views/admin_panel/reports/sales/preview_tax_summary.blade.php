<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Summary Report</title>
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
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 15px;
            font-size: 10px;
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

        .category-total-row {
            background-color: #cfd8dc;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #9e9e9e;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

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
        <div class="report-title">Tax Summary Report</div>
        <div class="date-range">
            From: {{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }} To: {{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Type</th>
                <th width="20%" class="text-left">Party</th>
                <th width="15%">CNIC / NTN #</th>
                <th width="7%">Qty</th>
                <th width="14%">Retail Value</th>
                <th width="14%">Sales Tax</th>
                <th width="15%">Inclusive Tax</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $g_qty = 0; 
                $g_retail = 0; 
                $g_tax = 0; 
                $g_inclusive = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="7" style="text-align: center; padding: 50px;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $type => $customerGroups)
                @php
                    $t_qty = 0; $t_retail = 0; $t_tax = 0; $t_inclusive = 0;
                @endphp

                @foreach($customerGroups as $customerId => $items)
                    @php
                        $customer = $items->first()->sale->customer;
                        $c_qty = $items->sum('sales_qty');
                        $c_retail = $items->sum(function($item) {
                            return $item->amount; // Using 'amount' as the base value before tax? 
                            // Or retail_price * qty?
                        });
                        
                        // Calculating tax per item because tax % might vary
                        $c_tax = $items->sum(function($item) {
                            $tax_p = $item->product && $item->product->latestPrice ? $item->product->latestPrice->sale_tax_percent : 18;
                            return $item->amount * ($tax_p / 100);
                        });
                        
                        $c_inclusive = $c_retail + $c_tax;

                        $t_qty += $c_qty;
                        $t_retail += $c_retail;
                        $t_tax += $c_tax;
                        $t_inclusive += $c_inclusive;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $type }}</td>
                        <td class="text-left">{{ $customer->customer_name }}</td>
                        <td class="text-center">{{ $customer->cnic }}</td>
                        <td class="text-center">{{ number_format($c_qty) }}</td>
                        <td class="text-right">{{ number_format($c_retail, 2) }}</td>
                        <td class="text-right">{{ number_format($c_tax, 2) }}</td>
                        <td class="text-right">{{ number_format($c_inclusive, 2) }}</td>
                    </tr>
                @endforeach

                <tr class="category-total-row">
                    <td colspan="3" class="text-right">Total {{ $type }}.</td>
                    <td class="text-center">{{ number_format($t_qty) }}</td>
                    <td class="text-right">{{ number_format($t_retail, 2) }}</td>
                    <td class="text-right">{{ number_format($t_tax, 2) }}</td>
                    <td class="text-right">{{ number_format($t_inclusive, 2) }}</td>
                </tr>

                @php
                    $g_qty += $t_qty;
                    $g_retail += $t_retail;
                    $g_tax += $t_tax;
                    $g_inclusive += $t_inclusive;
                @endphp
            @endforeach

            <tr class="grand-total-row">
                <td colspan="3" class="text-right">Grand Total</td>
                <td class="text-center">{{ number_format($g_qty) }}</td>
                <td class="text-right">{{ number_format($g_retail, 2) }}</td>
                <td class="text-right">{{ number_format($g_tax, 2) }}</td>
                <td class="text-right">{{ number_format($g_inclusive, 2) }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
