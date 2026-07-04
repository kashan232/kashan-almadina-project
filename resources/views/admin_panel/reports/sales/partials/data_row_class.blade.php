@php
    $entryType = $item->entry_type ?? 'sale';
    $classes = [];
    if ($entryType === 'sale_return') {
        $classes[] = 'return-row';
    }
    if ($entryType === 'customer_credit_note') {
        $classes[] = 'credit-note-row';
    }
@endphp
{{ implode(' ', $classes) }}
