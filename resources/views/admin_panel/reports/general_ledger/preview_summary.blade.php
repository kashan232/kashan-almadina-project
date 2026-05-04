<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger Summary</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; margin: 20px; color: #000; line-height: 1.5; }
        .header-table { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 15px; }
        .header-table td { padding: 10px; font-weight: bold; font-size: 16px; }
        .ledger-table { width: 100%; border-collapse: collapse; border: 2px solid #000; }
        .ledger-table th { background: #f2f2f2; border: 1px solid #000; padding: 12px; text-transform: uppercase; font-weight: bold; }
        .ledger-table td { border: 1px solid #000; padding: 12px; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .report-title { font-size: 20px; color: #000; text-decoration: underline; }
        @media print { 
            .no-print { display: none; } 
            @page { size: A4 {{ $orientation }}; margin: 1cm; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer; background: #1e3a8a; color: white; border: none; border-radius: 4px;">Print Summary</button>
        <button onclick="window.history.back()" style="padding: 8px 20px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 4px;">Go Back</button>
    </div>

    <table class="header-table">
        <tr>
            <td>
                <span class="report-title">Summary Ledger: 
                @if($type == 'customer') {{ $account_info->customer_name }} @elseif($type == 'vendor') {{ $account_info->name }} @else {{ $account_info->title }} @endif
                </span>
            </td>
            <td class="text-right">
                Period: {{ date('d-m-y', strtotime($startDate)) }} to {{ date('d-m-y', strtotime($endDate)) }}
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Opening Balance (B/F)</td>
                <td class="text-right fw-bold">{{ number_format($openingBalance, 0) }}</td>
            </tr>
            @php 
                $totalDebit = 0; $totalCredit = 0; 
                foreach($transactions as $t) { $totalDebit += $t['debit']; $totalCredit += $t['credit']; }
            @endphp
            <tr>
                <td>Total Transactions (Debit)</td>
                <td class="text-right">{{ number_format($totalDebit, 0) }}</td>
            </tr>
            <tr>
                <td>Total Transactions (Credit)</td>
                <td class="text-right">{{ number_format($totalCredit, 0) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr style="background: #eee; font-weight: bold;">
                <td class="text-right">Closing Balance</td>
                <td class="text-right">{{ number_format($openingBalance + $totalDebit - $totalCredit, 0) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
