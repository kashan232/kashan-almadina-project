<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Entry Report (Brand Wise)</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 6mm 10mm 6mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8pt;
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
            padding: 4pt 2pt;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
        }
        table.data-table td {
            border: 0.5pt solid #888;
            padding: 3pt 3pt;
            vertical-align: middle;
            font-size: 7.5pt;
        }
        .brand-heading-row td {
            background-color: #ede7f6 !important;
            border: 0.5pt solid #333 !important;
            padding: 4pt;
            font-weight: bold;
            font-size: 8.5pt;
            color: #4a148c;
        }
        .item-total-row td {
            font-weight: bold;
            background-color: #fafafa;
            border: 0.5pt solid #555;
            padding: 3pt;
        }
        .brand-total-row td {
            font-weight: bold;
            background-color: #f0f0f0;
            border-top: 1pt solid #444;
            border-bottom: 1pt solid #444;
            padding: 3.5pt;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 8pt;
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
                <div class="report-subtitle">Claim Entry Report (Brand Wise)</div>
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
                <th style="width: 8%;">CLM #</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 28%;">Item</th>
                <th style="width: 26%;">Customer Name</th>
                <th style="width: 11%;">Mfg Date</th>
                <th style="width: 11%;">Card No.</th>
                <th style="width: 6%;">QTY</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grand_count = 0; 
            @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="7" class="text-center" style="padding: 25pt;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $brandName => $itemGroups)
                @php $brand_count = 0; @endphp

                <tr class="brand-heading-row">
                    <td colspan="7" class="text-left">{{ strtoupper($brandName) }}</td>
                </tr>

                @foreach($itemGroups as $productId => $claims)
                    @php
                        $product = $claims->first()->product;
                        $itemName = $product->name ?? 'N/A';
                        $item_count = $claims->count();
                        $brand_count += $item_count;
                    @endphp

                    @foreach($claims as $claim)
                        @php
                            $displayId = preg_replace('/[^0-9]/', '', $claim->claim_no) ?: $claim->id;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $displayId }}</td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-m-y') }}</td>
                            <td class="text-left">{{ $itemName }}</td>
                            <td class="text-left">{{ $claim->party_name }}</td>
                            <td class="text-center">{{ $claim->mfg_date ?: '-' }}</td>
                            <td class="text-center">{{ $claim->card_no ?: '-' }}</td>
                            <td class="text-center">1</td>
                        </tr>
                    @endforeach

                    <tr class="item-total-row">
                        <td colspan="6" class="text-right">Total. {{ $itemName }}</td>
                        <td class="text-center">{{ number_format($item_count) }}</td>
                    </tr>
                @endforeach

                <tr class="brand-total-row">
                    <td colspan="6" class="text-right">{{ $brandName }} Total:</td>
                    <td class="text-center">{{ number_format($brand_count) }}</td>
                </tr>
                @php
                    $grand_count += $brand_count;
                @endphp
            @endforeach

            @if($grouped->isNotEmpty())
            <tr class="grand-total-row">
                <td colspan="6" class="text-right">Grand Total Qty:</td>
                <td class="text-center">{{ number_format($grand_count) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
