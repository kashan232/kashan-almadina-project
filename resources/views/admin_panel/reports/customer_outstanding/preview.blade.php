<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Outstanding Balance</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 4mm;
            background: #fff;
        }
        .no-print {
            padding: 8px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 10px;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 8px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 8px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #666;
            padding: 2px 4px;
            font-size: 9px;
        }
        .sno { width: 4%; text-align: center; }
        .type { width: 8%; text-align: center; }
        .customer { width: 20%; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .period-head {
            background: #eceff1;
            font-weight: bold;
        }
        .grand-row td {
            font-weight: bold;
            background: #eceff1;
            border-top: 2px solid #000;
        }
        .grand-label { text-align: right; }
        .empty-msg { text-align: center; padding: 24px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
        $fromLabel = $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '';
        $toLabel = $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '';
    @endphp

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ $generated_at->format('l, M j, Y') }}</div>
        <div class="report-title">Outstanding Balance</div>
        <div class="report-sub" style="font-size:8px;font-weight:bold;">Short View</div>
    </div>

    @if(!empty($rows))
    <table>
        <thead>
            <tr>
                <th class="sno" rowspan="2">S#</th>
                <th class="type" rowspan="2">Type</th>
                <th class="customer" rowspan="2">Party Name</th>
                <th rowspan="2">Opening Balance</th>
                <th colspan="4" class="period-head">Between {{ $fromLabel }} To. {{ $toLabel }}</th>
            </tr>
            <tr>
                <th>Sales</th>
                <th>SR/ PJ</th>
                <th>Receipts</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
            <tr>
                <td class="sno">{{ $i + 1 }}</td>
                <td class="type">{{ $row['party_type_label'] ?? 'Customer' }}</td>
                <td class="customer">{{ $row['party_name'] ?? $row['customer_name'] }}</td>
                <td class="num">{{ $fmt($row['opening']) }}</td>
                <td class="num">{{ $fmt($row['sales']) }}</td>
                <td class="num">{{ $fmt($row['sr_pj']) }}</td>
                <td class="num">{{ $fmt($row['receipts']) }}</td>
                <td class="num">{{ $fmt($row['balance']) }}</td>
            </tr>
            @endforeach
            <tr class="grand-row">
                <td colspan="3" class="grand-label">Grand Total Amount</td>
                <td class="num">{{ $fmt($grand['opening']) }}</td>
                <td class="num">{{ $fmt($grand['sales']) }}</td>
                <td class="num">{{ $fmt($grand['sr_pj']) }}</td>
                <td class="num">{{ $fmt($grand['receipts']) }}</td>
                <td class="num">{{ $fmt($grand['balance']) }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <p class="empty-msg">No outstanding balance found for selected filters.</p>
    @endif
</body>
</html>
