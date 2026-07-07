<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Item Stock Ledger</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 5mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 12px;
        }
        .ledger-block {
            page-break-after: always;
            margin-bottom: 20px;
        }
        .ledger-block:last-child { page-break-after: auto; }
        .top-bar {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            align-items: start;
            margin-bottom: 6px;
            min-height: 36px;
        }
        .item-title {
            font-size: 16px;
            font-weight: bold;
            color: #1565c0;
        }
        .wh-label {
            font-size: 10px;
            font-weight: bold;
            color: #666;
            margin-top: 2px;
        }
        .date-range {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding-top: 4px;
        }
        .date-range span { text-decoration: underline; }
        .gen-date {
            font-size: 11px;
            color: #333;
            text-align: right;
            padding-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }
        th {
            background: #1565c0;
            color: #fff;
            border: 1px solid #000;
            padding: 4px 2px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
        }
        td {
            border: 1px solid #666;
            padding: 2px 3px;
            vertical-align: middle;
            text-align: center;
            font-size: 8px;
            word-wrap: break-word;
        }
        tr.data-row:nth-child(even) td { background: #f7f7f7; }
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
            border-top: 2px solid #000;
        }
        .footer-wrap {
            margin-top: 6px;
            display: flex;
            justify-content: flex-end;
        }
        .hold-box {
            border: 2px solid #1565c0;
            padding: 5px 16px;
            font-weight: bold;
            font-size: 11px;
            color: #1565c0;
            min-width: 130px;
            text-align: center;
        }
        .empty-msg {
            text-align: center;
            padding: 40px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
        $fmtAmt = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
        $fmtPrice = fn($v) => ($v === null || abs((float)$v) < 0.0001) ? '' : number_format((float)$v, 0);
    @endphp

    @forelse($ledgers as $ledger)
    <div class="ledger-block">
        <div class="top-bar">
            <div>
                <div class="item-title">Item: {{ $ledger['product_name'] }}</div>
                @if(!empty($ledger['warehouse_label']))
                <div class="wh-label">Location: {{ $ledger['warehouse_label'] }}</div>
                @endif
            </div>
            <div class="date-range">
                From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
                &nbsp;&nbsp;To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
            </div>
            <div class="gen-date">{{ now()->format('l, F j, Y') }}</div>
        </div>

        <table>
            <colgroup>
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:4%;">
                <col style="width:12%;">
                <col style="width:5%;">
                <col style="width:6%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:4%;">
                <col style="width:4%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:4%;">
                <col style="width:5%;">
            </colgroup>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Party Name</th>
                    <th>Price</th>
                    <th>Amount</th>
                    <th>OPN Balance</th>
                    <th>Trn IN (OPB)</th>
                    <th>Purchase</th>
                    <th>Purchase Return</th>
                    <th>Sales Qty</th>
                    <th>Sales Return</th>
                    <th>Hold</th>
                    <th>Rel</th>
                    <th>Claim IN</th>
                    <th>Claim Out</th>
                    <th>TRN IN</th>
                    <th>TRN Out</th>
                    <th>Waste</th>
                    <th>Balance</th>
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
                    <td>{{ $fmt($row['cols']['claim_in'] ?? 0) }}</td>
                    <td class="{{ ($row['cols']['claim_out'] ?? 0) > 0 ? 'sales-red' : '' }}">{{ $fmt($row['cols']['claim_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['trf_in'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['trf_out'] ?? 0) }}</td>
                    <td>{{ $fmt($row['cols']['waste'] ?? 0) }}</td>
                    <td><b>{{ $fmt($row['balance']) }}</b></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" style="text-align:right;">Total:</td>
                    <td class="num-r">{{ $fmtAmt($ledger['totals']['amount']) }}</td>
                    <td>{{ $fmt($ledger['totals']['opn_balance']) }}</td>
                    <td></td>
                    <td>{{ $fmt($ledger['totals']['pur']) }}</td>
                    <td>{{ $fmt($ledger['totals']['pur_ret']) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['sales']) }}</td>
                    <td>{{ $fmt($ledger['totals']['sales_ret']) }}</td>
                    <td>{{ $fmt($ledger['totals']['hold']) }}</td>
                    <td>{{ $fmt($ledger['totals']['release']) }}</td>
                    <td>{{ $fmt($ledger['totals']['claim_in']) }}</td>
                    <td class="sales-red">{{ $fmt($ledger['totals']['claim_out']) }}</td>
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
        <p class="empty-msg">No ledger data found. Please select an Item, Warehouse (optional), and Date range.</p>
    @endforelse
</body>
</html>
