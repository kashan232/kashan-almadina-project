@php
    $entryType = $item->entry_type ?? 'sale';
    $showPlus = $entryType === 'customer_credit_note' && ($value ?? 0) > 0;
@endphp
<span class="{{ $showPlus ? 'credit-plus' : '' }}">{{ $showPlus ? '+' : '' }}{{ number_format($value ?? 0, $decimals ?? 0) }}</span>
