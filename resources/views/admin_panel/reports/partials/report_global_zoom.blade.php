{{-- Report previews render at 100% on screen and in print --}}
<style>
    @media screen {
        html {
            zoom: 1;
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
