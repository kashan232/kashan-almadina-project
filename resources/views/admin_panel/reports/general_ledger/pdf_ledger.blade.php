<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @php
        if ($type == 'customer') {
            $acCode = $account_info->customer_id ?: $account_info->id;
            $acName = $account_info->customer_name;
        } elseif ($type == 'vendor') {
            $acCode = $account_info->vendor_id ?: $account_info->id;
            $acName = $account_info->name;
        } else {
            $acCode = $account_info->account_code ?: $account_info->id;
            $acName = $account_info->title;
        }
        $fromLabel = $startDate ? date('d-m-y', strtotime($startDate)) : '';
        $toLabel = $endDate ? date('d-m-y', strtotime($endDate)) : '';
        $isSummary = ($reportMode ?? 'details') === 'summary';
    @endphp
    <title>Ledger - {{ $acCode }} {{ $acName }}</title>
    <style>
        @page {
            margin: 12.5mm 4mm 9mm 4mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .pdf-header {
            position: fixed;
            top: -9.5mm;
            left: 0;
            right: 0;
        }
        .header-box-tbl {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000000;
            background: #ffffff;
        }
        .header-box-tbl td {
            padding: 3px 6px;
            vertical-align: middle;
        }
        .header-title {
            font-size: 10pt;
            font-weight: bold;
            color: #000080;
            text-align: left;
        }
        .header-date {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000000;
            text-align: right;
            white-space: nowrap;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #777777;
        }
        .ledger-table thead {
            display: table-header-group;
        }
        .ledger-table tr {
            page-break-inside: avoid;
        }
        .ledger-table th {
            background: #e0e0e0;
            border: 1px solid #777777;
            padding: 2.5px 2px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #000000;
            text-align: center;
            line-height: 1.15;
        }
        .ledger-table td {
            border: 1px solid #777777;
            padding: 2px 3px;
            font-size: 7.5pt;
            line-height: 1.15;
            vertical-align: middle;
            color: #000000;
        }

        @if($isSummary)
            .col-date   { width: 7%; text-align: center; }
            .col-ref    { width: 8%; text-align: center; }
            .col-desc   { width: 38%; text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
            .col-qty    { width: 4.5%; text-align: center; padding: 1.5px 1px; }
            .col-amt    { width: 13.5%; text-align: right; }
            .col-credit { width: 13.5%; text-align: right; }
            .col-bal    { width: 15.5%; text-align: right; padding: 1.5px 3px; }
        @else
            .col-date       { width: 6.5%; text-align: center; }
            .col-ref        { width: 7.5%; text-align: center; }
            .col-desc       { width: 32%; text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
            .col-price      { width: 6%; text-align: right; }
            .col-debit-qty  { width: 4.5%; text-align: center; padding: 1.5px 1px; }
            .col-debit-amt  { width: 12%; text-align: right; }
            .col-credit-qty { width: 4.5%; text-align: center; padding: 1.5px 1px; }
            .col-credit-amt { width: 12%; text-align: right; }
            .col-bal        { width: 15%; text-align: right; padding: 1.5px 3px; }
        @endif

        /* Seamless Date / Reference / Description borders */
        .ledger-table th.col-date,
        .ledger-table td.col-date { border-right: none; }
        .ledger-table th.col-ref,
        .ledger-table td.col-ref { border-left: none; border-right: none; }
        .ledger-table th.col-desc,
        .ledger-table td.col-desc { border-left: none; border-right: 1px solid #777777; }

        /* Seamless Qty and Amount inside Debit & Credit */
        .ledger-table tbody td.col-debit-qty,
        .ledger-table tbody td.col-credit-qty { border-right: none; }
        .ledger-table tbody td.col-debit-amt,
        .ledger-table tbody td.col-credit-amt { border-left: none; }

        /* DR/CR Split inside Balance column */
        .bal-tbl {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .bal-tbl td {
            border: none !important;
            padding: 0 !important;
            font-size: 7.2pt;
            line-height: 1.1;
            background: transparent;
        }
        .bal-tbl td.drcr {
            text-align: left;
            font-weight: bold;
            width: 24%;
        }
        .bal-tbl td.bal-val {
            text-align: right;
            font-weight: bold;
            width: 76%;
        }

        .total-row td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            background: #ffffff;
            padding: 2.5px 3px;
            font-weight: bold;
            font-size: 7.5pt;
            color: #000080;
        }
        .total-label {
            text-align: right;
            color: #000080;
            font-weight: bold;
            padding-right: 6px;
        }
        .total-val {
            color: #000080;
            font-weight: bold;
        }

        .report-footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .report-footer td {
            border: none;
            padding: 2px 0;
            font-size: 7pt;
            color: #000000;
        }
        .report-footer td.fl {
            text-align: left;
            font-weight: bold;
        }
        .report-footer td.fr {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <table class="header-box-tbl">
            <tr>
                <td class="header-title">Ledger of:- &nbsp; {{ $acCode }} &nbsp;-&nbsp; {{ $acName }}</td>
                <td class="header-date">From: {{ $fromLabel }} To: {{ $toLabel }}</td>
            </tr>
        </table>
    </div>

    @php
        $runningBalance = (float)$openingBalance;
        $totalDebitAmount = 0;
        $totalCreditAmount = 0;
        $totalDebitQty = 0;
        $totalCreditQty = 0;
        $totalSummaryQty = 0;

        $fmtQty = function($q) {
            $val = (float)$q;
            if (abs($val) < 0.0001) return '';
            return (floor($val) == $val) ? ((int)$val . ' -') : (number_format($val, 1) . ' -');
        };
    @endphp

    <table class="ledger-table">
        @if($isSummary)
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-ref">Reference</th>
                    <th class="col-desc">Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-amt">Debit</th>
                    <th class="col-credit">Credit</th>
                    <th class="col-bal">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-date"></td>
                    <td class="col-ref"></td>
                    <td class="col-desc" style="text-align: center; font-weight: bold;">B/F:</td>
                    <td class="col-qty"></td>
                    <td class="col-amt" style="text-align: center;">-</td>
                    <td class="col-credit" style="text-align: center;">-</td>
                    <td class="col-bal">
                        <table class="bal-tbl"><tr>
                            <td class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                            <td class="bal-val">{{ number_format(abs($runningBalance), 0) }}</td>
                        </tr></table>
                    </td>
                </tr>
                @foreach($transactions as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $totalDebitAmount += $trx['debit'];
                        $totalCreditAmount += $trx['credit'];
                        $totalSummaryQty += (float)($trx['qty'] ?? 0);
                        $qtyDisp = ((float)($trx['qty'] ?? 0) > 0) ? ((floor($trx['qty']) == $trx['qty']) ? (int)$trx['qty'] : number_format($trx['qty'], 1)) : '';
                    @endphp
                    <tr>
                        <td class="col-date">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td class="col-ref">{{ trim(($trx['ref'] ?? '') . ' ' . ($trx['inv'] ?? '')) }}</td>
                        <td class="col-desc">{{ $trx['desc'] }}</td>
                        <td class="col-qty">{{ $qtyDisp }}</td>
                        <td class="col-amt" style="{{ $trx['debit'] > 0 ? '' : 'text-align: center;' }}">{{ $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-' }}</td>
                        <td class="col-credit" style="{{ $trx['credit'] > 0 ? '' : 'text-align: center;' }}">{{ $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-' }}</td>
                        <td class="col-bal">
                            <table class="bal-tbl"><tr>
                                <td class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                                <td class="bal-val">{{ number_format(abs($runningBalance), 0) }}</td>
                            </tr></table>
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="total-label">{{ $acName }} Total. &gt;&gt;&gt;</td>
                    <td class="col-qty total-val">{{ $totalSummaryQty > 0 ? ((floor($totalSummaryQty) == $totalSummaryQty) ? (int)$totalSummaryQty : number_format($totalSummaryQty, 1)) : '' }}</td>
                    <td class="col-amt total-val">{{ number_format($totalDebitAmount, 0) }}</td>
                    <td class="col-credit total-val" style="{{ $totalCreditAmount > 0 ? '' : 'text-align: center;' }}">{{ $totalCreditAmount > 0 ? number_format($totalCreditAmount, 0) : '-' }}</td>
                    <td class="col-bal total-val">
                        <table class="bal-tbl"><tr>
                            <td class="drcr" style="color: #000080;">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                            <td class="bal-val" style="color: #000080;">{{ number_format(abs($runningBalance), 0) }}</td>
                        </tr></table>
                    </td>
                </tr>
            </tbody>
        @else
            <thead>
                <tr>
                    <th rowspan="2" class="col-date">Date</th>
                    <th rowspan="2" class="col-ref">Reference</th>
                    <th rowspan="2" class="col-desc">Description</th>
                    <th rowspan="2" class="col-price">Price</th>
                    <th colspan="2">Debit</th>
                    <th colspan="2">Credit</th>
                    <th rowspan="2" class="col-bal">Balance</th>
                </tr>
                <tr>
                    <th class="col-debit-qty">Qty</th>
                    <th class="col-debit-amt">Amount</th>
                    <th class="col-credit-qty">Qty</th>
                    <th class="col-credit-amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-date"></td>
                    <td class="col-ref"></td>
                    <td class="col-desc" style="text-align: center; font-weight: bold;">B/F:</td>
                    <td class="col-price"></td>
                    <td class="col-debit-qty"></td>
                    <td class="col-debit-amt" style="text-align: center;">-</td>
                    <td class="col-credit-qty"></td>
                    <td class="col-credit-amt" style="text-align: center;">-</td>
                    <td class="col-bal">
                        <table class="bal-tbl"><tr>
                            <td class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                            <td class="bal-val">{{ number_format(abs($runningBalance), 0) }}</td>
                        </tr></table>
                    </td>
                </tr>
                @foreach($transactions as $trx)
                    @php
                        $runningBalance += ($trx['debit'] - $trx['credit']);
                        $totalDebitAmount += $trx['debit'];
                        $totalCreditAmount += $trx['credit'];
                        $isDebitItem = ($trx['debit'] > 0 || (isset($trx['ref']) && $trx['ref'] === 'SJ' && (float)($trx['qty'] ?? 0) > 0));
                        $isCreditItem = ($trx['credit'] > 0 || (isset($trx['ref']) && in_array($trx['ref'], ['SRJ', 'PRJ', 'PJ', 'CLM']) && (float)($trx['qty'] ?? 0) > 0));

                        if ($isDebitItem) $totalDebitQty += (float)$trx['qty'];
                        if ($isCreditItem) $totalCreditQty += (float)$trx['qty'];

                        $dQty = ($isDebitItem && (float)($trx['qty'] ?? 0) > 0) ? $fmtQty($trx['qty']) : '';
                        $dAmt = $trx['debit'] > 0 ? number_format($trx['debit'], 0) : '-';
                        $cQty = ($isCreditItem && (float)($trx['qty'] ?? 0) > 0) ? $fmtQty($trx['qty']) : '';
                        $cAmt = $trx['credit'] > 0 ? number_format($trx['credit'], 0) : '-';
                        $priceDisplay = ($trx['price'] ?? 0) > 0 ? ((floor($trx['price']) == $trx['price']) ? number_format($trx['price'], 0) : number_format($trx['price'], 2)) : '';
                    @endphp
                    <tr>
                        <td class="col-date">{{ date('d-m-y', strtotime($trx['date'])) }}</td>
                        <td class="col-ref">{{ trim(($trx['ref'] ?? '') . ' ' . ($trx['inv'] ?? '')) }}</td>
                        <td class="col-desc">{{ $trx['desc'] }}</td>
                        <td class="col-price">{{ $priceDisplay }}</td>
                        <td class="col-debit-qty">{{ $dQty }}</td>
                        <td class="col-debit-amt" style="{{ $trx['debit'] > 0 ? '' : 'text-align: center;' }}">{{ $dAmt }}</td>
                        <td class="col-credit-qty">{{ $cQty }}</td>
                        <td class="col-credit-amt" style="{{ $trx['credit'] > 0 ? '' : 'text-align: center;' }}">{{ $cAmt }}</td>
                        <td class="col-bal">
                            <table class="bal-tbl"><tr>
                                <td class="drcr">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                                <td class="bal-val">{{ number_format(abs($runningBalance), 0) }}</td>
                            </tr></table>
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="total-label">{{ $acName }} Total. &gt;&gt;&gt;</td>
                    <td class="col-price"></td>
                    <td class="col-debit-qty total-val">{{ $totalDebitQty > 0 ? $fmtQty($totalDebitQty) : '' }}</td>
                    <td class="col-debit-amt total-val">{{ number_format($totalDebitAmount, 0) }}</td>
                    <td class="col-credit-qty total-val">{{ $totalCreditQty > 0 ? $fmtQty($totalCreditQty) : '' }}</td>
                    <td class="col-credit-amt total-val" style="{{ $totalCreditAmount > 0 ? '' : 'text-align: center;' }}">{{ $totalCreditAmount > 0 ? number_format($totalCreditAmount, 0) : '-' }}</td>
                    <td class="col-bal total-val">
                        <table class="bal-tbl"><tr>
                            <td class="drcr" style="color: #000080;">{{ $runningBalance >= 0 ? 'DR.' : 'CR.' }}</td>
                            <td class="bal-val" style="color: #000080;">{{ number_format(abs($runningBalance), 0) }}</td>
                        </tr></table>
                    </td>
                </tr>
            </tbody>
        @endif
    </table>

    <table class="report-footer">
        <tr>
            <td class="fl">Report Generated: {{ date('g:i:s A | l, F d, Y') }}</td>
            <td class="fr">System Generated Report and Required no Signature</td>
        </tr>
    </table>
</body>
</html>
