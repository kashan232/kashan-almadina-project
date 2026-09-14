<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Hold and Release Statement (Detailed)</title>
    <style>
        @page { size: A4 portrait; margin: 6mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            text-align: center;
            color: #0d47a1;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 16px;
        }
        .report-title {
            color: #800000;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 10px 0;
        }
        .header-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .date-range {
            text-align: left;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            text-align: right;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 12px;
        }
        th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: middle;
            font-size: 11px;
        }
        .party-header {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            color: #800080;
            text-decoration: underline;
            margin-top: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .product-header {
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
            text-decoration: underline;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .num { text-align: right; }
        .center { text-align: center; }
        .subtotal-row td {
            font-weight: bold;
            background: #fff;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .grand-row td {
            font-weight: bold;
            background: #e0e0e0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 12px;
        }
        .empty-msg {
            text-align: center;
            padding: 30px;
            font-size: 14px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 25px;font-weight:bold;cursor:pointer;">Print Statement</button>
        <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:10px 25px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <div class="company-name">Al Madina Traders</div>
    <div class="report-header">
        <div class="report-title">Stock Hold and Release Statement</div>
        <div class="header-meta-row">
            <div class="date-range">
                From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
                To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
            </div>
            <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 15%;">Reference</th>
                <th style="width: 20%;">Ref#</th>
                <th style="width: 16%;" class="num">Hold</th>
                <th style="width: 16%;" class="num">Release</th>
                <th style="width: 18%;" class="num">Balance</th>
            </tr>
        </thead>
    </table>

    @forelse($parties as $party)
        <div class="party-header">{{ $party['party_name'] }}</div>

        @foreach($party['products'] as $prod)
            <div class="product-header">{{ $prod['product_name'] }}</div>
            <table>
                <tbody>
                    @if(abs($prod['opening']) > 0.0001)
                        <tr style="background: #fafafa; font-weight: bold;">
                            <td colspan="3" style="text-align: right;">Opening Balance:</td>
                            <td class="num"></td>
                            <td class="num"></td>
                            <td class="num">{{ number_format($prod['opening'], 0) }}</td>
                        </tr>
                    @endif

                    @foreach($prod['entries'] as $entry)
                        <tr>
                            <td style="width: 15%;" class="center">{{ $entry['date'] }}</td>
                            <td style="width: 15%;" class="center">{{ $entry['ref_type'] }}</td>
                            <td style="width: 20%;" class="center">{{ $entry['ref_no'] }}</td>
                            <td style="width: 16%;" class="num">{{ $fmt($entry['hold']) }}</td>
                            <td style="width: 16%;" class="num">{{ $fmt($entry['release']) }}</td>
                            <td style="width: 18%;" class="num">{{ number_format($entry['balance'], 0) }}</td>
                        </tr>
                    @endforeach

                    <tr class="subtotal-row">
                        <td colspan="3" style="text-align: right; font-weight: bold; color: #800080;">Sub Total.</td>
                        <td style="width: 16%;" class="num">{{ $fmt($prod['sub_hold']) }}</td>
                        <td style="width: 16%;" class="num">{{ $fmt($prod['sub_release']) }}</td>
                        <td style="width: 18%;" class="num">{{ number_format($prod['final_balance'], 0) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @empty
        <p class="empty-msg">No hold / release statement entries found for selected filters.</p>
    @endforelse

    @if(!empty($parties))
        <table style="margin-top: 15px;">
            <tbody>
                <tr class="grand-row">
                    <td colspan="3" style="text-align: right; color: #800080;">Grand Total.</td>
                    <td style="width: 16%;" class="num">{{ $fmt($grand['hold']) }}</td>
                    <td style="width: 16%;" class="num">{{ $fmt($grand['release']) }}</td>
                    <td style="width: 18%;" class="num">{{ number_format($grand['balance'], 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
