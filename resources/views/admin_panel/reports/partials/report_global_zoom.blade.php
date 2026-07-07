{{-- Global 150% screen zoom for all report previews (print stays 100%) --}}
<style>
    @media screen {
        html {
            zoom: 1.5;
        }
        @supports not (zoom: 1.5) {
            body {
                transform: scale(1.5);
                transform-origin: top left;
                width: 66.6667%;
            }
        }
    }
    @media print {
        html {
            zoom: 1 !important;
        }
        body {
            transform: none !important;
            width: auto !important;
        }
    }
</style>
