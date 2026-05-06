<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger Summary</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #000; line-height: 1.2; }
        .wrapper { width: 100%; border: 1px solid #999; padding: 2px; }
        .header-box { border: 1px solid #333; padding: 5px; margin-bottom: 5px; background: #fff; display: flex; justify-content: space-between; align-items: center; }
        .header-box h2 { margin: 0; font-size: 16px; color: #1e3a8a; }
        .header-box .period { font-size: 14px; font-weight: bold; }
        
        .ledger-table { width: 100%; border-collapse: collapse; }
        .ledger-table th { background: #e5e7eb; border: 1px solid #999; padding: 4px 8px; text-align: left; font-weight: bold; font-size: 11px; }
        .ledger-table td { border: 1px solid #ccc; padding: 4px 8px; vertical-align: top; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        
        .footer-row { background: #fff; font-weight: bold; border-top: 2px solid #333; }
        .footer-meta { margin-top: 5px; display: flex; justify-content: space-between; font-size: 10px; font-weight: bold; }
        
        @media print { 
            .no-print { display: none; } 
            body { margin: 0; padding: 10px; }
            .wrapper { border: none; }
            @page { size: A4 {{ $orientation }}; margin: 0.5cm; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 10px; text-align: right;">
        <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer; background: #1e3a8a; color: white; border: none; border-radius: 3px;">Print</button>
        <button onclick="window.history.back()" style="padding: 5px 15px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 3px;">Back</button>
    </div>

    <div class="wrapper">
        <div class="header-box">
            <h2>Ledger of:- 
                @if($type == 'customer') 
                    {{ $account_info->id }} - {{ $account_info->customer_name }} 
                @elseif($type == 'vendor') 
                    {{ $account_info->id }} - {{ $account_info->name }} 
                @else 
                    {{ $account_info->account_code }} - {{ $account_info->title }} 
                @endif
            </h2>
            <div class="period">From: {{ date('d-m-y', strtotime($startDate)) }} To: {{ date('d-m-y', strtotime($endDate)) }}</div>
        </div>

        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Date</th>
                    <th style="width: 80px;">Reference</th>
                    <th>Description</th>
                    <th style="width: 50px;" class="text-center">Qty</th>
                    <th style="width: 100px;" class="text-right">Debit</th>
                    <th style="width: 100px;" class="text-right">Credit</th>
                    <th style="width: 120px;" class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $runningBalance = (float)$openingBalance; 
                    $totalDebit = 0; 
                    $totalCredit = 0; 
                    $totalQty = 0;
                @endphp
                
                <!-- Opening Balance Line -->
                <tr>
                    <td></td>
                    <td class="text-center">0</td>
                    <td class="fw-bold">: B/F</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right fw-bold">
                        {{ $runningBalance >= 0 ? 'DR' : 'CR' }} {{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>

                @foreach($transactions as $trx)
                    @php 
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $totalDebit += $trx['debit'];
                        $totalCredit += $trx['credit'];
                        $totalQty += $trx['qty'];
                    @endphp
                    <tr>
                        <td>{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td>{{ $trx['ref'] }} &nbsp; {{ $trx['inv'] }}</td>
                        <td>{{ $trx['desc'] }}</td>
                        <td class="text-center">{{ $trx['qty'] > 0 ? number_format($trx['qty'], 0) : '' }}</td>
                        <td class="text-right">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '' }}</td>
                        <td class="text-right">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '' }}</td>
                        <td class="text-right fw-bold">
                            {{ $runningBalance >= 0 ? 'DR' : 'CR' }} {{ number_format(abs($runningBalance), 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="footer-row">
                    <td colspan="3" class="text-right">
                        @if($type == 'customer') {{ $account_info->customer_name }} @elseif($type == 'vendor') {{ $account_info->name }} @else {{ $account_info->title }} @endif Total. >>>
                    </td>
                    <td class="text-center">{{ number_format($totalQty, 0) }}</td>
                    <td class="text-right">{{ number_format($totalDebit, 0) }}</td>
                    <td class="text-right">{{ number_format($totalCredit, 0) }}</td>
                    <td class="text-right">
                        {{ $runningBalance >= 0 ? 'DR' : 'CR' }} {{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer-meta">
        <div>Report Generated: {{ date('h:i:s A | l, F d, Y') }}</div>
        <div>System Generated Report and Required no Signature</div>
    </div>

</body>
</html>
