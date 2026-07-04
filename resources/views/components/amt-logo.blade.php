@props([
    'width' => null,
    'height' => null,
    'class' => '',
    'alt' => 'Al Madina Traders',
])

@php
    $styles = ['height:auto'];
    if ($width) {
        $styles[] = 'max-width:' . $width;
    }
    if ($height) {
        $styles[] = 'max-height:' . $height;
    }
@endphp

<img src="{{ asset('amt-logo.png') }}" alt="{{ $alt }}" class="{{ $class }}" style="{{ implode(';', $styles) }}" {{ $attributes }}>
