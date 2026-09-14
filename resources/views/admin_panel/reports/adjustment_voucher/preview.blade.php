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
    $totalCols = 8;
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
            <th width="23%">Narration</th>
            <th width="13%">Debit (Increase)</th>
            <th width="13%">Credit (Decrease)</th>
        </tr>
    </thead>
    <tbody>
        @php $grand_debit = 0; $grand_credit = 0; @endphp
        @if($grouped->isEmpty())
            <tr><td colspan="{{ $totalCols }}" style="text-align:center;padding:50px;">No Data Found</td></tr>
        @endif
        @foreach($grouped as $items)
            @php $groupLabel = $items->first()->group_label; $group_debit = 0; $group_credit = 0; $sno = 0; @endphp
            <tr class="group-heading-row"><td colspan="{{ $totalCols }}" class="text-left">{{ $groupLabel }}</td></tr>
            @foreach($items as $line)
                @php
                    $sno++;
                    $amount = (float)$line->amount;
                    if ($isSourceParty) {
                        // Grouped by Source Party: Party is Debited (Increase), Destination Account is Credited (Decrease)
                        $debit = $amount;
                        $credit = 0;
                    } else {
                        // Grouped by Destination Account: Destination Account is Credited (Decrease), Source Party is Debited
                        $debit = 0;
                        $credit = $amount;
                    }
                    $group_debit += $debit;
                    $group_credit += $credit;
                    $grand_debit += $debit;
                    $grand_credit += $credit;
                    $displayVouc = preg_replace('/[^0-9]/', '', $line->voucher_no) ?: $line->voucher_no;
                @endphp
                <tr class="data-row">
                    <td class="text-center">{{ $sno }}</td>
                    <td class="text-center">{{ $displayVouc }}</td>
                    <td class="text-center">{{ $line->voucher_date ? \Carbon\Carbon::parse($line->voucher_date)->format('d-m-y') : '-' }}</td>
                    <td class="text-center">{{ $line->reference_no ?: '-' }}</td>
                    <td class="text-left">{{ $isSourceParty ? $line->account_name : $line->party_name }}</td>
                    <td class="text-left">{{ $line->narration }}</td>
                    <td class="amount-cell">{{ $debit ? number_format($debit, 0) : '-' }}</td>
                    <td class="amount-cell">{{ $credit ? number_format($credit, 0) : '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" class="total-label">{{ $groupLabel }} Total:</td>
                <td class="amount-box">{{ number_format($group_debit, 0) }}</td>
                <td class="amount-box">{{ number_format($group_credit, 0) }}</td>
            </tr>
        @endforeach
        @if(!$grouped->isEmpty())
            <tr class="grand-total-row">
                <td colspan="6" class="grand-label">Grand Total:</td>
                <td class="amount-box">{{ number_format($grand_debit, 0) }}</td>
                <td class="amount-box">{{ number_format($grand_credit, 0) }}</td>
            </tr>
        @endif
    </tbody>
</table>
@include('admin_panel.reports.partials.voucher_preview_footer')
</body>
</html>
