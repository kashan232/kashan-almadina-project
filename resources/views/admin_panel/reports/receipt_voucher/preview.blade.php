<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Receipt Voucher Report</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background-color: #fff;
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
            color: #8e24aa;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }
        .date-range { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .group-heading-row td {
            background-color: #fff;
            border: none;
            border-top: 2px solid #000;
            padding: 10px 6px 4px 6px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .data-row td { border: 1px solid #999; }
        .amount-cell { font-weight: bold; text-align: right; }
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .total-label { text-align: right; color: #0d47a1; text-transform: uppercase; }
        .amount-box {
            background-color: #cfd8dc;
            text-align: right;
            font-weight: bold;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border-top: 2px solid #000;
        }
        .grand-label { text-align: right; color: #0d47a1; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

@php
    $isPartyWise = ($report_type ?? 'source_party') === 'source_party';
    $isHeadWise = in_array($report_type ?? '', ['sub_head', 'main_head'], true);
    $extraColLabel = $isPartyWise ? 'Main Head' : 'Source Party Name';
    $totalCols = 8;
@endphp

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
</div>

<div style="text-align:center;margin-bottom:6px;"><x-amt-logo width="110px" style="margin:0 auto;" /></div>

<div class="company-name">Al-Madina Traders</div>

<div class="report-header">
    <div class="generated-date">{{ now()->format('l, F d, Y') }}</div>
    <h1 class="report-title">Receipt Voucher Report</h1>
    <div class="date-range">
        Receipt From: <span>{{ $receipt_from ? \Carbon\Carbon::parse($receipt_from)->format('d-m-y') : '-' }}</span>
        To: <span>{{ $receipt_to ? \Carbon\Carbon::parse($receipt_to)->format('d-m-y') : '-' }}</span>
    </div>
    <div class="date-range">
        Entry From: <span>{{ $entry_from ? \Carbon\Carbon::parse($entry_from)->format('d-m-y') : '-' }}</span>
        To: <span>{{ $entry_to ? \Carbon\Carbon::parse($entry_to)->format('d-m-y') : '-' }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="5%">S.No.</th>
            <th width="8%">Vouc. ID</th>
            <th width="9%">Receipt Date</th>
            <th width="10%">Reference No.</th>
            <th width="16%">{{ $extraColLabel }}</th>
            <th width="22%">Narration</th>
            <th width="18%">Bank Details</th>
            <th width="12%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php $grand_amount = 0; @endphp

        @if($grouped->isEmpty())
            <tr>
                <td colspan="{{ $totalCols }}" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $groupKey => $items)
            @php
                $groupLabel = $items->first()->group_label;
                $group_amount = 0;
                $sno = 0;
            @endphp

            <tr class="group-heading-row">
                <td colspan="{{ $totalCols }}" class="text-left">{{ $groupLabel }}</td>
            </tr>

            @foreach($items as $line)
                @php
                    $sno++;
                    $group_amount += (float) $line->amount;
                    $grand_amount += (float) $line->amount;
                    $displayVouc = preg_replace('/[^0-9]/', '', $line->rvid) ?: $line->rvid;
                @endphp
                <tr class="data-row">
                    <td class="text-center">{{ $sno }}</td>
                    <td class="text-center">{{ $displayVouc }}</td>
                    <td class="text-center">{{ $line->receipt_date ? \Carbon\Carbon::parse($line->receipt_date)->format('d-m-y') : '-' }}</td>
                    <td class="text-center">{{ $line->reference_no ?: '-' }}</td>
                    <td class="text-left">{{ $isPartyWise ? $line->sub_head_name : $line->party_name }}</td>
                    <td class="text-left">{{ $line->narration }}</td>
                    <td class="text-left">{{ $line->bank_details }}</td>
                    <td class="amount-cell">{{ number_format($line->amount, 0) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="7" class="total-label">{{ $groupLabel }} Total:</td>
                <td class="amount-box">{{ number_format($group_amount, 0) }}</td>
            </tr>
        @endforeach

        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="7" class="grand-label">Grand Total:</td>
                <td class="amount-box">{{ number_format($grand_amount, 0) }}</td>
            </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    <div>{{ now()->format('l, F d, Y') }}</div>
    <div>Generated by ERP System</div>
</div>

</body>
</html>
