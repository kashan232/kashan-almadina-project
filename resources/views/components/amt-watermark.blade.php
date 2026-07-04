@props([
    'class' => '',
    'style' => '',
])

<img src="{{ asset('amt-watermark.png') }}" alt="" class="amt-watermark {{ $class }}"
     style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:70%; max-width:420px; opacity:0.07; pointer-events:none; z-index:0; {{ $style }}"
     {{ $attributes }}>
