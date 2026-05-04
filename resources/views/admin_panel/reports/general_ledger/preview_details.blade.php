<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; margin: 20px; color: #000; line-height: 1.4; }
        .header-table { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { padding: 8px; font-weight: bold; font-size: 14px; }
        .ledger-table { width: 100%; border-collapse: collapse; border: 2px solid #000; }
        .ledger-table th { background: #f2f2f2; border: 1px solid #000; padding: 8px; text-transform: uppercase; font-size: 12px; font-weight: bold; color: #000; }
        .ledger-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
        .ledger-table tr:nth-child(even) { background-color: #fff; }
        .ledger-table tr.border-bottom td { border-bottom: 1px solid #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-grey { background-color: #f2f2f2; }
        .footer-info { margin-top: 15px; font-size: 12px; display: flex; justify-content: space-between; font-weight: bold; }
        .report-title { font-size: 18px; color: #000; text-decoration: underline; }
        .dr-text { color: #000; font-weight: bold; }
        .cr-text { color: #000; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            @page { size: A4 {{ $orientation }}; margin: 1cm; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer; background: #1e3a8a; color: white; border: none; border-radius: 4px;">Print Report</button>
        <button onclick="window.history.back()" style="padding: 8px 20px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 4px;">Go Back</button>
    </div>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <span class="report-title">Ledger of:- &nbsp; 
                @if($type == 'customer')
                    {{ $account_info->customer_id }} - {{ $account_info->customer_name }}
                @elseif($type == 'vendor')
                    {{ $account_info->vendor_id }} - {{ $account_info->name }}
                @else
                    {{ $account_info->account_code }} - {{ $account_info->title }}
                @endif
                </span>
            </td>
            <td class="text-right" style="width: 40%;">
                From: {{ date('d-m-y', strtotime($startDate)) }} To: {{ date('d-m-y', strtotime($endDate)) }}
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 60px;">Date</th>
                <th rowspan="2" style="width: 80px;">Reference</th>
                <th rowspan="2">Description</th>
                <th rowspan="2" style="width: 70px;">Price</th>
                <th colspan="2">Debit</th>
                <th colspan="2">Credit</th>
                <th rowspan="2" style="width: 100px;">Balance</th>
            </tr>
            <tr>
                <th style="width: 40px;">Qty</th>
                <th style="width: 70px;">Amount</th>
                <th style="width: 40px;">Qty</th>
                <th style="width: 70px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <!-- B/F -->
            <tr class="border-bottom">
                <td></td>
                <td></td>
                <td class="text-center fw-bold">B/F:</td>
                <td></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-right fw-bold">
                    @php 
                        $runningBalance = $openingBalance;
                        $totalDebitAmount = 0;
                        $totalCreditAmount = 0;
                        $totalQty = 0;
                    @endphp
                    {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                </td>
            </tr>

            @foreach($transactions as $trx)
                @php
                    $runningBalance += ($trx['debit'] - $trx['credit']);
                    $totalDebitAmount += $trx['debit'];
                    $totalCreditAmount += $trx['credit'];
                    $totalQty += $trx['qty'];
                @endphp
                <tr class="border-bottom">
                    <td class="text-center">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                    <td class="text-center">{{ $trx['ref'] }} {{ $trx['inv'] }}</td>
                    <td>{{ $trx['desc'] }}</td>
                    <td class="text-right">{{ $trx['price'] > 0 ? number_format($trx['price'], 0) : '' }}</td>
                    <td class="text-center">{{ $trx['debit'] > 0 ? $trx['qty'] : '' }}</td>
                    <td class="text-right">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-' }}</td>
                    <td class="text-center">{{ $trx['credit'] > 0 ? $trx['qty'] : '' }}</td>
                    <td class="text-right">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-' }}</td>
                    <td class="text-right fw-bold">
                        {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #eee; font-weight: bold;">
                <td colspan="3" class="text-right" style="color: #1e3a8a;">
                    @if($type == 'customer') {{ $account_info->customer_name }} @else {{ $account_info->title ?? $account_info->name }} @endif Total. >>>
                </td>
                <td></td>
                <td class="text-center">{{ $totalQty }}</td>
                <td class="text-right">{{ number_format($totalDebitAmount, 0) }}</td>
                <td class="text-center">-</td>
                <td class="text-right">{{ number_format($totalCreditAmount, 0) }}</td>
                <td class="text-right" style="color: #1e3a8a;">
                    {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-info">
        <div>Report Generated: {{ date('h:i:s A | l, F d, Y') }}</div>
        <div>System Generated Report and Required no Signature</div>
    </div>

</body>
</html>
