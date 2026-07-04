<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journal Voucher Report</title>
    @include('admin_panel.reports.partials.voucher_preview_styles')
</head>
<body>
@php $totalCols = 8; @endphp
@include('admin_panel.reports.partials.voucher_preview_header', [
    'reportTitle' => 'Journal Voucher Report',
    'showReceiptDates' => false,
])
<table>
    <thead>
        <tr>
            <th width="5%">S.No.</th>
            <th width="8%">Vouc. ID</th>
            <th width="10%">Entry Date</th>
            <th width="10%">Reference No.</th>
            <th width="18%">Party Name</th>
            <th width="24%">Narration</th>
            <th width="12%">Debit</th>
            <th width="12%">Credit</th>
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
                    $group_debit += (float)$line->debit;
                    $group_credit += (float)$line->credit;
                    $grand_debit += (float)$line->debit;
                    $grand_credit += (float)$line->credit;
                    $displayVouc = preg_replace('/[^0-9]/', '', $line->voucher_no) ?: $line->voucher_no;
                @endphp
                <tr class="data-row">
                    <td class="text-center">{{ $sno }}</td>
                    <td class="text-center">{{ $displayVouc }}</td>
                    <td class="text-center">{{ $line->voucher_date ? \Carbon\Carbon::parse($line->voucher_date)->format('d-m-y') : '-' }}</td>
                    <td class="text-center">{{ $line->reference_no ?: '-' }}</td>
                    <td class="text-left">{{ $line->party_name }}</td>
                    <td class="text-left">{{ $line->narration }}</td>
                    <td class="amount-cell">{{ $line->debit ? number_format($line->debit, 0) : '-' }}</td>
                    <td class="amount-cell">{{ $line->credit ? number_format($line->credit, 0) : '-' }}</td>
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
