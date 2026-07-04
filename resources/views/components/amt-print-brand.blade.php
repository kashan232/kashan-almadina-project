@props([
    'docSubtitle' => null,
    'docTitle' => null,
    'logoWidth' => '110px',
    'showContact' => true,
])

<div class="amt-print-brand" style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
    <div>
        <div style="font-family:'Times New Roman', Times, serif; font-size:20px; font-weight:700; line-height:1.1;">Al-Madina Traders</div>
        @if($showContact)
            <div style="font-size:10px; font-style:italic; color:#444; line-height:1.35; margin-top:2px;">Shop#2, United Hotel, Qazi Qayoom Road, Hyderabad.</div>
            <div style="font-size:10px; color:#444;">Mob / Whatsapp: 0312-0252899 , Tel: 022-2780942</div>
        @endif
        @if($docSubtitle)
            <div style="margin-top:4px; font-size:12px; font-weight:600; color:#333;">{{ $docSubtitle }}</div>
        @endif
    </div>
    <div style="text-align:right; flex-shrink:0;">
        <x-amt-logo :width="$logoWidth" style="display:block; margin-left:auto;" />
        @if($docTitle)
            <div style="border:1.5px solid #000; padding:4px 10px; margin-top:4px; font-weight:700; font-size:11px; text-align:center; min-width:90px;">{{ $docTitle }}</div>
        @endif
    </div>
</div>
