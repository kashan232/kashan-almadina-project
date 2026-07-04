<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjustment Voucher Report</title>
    @include('admin_panel.reports.partials.voucher_preview_styles')
</head>
<body>
@php
    $isSourceParty = ($report_type ?? 'source_party') === 'source_party';
    $extraColLabel = $isSourceParty ? 'Destination Account' : 'Source Party Name';
    $totalCols = 7;
@endphp
@include('admin_panel.reports.partials.voucher_preview_header', [
    'reportTitle' => 'Adjustment Voucher Report',
    'showReceiptDates' => false,
])
<table>
    <thead>
        <tr>
            <th width="5%">S.No.</th>
            <th width="8%">Vouc. ID</th>
            <th width="10%">Entry Date</th>
            <th width="10%">Reference No.</th>
            <th width="18%">{{ $extraColLabel }}</th>
            <th width="24%">Narration</th>
            <th width="15%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php $grand_amount = 0; @endphp
        @if($grouped->isEmpty())
            <tr><td colspan="{{ $totalCols }}" style="text-align:center;padding:50px;">No Data Found</td></tr>
        @endif
        @foreach($grouped as $items)
            @php $groupLabel = $items->first()->group_label; $group_amount = 0; $sno = 0; @endphp
            <tr class="group-heading-row"><td colspan="{{ $totalCols }}" class="text-left">{{ $groupLabel }}</td></tr>
            @foreach($items as $line)
                @php
                    $sno++; $group_amount += (float)$line->amount; $grand_amount += (float)$line->amount;
                    $displayVouc = preg_replace('/[^0-9]/', '', $line->voucher_no) ?: $line->voucher_no;
                @endphp
                <tr class="data-row">
                    <td class="text-center">{{ $sno }}</td>
                    <td class="text-center">{{ $displayVouc }}</td>
                    <td class="text-center">{{ $line->voucher_date ? \Carbon\Carbon::parse($line->voucher_date)->format('d-m-y') : '-' }}</td>
                    <td class="text-center">{{ $line->reference_no ?: '-' }}</td>
                    <td class="text-left">{{ $isSourceParty ? $line->account_name : $line->party_name }}</td>
                    <td class="text-left">{{ $line->narration }}</td>
                    <td class="amount-cell">{{ number_format($line->amount, 0) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" class="total-label">{{ $groupLabel }} Total:</td>
                <td class="amount-box">{{ number_format($group_amount, 0) }}</td>
            </tr>
        @endforeach
        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="6" class="grand-label">Grand Total:</td>
                <td class="amount-box">{{ number_format($grand_amount, 0) }}</td>
            </tr>
        @endif
    </tbody>
</table>
@include('admin_panel.reports.partials.voucher_preview_footer')
</body>
</html>
