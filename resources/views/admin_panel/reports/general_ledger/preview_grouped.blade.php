<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grouped General Ledger Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; margin: 15px; color: #000; line-height: 1.3; }
        .header-table { width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { padding: 6px 8px; font-weight: bold; font-size: 13px; }
        .ledger-table { width: 100%; border-collapse: collapse; border: 2px solid #000; margin-bottom: 12px; }
        .ledger-table th { background: #e2e8f0; border: 1px solid #000; padding: 5px; text-transform: uppercase; font-size: 10px; font-weight: bold; color: #000; text-align: center; }
        .ledger-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .subhead-title { font-weight: bold; color: #1e3a8a; text-decoration: underline; font-size: 11px; }
        .subhead-total td { background-color: #f1f5f9; font-weight: bold; border-top: 1.5px solid #000; }
        .footer-info { margin-top: 10px; font-size: 10px; display: flex; justify-content: space-between; font-weight: bold; }
        .report-title { font-size: 16px; color: #000; text-decoration: underline; }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            @page { size: A4 {{ $orientation }}; margin: 0.8cm; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 18px; cursor: pointer; background: #1e3a8a; color: white; border: none; border-radius: 4px; font-weight: bold;">Print Report</button>
        <button onclick="window.history.back()" style="padding: 6px 18px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 4px; font-weight: bold;">Go Back</button>
    </div>

    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <span class="report-title">Ledger of:- &nbsp; {{ $mainTitle }}</span>
            </td>
            <td class="text-right" style="width: 35%;">
                From: {{ date('d-m-y', strtotime($startDate)) }} To: {{ date('d-m-y', strtotime($endDate)) }}
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 55px;">Date</th>
                <th rowspan="2" style="width: 75px;">Reference</th>
                <th rowspan="2">Description</th>
                <th rowspan="2" style="width: 60px;">Price</th>
                <th colspan="2">Debit</th>
                <th colspan="2">Credit</th>
                <th rowspan="2" style="width: 90px;">Balance</th>
            </tr>
            <tr>
                <th style="width: 35px;">Qty</th>
                <th style="width: 65px;">Amount</th>
                <th style="width: 35px;">Qty</th>
                <th style="width: 65px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedData as $sub)
                @php
                    $runningBalance = $sub['openingBalance'];
                    $subDebit = 0;
                    $subCredit = 0;
                    $subQty = 0;
                @endphp

                {{-- Sub-head Section Title Banner --}}
                <tr style="background-color: #f8fafc; border-top: 2px solid #000; border-bottom: 1.5px solid #000;">
                    <td colspan="9" style="padding: 6px 10px; font-weight: bold; font-size: 12px; color: #1e3a8a;">
                        <i class="fas fa-folder-open me-1"></i> {{ $sub['code'] ? $sub['code'] . ' - ' : '' }}{{ $sub['title'] }}
                    </td>
                </tr>

                {{-- Sub-head / Account B/F Row --}}
                <tr class="bg-light">
                    <td></td>
                    <td></td>
                    <td class="text-center fw-bold">B/F:</td>
                    <td></td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-right fw-bold">
                        {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>

                {{-- Sub-head Transactions --}}
                @foreach($sub['transactions'] as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $subDebit += $trx['debit'];
                        $subCredit += $trx['credit'];
                        $subQty += ($trx['qty'] ?? 0);
                    @endphp
                    <tr>
                        <td class="text-center">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td class="text-center">{{ $trx['ref'] }} {{ $trx['inv'] }}</td>
                        <td>{{ $trx['desc'] }}</td>
                        <td class="text-right">{{ ($trx['price'] ?? 0) > 0 ? number_format($trx['price'], 0) : '' }}</td>
                        <td class="text-center">{{ ($trx['debit'] > 0 && ($trx['qty'] ?? 0) > 0) ? $trx['qty'] : '' }}</td>
                        <td class="text-right">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-' }}</td>
                        <td class="text-center">{{ ($trx['credit'] > 0 && ($trx['qty'] ?? 0) > 0) ? $trx['qty'] : '' }}</td>
                        <td class="text-right">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-' }}</td>
                        <td class="text-right fw-bold">
                            {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                        </td>
                    </tr>
                @endforeach

                {{-- Sub-head Total Row (Matching User Screenshot) --}}
                <tr class="subhead-total">
                    <td colspan="3" class="text-right">
                        <span class="subhead-title">{{ $sub['title'] }} Total. >>></span>
                    </td>
                    <td></td>
                    <td class="text-center">{{ $subQty > 0 ? $subQty : '-' }}</td>
                    <td class="text-right">{{ number_format($subDebit, 0) }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">{{ number_format($subCredit, 0) }}</td>
                    <td class="text-right" style="color: #1e3a8a;">
                        {{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-info">
        <div>Report Generated: {{ date('h:i:s A | l, F d, Y') }}</div>
        <div>System Generated Report and Required no Signature</div>
    </div>

</body>
</html>
