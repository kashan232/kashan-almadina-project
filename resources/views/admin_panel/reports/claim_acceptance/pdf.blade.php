<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Acceptance Report</title>
    <style>
        @page {
            size: a4 landscape;
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
        .total-row td {
            font-weight: bold;
            background-color: #f5f5f5;
            border: 0.5pt solid #444;
            padding: 3pt;
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
                <div class="report-subtitle">Claim Acceptance Report</div>
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
                <th style="width: 9%;">Voucher</th>
                <th style="width: 14%;">Claim From</th>
                <th style="width: 14%;">Accept In</th>
                <th style="width: 9%;">BTR #</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 20%;">Supplier</th>
                <th style="width: 18%;">Item</th>
                <th style="width: 7%;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @php $grand_qty = 0; @endphp

            @if($grouped->isEmpty())
                <tr>
                    <td colspan="8" class="text-center" style="padding: 25pt;">No Data Found</td>
                </tr>
            @endif

            @foreach($grouped as $acceptanceId => $items)
                @php
                    $voucher = $items->first()->voucher;
                    $displayVoucher = preg_replace('/[^0-9]/', '', $voucher->voucher_no) ?: $voucher->voucher_no;
                    $claimFrom = $voucher->fromWarehouse->warehouse_name ?? 'N/A';
                    $acceptIn = $voucher->toWarehouse->warehouse_name ?? 'N/A';
                    $partyName = $voucher->partyName();
                    $voucher_qty = 0;
                @endphp

                @foreach($items as $item)
                    @php
                        $qty = (float) $item->quantity;
                        $voucher_qty += $qty;
                        $grand_qty += $qty;
                        $itemDate = \Carbon\Carbon::parse($voucher->date)->format('d-m-y');
                    @endphp
                    <tr>
                        <td class="text-center" style="font-weight:bold;">{{ $displayVoucher }}</td>
                        <td class="text-left">{{ $claimFrom }}</td>
                        <td class="text-left">{{ $acceptIn }}</td>
                        <td class="text-center">{{ $item->btr_no ?: '-' }}</td>
                        <td class="text-center">{{ $itemDate }}</td>
                        <td class="text-left">{{ $partyName }}</td>
                        <td class="text-left">{{ $item->product->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($qty, 2) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="7" class="text-right">Total:</td>
                    <td class="text-right">{{ number_format($voucher_qty, 2) }}</td>
                </tr>
            @endforeach

            @if(!$grouped->isEmpty())
                <tr class="grand-total-row">
                    <td colspan="7" class="text-right">Grand Total:</td>
                    <td class="text-right">{{ number_format($grand_qty, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
