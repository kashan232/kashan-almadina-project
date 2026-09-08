@php
    $cleanInv = preg_match('/\d+/', $invoiceNo ?? '', $m) ? ltrim($m[0], '0') : ($invoiceNo ?? '');
    if (empty($cleanInv) || $cleanInv === '') { $cleanInv = '0'; }
    $cleanInv = str_pad($cleanInv, 3, '0', STR_PAD_LEFT);
@endphp
<tr class="party-heading-row">
    <td colspan="{{ $colspan }}" class="text-left">
        Inv.No: <span class="party-name">{{ $cleanInv }}</span>
        &nbsp;&nbsp;&nbsp;
        Date: <span class="party-name">{{ $saleDate }}</span>
    </td>
</tr>
