<style>
    @media screen {
        html {
            zoom: 1;
        }
        .report-zoom-wrapper, .report-sheet, .ledger-block, body > div:not(.no-print) {
            cursor: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='8'/><line x1='21' y1='21' x2='16.65' y2='16.65'/><line x1='11' y1='8' x2='11' y2='14'/><line x1='8' y1='11' x2='14' y2='11'/></svg>") 11 11, zoom-in !important;
            transition: transform 0.25s ease;
            transform-origin: top left;
        }
        .report-sheet {
            transform-origin: top right !important;
        }
        .is-zoomed-max {
            cursor: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='8'/><line x1='21' y1='21' x2='16.65' y2='16.65'/><line x1='8' y1='11' x2='14' y2='11'/></svg>") 11 11, zoom-out !important;
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    let zoomLevel = 0; // 0 = 1x, 1 = 1.3x, 2 = 1.6x
    document.addEventListener('click', function (e) {
        if (e.target.closest('button, a, input, select, textarea, .no-print')) return;

        let target = document.querySelector('.report-sheet') || document.querySelector('.report-zoom-wrapper') || document.body;
        zoomLevel = (zoomLevel + 1) % 3;

        if (zoomLevel === 1) {
            target.style.transform = 'scale(1.3)';
            target.classList.remove('is-zoomed-max');
        } else if (zoomLevel === 2) {
            target.style.transform = 'scale(1.6)';
            target.classList.add('is-zoomed-max');
        } else {
            target.style.transform = 'none';
            target.classList.remove('is-zoomed-max');
        }
    });
});
</script>
