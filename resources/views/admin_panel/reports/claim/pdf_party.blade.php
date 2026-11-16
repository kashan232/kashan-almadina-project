<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Entry Report (Party Wise)</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 6mm 10mm 6mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-tbl td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .company-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0d47a1;
        }
        .report-subtitle {
            font-size: 9pt;
            font-weight: bold;
            color: #333;
        }
        .date-range {
            font-size: 8pt;
            font-weight: bold;
            text-align: right;
            color: #222;
        }
        .date-range span { text-decoration: underline; }
        .gen-date {
            font-size: 6.5pt;
            color: #555;
            text-align: right;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.data-table thead {
            display: table-header-group;
        }
        table.data-table th {
            background-color: #cfd8dc;
            border: 0.5pt solid #444;
            padding: 3.5pt 2pt;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
        }
        table.data-table td {
            border: 0.5pt solid #888;
            padding: 2.5pt 3pt;
            vertical-align: middle;
            font-size: 7pt;
        }
        .party-heading-row td {
            background-color: #e8f5e9 !important;
            border: 0.5pt solid #333 !important;
            padding: 3.5pt 4pt;
            font-weight: bold;
            font-size: 8pt;
            color: #1b5e20;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f5f5f5;
            border: 0.5pt solid #444;
            padding: 3pt;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 7.5pt;
            background-color: #cfd8dc;
            border-top: 1.5pt solid #000;
            border-bottom: 1.5pt solid #000;
            padding: 4pt 3pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>

    <table class="header-tbl">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">AL-MADINA TRADERS</div>
                <div class="report-subtitle">Claim Entry Report (Party Wise)</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="date-range">
                    From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-Y') : '' }}</span>
                    &nbsp;&nbsp;To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-Y') : '' }}</span>
                </div>
                <div class="gen-date">Printed On: {{ now()->format('d-M-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">S#</th>
                <th style="width: 6%;">CLM #</th>
                <th style="width: 7%;">Date</th>
                <th style="width: 6%;">Item ID</th>
                <th style="width: 21%;">Item Description</th>
                <th style="width: 10%;">Claim Type</th>
                <th style="width: 14%;">Fault Found</th>
                <th style="width: 8%;">Mfg Date</th>
                <th style="width: 8%;">Card No.</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 11%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_qty = 0;
                $grand_amount = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="11" class="text-center" style="padding: 25pt;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $partyKey => $items)
                @php
                    $first = $items->first();
                    $partyName = strtoupper($first->party_name);
                    $party_qty = $items->count();
                    $party_total = 0;
                    $sn = 0;
                @endphp

                <tr class="party-heading-row">
                    <td colspan="11" class="text-left">{{ $partyName }}</td>
                </tr>

                @foreach($items as $claim)
                    @php
                        $sn++;
                        $amount = (float) $claim->report_amount;
                        $party_total += $amount;
                        $displayId = preg_replace('/[^0-9]/', '', $claim->claim_no) ?: $claim->id;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $sn }}</td>
                        <td class="text-center">{{ $displayId }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-m-y') }}</td>
                        <td class="text-center">{{ $claim->product_id }}</td>
                        <td class="text-left">{{ $claim->product->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $claim->claim_type_label }}</td>
                        <td class="text-left">{{ $claim->fault_found ?: '-' }}</td>
                        <td class="text-center">{{ $claim->mfg_date ?: '-' }}</td>
                        <td class="text-center">{{ $claim->card_no ?: '-' }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right">{{ number_format($amount, 2) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="9" class="text-right">{{ $partyName }} Total:</td>
                    <td class="text-center">{{ number_format($party_qty) }}</td>
                    <td class="text-right">{{ number_format($party_total, 2) }}</td>
                </tr>

                @php 
                    $grand_qty += $party_qty;
                    $grand_amount += $party_total; 
                @endphp
            @endforeach

            @if($grouped->isNotEmpty())
            <tr class="grand-total-row">
                <td colspan="9" class="text-right">Grand Total:</td>
                <td class="text-center">{{ number_format($grand_qty) }}</td>
                <td class="text-right">{{ number_format($grand_amount, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
