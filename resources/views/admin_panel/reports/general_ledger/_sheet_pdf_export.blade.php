{{-- Shared 1:1 A4 per-sheet PDF export for paginated General Ledger previews --}}
<style>
    body.pdf-exporting #pagesWrapper {
        width: {{ $sheetW }} !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body.pdf-exporting .page-sheet {
        width: {{ $sheetW }} !important;
        height: {{ $isLandscape ? '209mm' : '296mm' }} !important;
        min-height: {{ $isLandscape ? '209mm' : '296mm' }} !important;
        margin: 0 !important;
        box-shadow: none !important;
    }
</style>
<script>
    async function exportReportToPDF() {
        var sheets = Array.from(document.querySelectorAll("#pagesWrapper .page-sheet"));
        if (!sheets.length) return;

        var btn = document.getElementById("btnExportPDF");
        var origText = btn ? btn.innerText : "";
        if (btn) {
            btn.disabled = true;
            btn.innerText = "Generating PDF...";
        }

        document.body.classList.add("pdf-exporting");

        try {
            var sheetWidthPx = {{ $isLandscape ? 1123 : 794 }};
            var fileName = "General_Ledger_{{ $acCode }}.pdf";
            var opt = {
                margin:      [0, 0, 0, 0],
                filename:    fileName,
                image:       { type: "jpeg", quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: sheetWidthPx,
                    backgroundColor: "#ffffff"
                },
                jsPDF: {
                    unit: "mm",
                    format: "a4",
                    orientation: "{{ $isLandscape ? 'landscape' : 'portrait' }}"
                }
            };

            var pdf = await new Promise(function(resolve, reject) {
                html2pdf().set(opt).from(sheets[0]).toPdf().get("pdf", function(pdfInstance) {
                    resolve(pdfInstance);
                }).catch(reject);
            });

            var pageW = pdf.internal.pageSize.getWidth();
            for (var i = 1; i < sheets.length; i++) {
                var canvas = await new Promise(function(resolve, reject) {
                    html2pdf().set(opt).from(sheets[i]).toCanvas().get("canvas", function(c) {
                        resolve(c);
                    }).catch(reject);
                });
                var imgData = canvas.toDataURL("image/jpeg", 0.98);
                var imgH = (canvas.height * pageW) / canvas.width;
                pdf.addPage();
                pdf.addImage(imgData, "JPEG", 0, 0, pageW, imgH);
            }

            pdf.save(fileName);
        } catch (err) {
            console.error("PDF export failed:", err);
            alert("PDF export failed. Please try Print Report -> Save as PDF.");
        } finally {
            document.body.classList.remove("pdf-exporting");
            if (btn) {
                btn.disabled = false;
                btn.innerText = origText;
            }
        }
    }
</script>
