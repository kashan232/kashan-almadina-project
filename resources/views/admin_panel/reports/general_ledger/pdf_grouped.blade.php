<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grouped General Ledger Report</title>
    <style>
        @page {
            margin: 13mm 5mm 9mm 5mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .pdf-header {
            position: fixed;
            top: -10mm;
            left: 0;
            right: 0;
        }
        .header-table {
            width: 100%;
            border: 1.5px solid #000000;
            border-collapse: collapse;
            background: #ffffff;
        }
        .header-table td {
            padding: 3px 6px;
            font-weight: bold;
        }
        .report-title {
            font-size: 9.5pt;
            color: #000080;
            text-align: left;
        }
        .report-date {
            font-size: 8.5pt;
            color: #000000;
            text-align: right;
            white-space: nowrap;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
        }
        .ledger-table thead { display: table-header-group; }
        .ledger-table tr { page-break-inside: avoid; }
        .ledger-table th {
            background: #e2e8f0;
            border: 1px solid #000000;
            padding: 3px 2px;
            text-transform: uppercase;
            font-size: 7pt;
            font-weight: bold;
            color: #000000;
            text-align: center;
        }
        .ledger-table td {
            border: 1px solid #000000;
            padding: 2.5px 3px;
            vertical-align: middle;
            font-size: 7.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .subhead-row td {
            background-color: #f8fafc;
            border-top: 1.5px solid #000000;
            border-bottom: 1.2px solid #000000;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 8pt;
            color: #1e3a8a;
        }
        .subhead-total td {
            background-color: #f1f5f9;
            font-weight: bold;
            border-top: 1.2px solid #000000;
        }
        .subhead-title {
            font-weight: bold;
            color: #1e3a8a;
            text-decoration: underline;
        }
        .grand-total td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 8pt;
            border-top: 1.5px solid #000000;
            padding: 4px 3px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .footer-table td {
            border: none;
            font-size: 7pt;
            font-weight: bold;
            padding: 2px 0;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <table class="header-table">
            <tr>
                <td class="report-title">Ledger of:- &nbsp; {{ $mainTitle }}</td>
                <td class="report-date">From: {{ date('d-m-y', strtotime($startDate)) }} To: {{ date('d-m-y', strtotime($endDate)) }}</td>
            </tr>
        </table>
    </div>

    <table class="ledger-table">
        <colgroup>
            <col style="width: 7%;">
            <col style="width: 9%;">
            <col style="width: 30%;">
            <col style="width: 7%;">
            <col style="width: 4.5%;">
            <col style="width: 11.5%;">
            <col style="width: 4.5%;">
            <col style="width: 11.5%;">
            <col style="width: 15%;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference</th>
                <th rowspan="2">Description</th>
                <th rowspan="2">Price</th>
                <th colspan="2">Debit</th>
                <th colspan="2">Credit</th>
                <th rowspan="2">Balance</th>
            </tr>
            <tr>
                <th>Qty</th>
                <th>Amount</th>
                <th>Qty</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandDebit = 0;
                $grandCredit = 0;
                $grandQty = 0;
                $grandClosing = 0;
            @endphp
            @foreach($groupedData as $sub)
                @php
                    $runningBalance = $sub['openingBalance'];
                    $subDebit = 0;
                    $subCredit = 0;
                    $subQty = 0;
                @endphp
                <tr class="subhead-row">
                    <td colspan="9">{{ $sub['code'] ? $sub['code'] . ' - ' : '' }}{{ $sub['title'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td class="text-center fw-bold">B/F:</td>
                    <td></td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-right fw-bold">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}</td>
                </tr>
                @foreach($sub['transactions'] as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $subDebit += $trx['debit'];
                        $subCredit += $trx['credit'];
                        $subQty += ($trx['qty'] ?? 0);
                        $grandDebit += $trx['debit'];
                        $grandCredit += $trx['credit'];
                        $grandQty += ($trx['qty'] ?? 0);
                    @endphp
                    <tr>
                        <td class="text-center">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td class="text-center">{{ $trx['ref'] }} {{ $trx['inv'] }}</td>
                        <td>{{ $trx['desc'] }}</td>
                        <td class="text-right">{{ ($trx['price'] ?? 0) > 0 ? ((floor($trx['price']) == $trx['price']) ? number_format($trx['price'], 0) : number_format($trx['price'], 2)) : '' }}</td>
                        <td class="text-center">{{ ($trx['debit'] > 0 && ($trx['qty'] ?? 0) > 0) ? $trx['qty'] : '' }}</td>
                        <td class="text-right">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-' }}</td>
                        <td class="text-center">{{ ($trx['credit'] > 0 && ($trx['qty'] ?? 0) > 0) ? $trx['qty'] : '' }}</td>
                        <td class="text-right">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-' }}</td>
                        <td class="text-right fw-bold">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}</td>
                    </tr>
                @endforeach
                @php $grandClosing += $runningBalance; @endphp
                <tr class="subhead-total">
                    <td colspan="3" class="text-right"><span class="subhead-title">{{ $sub['title'] }} Total. &gt;&gt;&gt;</span></td>
                    <td></td>
                    <td class="text-center">{{ $subQty > 0 ? $subQty : '-' }}</td>
                    <td class="text-right">{{ number_format($subDebit, 0) }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">{{ number_format($subCredit, 0) }}</td>
                    <td class="text-right" style="color: #1e3a8a;">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($runningBalance), 0) }}</td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td colspan="3" class="text-right" style="padding: 4px 6px;">GRAND TOTAL AMOUNT &gt;&gt;&gt;</td>
                <td></td>
                <td class="text-center">{{ $grandQty > 0 ? $grandQty : '-' }}</td>
                <td class="text-right">{{ number_format($grandDebit, 0) }}</td>
                <td class="text-center">-</td>
                <td class="text-right">{{ number_format($grandCredit, 0) }}</td>
                <td class="text-right" style="color: #60a5fa;">{{ $grandClosing >= 0 ? 'DR.' : 'CR.' }} {{ number_format(abs($grandClosing), 0) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td style="text-align: left;">Report Generated: {{ date('h:i:s A | l, F d, Y') }}</td>
            <td style="text-align: right;">System Generated Report and Required no Signature</td>
        </tr>
    </table>
</body>
</html>
