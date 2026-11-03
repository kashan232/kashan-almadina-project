<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Acceptance - {{ $voucher->voucher_no ?: ('#' . $voucher->id) }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        @page {
            size: a5;
            margin: 4mm 5mm 3mm 5mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000;
            background: #525659;
            margin: 0;
            padding: 0;
        }

        /* 🎛️ Floating Action Toolbar */
        .no-print-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: #212529;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .toolbar-info {
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }
        .toolbar-actions {
            display: flex;
            gap: 8px;
        }
        .btn-toolbar {
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print { background: #0d6efd; color: #fff; }
        .btn-print:hover { background: #0b5ed7; }
        .btn-pdf { background: #c2185b; color: #fff; }
        .btn-pdf:hover { background: #ad1457; }
        .btn-close-tb { background: #6c757d; color: #fff; }
        .btn-close-tb:hover { background: #5c636a; }

        /* 📄 A5 Sheet Container */
        .a5-sheet {
            width: 148mm;
            min-height: 210mm;
            margin: 15px auto;
            background: #fff;
            padding: 5mm 6mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Header Layout */
        .header-top {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 20pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #000;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8pt;
            color: #222;
            margin-top: 1px;
        }
        .company-phone {
            font-size: 8pt;
            font-weight: 600;
            color: #000;
            margin-top: 1px;
        }

        .voucher-title-badge {
            display: inline-block;
            background: #000;
            color: #fff;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 12px;
            border-radius: 3px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* Meta Box */
        .meta-box {
            display: flex;
            justify-content: space-between;
            border: 1px solid #333;
            border-radius: 3px;
            padding: 5px 8px;
            margin-bottom: 8px;
            background: #fafafa;
            font-size: 8pt;
            line-height: 1.4;
        }
        .meta-left, .meta-right {
            width: 48%;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
        }
        .meta-lbl {
            font-weight: 600;
            color: #444;
        }
        .meta-val {
            font-weight: 700;
            color: #000;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .items-table th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 3.5px 5px;
            font-weight: 700;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #777;
            padding: 3px 5px;
            vertical-align: middle;
        }
        .items-table tfoot td {
            border-top: 2px solid #000;
            font-weight: 700;
            background: #f5f5f5;
        }

        /* Remarks Box */
        .remarks-box {
            border: 1px solid #999;
            border-radius: 3px;
            padding: 4px 6px;
            margin-bottom: 12px;
            font-size: 7.5pt;
            background: #fafafa;
        }
        .remarks-lbl {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        /* Signatures Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 25px;
            padding-bottom: 5px;
        }
        .sig-block {
            text-align: center;
            width: 40%;
        }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 8pt;
            font-weight: 600;
        }

        .footer-note {
            text-align: center;
            font-size: 7pt;
            color: #666;
            margin-top: 6px;
            border-top: 1px dashed #ccc;
            padding-top: 3px;
        }

        @media print {
            @page {
                size: a5;
                margin: 4mm 5mm 3mm 5mm;
            }
            html, body {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .a5-sheet {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar -->
    <div class="no-print-toolbar">
        <div class="toolbar-info">
            <i class="fa fa-clipboard me-1"></i> Claim Acceptance &bull; {{ $voucher->voucher_no ?: ('#' . $voucher->id) }}
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="triggerClaimPrint()">
                <i class="fa fa-print"></i> Print Voucher
            </button>
            <button class="btn-toolbar btn-pdf" id="btnExportPDF" onclick="exportClaimToPDF()">
                <i class="fa fa-file-pdf-o"></i> Export to PDF
            </button>
            <button class="btn-toolbar btn-close-tb" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

    <!-- Printable A5 Sheet -->
    <div class="a5-sheet" id="claimSheet">
        <div>
            <!-- Header -->
            <div class="header-top">
                <div class="company-name">Al-Madina Traders</div>
                <div class="company-sub">Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.</div>
                <div class="company-phone">Mob/Whatsapp: 0312-0252899, Tel: 022-2780942</div>
                <div class="voucher-title-badge">Claim Acceptance Voucher</div>
            </div>

            <!-- Meta Box -->
            <div class="meta-box">
                <div class="meta-left">
                    <div class="meta-row">
                        <span class="meta-lbl">Voucher #:</span>
                        <span class="meta-val">{{ $voucher->voucher_no ?: ('#' . $voucher->id) }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-lbl">Date:</span>
                        <span class="meta-val">{{ \Carbon\Carbon::parse($voucher->date)->format('d-M-Y') }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-lbl">Status:</span>
                        <span class="meta-val">{{ strtoupper($voucher->status ?? 'POSTED') }}</span>
                    </div>
                </div>
                <div class="meta-right">
                    <div class="meta-row">
                        <span class="meta-lbl">Party Type:</span>
                        <span class="meta-val">{{ ucfirst($voucher->party_type ?? 'Vendor') }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-lbl">Party Name:</span>
                        <span class="meta-val">
                            @if($voucher->party_type == 'vendor')
                                {{ $voucher->vendor->name ?? 'N/A' }}
                            @else
                                {{ $voucher->customer->customer_name ?? 'N/A' }}
                            @endif
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-lbl">Contact #:</span>
                        <span class="meta-val">
                            @if($voucher->party_type == 'vendor')
                                {{ $voucher->vendor->phone ?? '-' }}
                            @else
                                {{ $voucher->customer->customer_phone ?? '-' }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">S#</th>
                        <th style="width: 26%;">BTR #</th>
                        <th style="width: 50%; text-align: left; padding-left: 6px;">Product Description</th>
                        <th style="width: 16%; text-align: center;">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($voucher->items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ $item->btr_no ?: '-' }}</td>
                        <td style="text-align: left; padding-left: 6px;">{{ $item->product->name ?? ('Product #' . $item->product_id) }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ number_format((float)$item->quantity, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding-right: 8px;">Total Quantity: &gt;&gt;</td>
                        <td style="text-align: center; font-weight: 800;">{{ number_format((float)$voucher->items->sum('quantity'), 0) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Remarks -->
            @if($voucher->remarks)
            <div class="remarks-box">
                <div class="remarks-lbl">Remarks:</div>
                <div>{{ $voucher->remarks }}</div>
            </div>
            @endif
        </div>

        <div>
            <!-- Signatures -->
            <div class="signature-section">
                <div class="sig-block">
                    <div class="sig-line">Accepted By: {{ $voucher->creator->name ?? 'Admin' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-line">Authorized Signature</div>
                </div>
            </div>

            <div class="footer-note">
                System Generated Claim Acceptance Voucher &bull; Printed at: {{ date('d-M-Y h:i A') }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>
<script src="{{ asset('js/qz-print-service.js') }}"></script>
<script>
function triggerClaimPrint() {
    printWithQzOrBrowser('claim_acceptance', '#claimSheet', function() { window.print(); });
}
</script>
<!-- html2pdf for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportClaimToPDF() {
            var element = document.getElementById("claimSheet");
            if (!element) return;
            var opt = {
                margin:       [4, 5, 4, 5],
                filename:     "Claim_Acceptance_{{ $voucher->voucher_no ?: $voucher->id }}.pdf",
                image:        { type: "jpeg", quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: "mm", format: "a5", orientation: "portrait" }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
