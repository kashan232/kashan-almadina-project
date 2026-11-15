<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Stock Ledger</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 5mm 8mm 5mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 6pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        .ledger-block {
            margin-bottom: 12px;
            page-break-after: always;
        }
        .ledger-block:last-child {
            page-break-after: auto;
        }
        .top-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .top-tbl td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .item-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1565c0;
        }
        .wh-label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #555555;
            margin-top: 1px;
        }
        .date-range {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }
        .gen-date {
            font-size: 7pt;
            color: #333333;
            text-align: right;
        }

        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            table-layout: fixed;
        }
        table.ledger-table thead {
            display: table-header-group;
        }
        table.ledger-table tr {
            page-break-inside: avoid;
        }
        table.ledger-table th {
            background: #1565c0;
            color: #ffffff;
            border: 1px solid #000000;
            padding: 2.5px 1px;
            font-size: 5.5pt;
            font-weight: bold;
            text-align: center;
            line-height: 1.15;
        }
        table.ledger-table td {
            border: 1px solid #666666;
            padding: 1.5px 1.5px;
            vertical-align: middle;
            text-align: center;
            font-size: 5.8pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        tr.data-row:nth-child(even) td { background: #f8f9fa; }
        .party { text-align: left; }
        .num-r { text-align: right; }
        .sales-red { color: #c00000; font-weight: bold; }
        .bf-row td {
            background: #e8e8e8 !important;
            font-weight: bold;
        }
        .total-row td {
            background: #d9d9d9 !important;
            font-weight: bold;
            border-top: 1.5px solid #000000;
        }
        .footer-wrap {
            margin-top: 4px;
            text-align: right;
        }
        .hold-box {
            display: inline-block;
            border: 1.5px solid #1565c0;
            padding: 2px 10px;
            font-weight: bold;
            font-size: 7.5pt;
            color: #1565c0;
            background: #ffffff;
        }
    </style>
</head>
<body>
    @php
        $fmt = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
        $fmtAmt = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
        $fmtPrice = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
    @endphp

    @forelse($ledgers as $ledger)
    <div class="ledger-block">
        <table class="top-tbl">
            <tr>
                <td style="width: 45%;">
                    <div class="item-title">Item: {{ $ledger['product_name'] }}</div>
                    @if(!empty($ledger['warehouse_label']))
                    <div class="wh-label">Location: {{ $ledger['warehouse_label'] }}</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: center;">
                    <div class="date-range">
                        From: {{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}
                        &nbsp;&nbsp;To: {{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}
                    </div>
                </td>
                <td style="width: 25%; text-align: right;">
                    <div class="gen-date">{{ now()->format('l, F j, Y') }}</div>
                </td>
            </tr>
        </table>

        <table class="ledger-table">
            <colgroup>
                <col style="width:3.5%;">
                <col style="width:4.5%;">
                <col style="width:3.5%;">
                <col style="width:11%;">
                <col style="width:4.5%;">
                <col style="width:5.5%;">
                <col style="width:4.5%;">
                <col style="width:4%;">
                <col style="width:4.5%;">
                <col style="width:4.5%;">
                <col style="width:4.5%;">
                <col style="width:4.5%;">
                <col style="width:3.5%;">
                <col style="width:3.5%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:5%;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">ID</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Type</th>
                    <th rowspan="2">Party Name</th>
                    <th rowspan="2">Price</th>
                    <th rowspan="2">Amount</th>
                    <th rowspan="2">OPN Balance</th>
                    <th rowspan="2">Trn IN (OPB)</th>
                    <th rowspan="2">Purchase</th>
                    <th rowspan="2">Purchase Return</th>
                    <th rowspan="2">Sales Qty</th>
                    <th rowspan="2">Sales Return</th>
                    <th rowspan="2">Hold</th>
                    <th rowspan="2">Rel</th>
                    <th colspan="3" style="background:#0d47a1;">Customer Claim</th>
                    <th colspan="2" style="background:#0d47a1;">Claim Acceptance</th>
                    <th colspan="2" style="background:#0d47a1;">Claim Item Receipt</th>
                    <th rowspan="2">TRN IN</th>
                    <th rowspan="2">TRN Out</th>
                    <th rowspan="2">Waste</th>
                    <th rowspan="2">Balance</th>
                </tr>
                <tr>
                    <th style="background:#e65100;">CLM-REP</th>
                    <th>CLM-IN</th>
                    <th style="background:#e65100;">CLM-OUT</th>
                    <th>CLM-IN</th>
                    <th style="background:#e65100;">CLM-OUT</th>
                    <th>CLM-IN</th>
                    <th style="background:#e65100;">CLM-OUT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledger['rows'] as $row)
                <tr class="{{ !empty($row['is_bf']) ? 'bf-row' : 'data-row' }}">
                    <td>{{ $row['ref_id'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td><b>{{ $row['type_code'] }}</b></td>
                    <td class="party">{{ $row['party_name'] }}</td>
                    <td class="num-r">{{ $fmtPrice($row['price']) }}</td>
                    <td class="num-r">{{ $fmtAmt($row['amount']) }}</td>
                    <td>{{ !empty($row['is_bf']) ? $fmt($row['opn_balance']) : '' }}</td>
                    <td></td>
                    <td>{{ $fmt($row['cols']['pur'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['pur_ret'] ?? 0) }}</td>
                    <td class="{{ ($row['cols']['sales'] ?? 0) > 0 ? 'sales-red' : '' }}">{{ $fmt($row['cols']['sales'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['sales_ret'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['hold'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['release'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['clm_rep'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['clm_in'] ?? 0) }}</td>
                    <td class="{{ (($row['cols']['clm_out'] ?? 0) > 0) ? 'sales-red' : '' }}">{{ $fmt($row['cols']['clm_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['cla_in'] ?? 0) }}</td>
                    <td class="{{ (($row['cols']['cla_out'] ?? 0) > 0) ? 'sales-red' : '' }}">{{ $fmt($row['cols']['cla_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['cli_in'] ?? 0) }}</td>
                    <td class="{{ (($row['cols']['cli_out'] ?? 0) > 0) ? 'sales-red' : '' }}">{{ $fmt($row['cols']['cli_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['trf_in'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['trf_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['waste'] ?? 0) }}</td>
                    <td><b>{{ $fmt($row['balance']) }}</b></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Total:</td>
                    <td class="num-r"></td>
                    <td class="num-r">{{ $fmtAmt($ledger['totals']['amount']) }}</td>
                    <td>{{ $fmt($ledger['totals']['opn_balance']) }}</td>
                    <td></td>
                    <td>{{ $fmt($ledger['totals']['pur']) }}</td>
                    <td>{{ $fmt($ledger['totals']['pur_ret']) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['sales']) }}</td>
                    <td>{{ $fmt($ledger['totals']['sales_ret']) }}</td>
                    <td>{{ $fmt($ledger['totals']['hold']) }}</td>
                    <td>{{ $fmt($ledger['totals']['release']) }}</td>
                    <td>{{ $fmt($ledger['totals']['clm_rep'] ?? 0) }}</td>
                    <td>{{ $fmt($ledger['totals']['clm_in'] ?? 0) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['clm_out'] ?? 0) }}</td>
                    <td>{{ $fmt($ledger['totals']['cla_in'] ?? 0) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['cla_out'] ?? 0) }}</td>
                    <td>{{ $fmt($ledger['totals']['cli_in'] ?? 0) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['cli_out'] ?? 0) }}</td>
                    <td>{{ $fmt($ledger['totals']['trf_in']) }}</td>
                    <td>{{ $fmt($ledger['totals']['trf_out']) }}</td>
                    <td>{{ $fmt($ledger['totals']['waste']) }}</td>
                    <td><b>{{ $fmt($ledger['totals']['balance']) }}</b></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-wrap">
            <div class="hold-box">Hold Qty: {{ number_format($ledger['hold_qty'], 2) }}</div>
        </div>
    </div>
    @empty
        <p style="text-align:center; padding:30px; font-size:9pt;">No ledger data found for selected filters.</p>
    @endforelse
</body>
</html>
