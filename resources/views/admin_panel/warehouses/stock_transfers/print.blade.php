<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Transfer Order #{{ $transfer->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background: #525659;
            color: #000;
        }

        /* 🎛️ Floating Action Toolbar (Screen only) */
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
        .btn-thermal { background: #198754; color: #fff; }
        .btn-thermal:hover { background: #157347; }
        .btn-close-tb { background: #6c757d; color: #fff; }
        .btn-close-tb:hover { background: #5c636a; }

        /* 📄 A5 Portrait Paper Sheet Container */
        .a5-container {
            width: 148mm;
            min-height: 210mm;
            margin: 15px auto;
            background: #fff;
            padding: 8mm 7mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35);
            box-sizing: border-box;
            position: relative;
        }

        /* Header Layout */
        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .company-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.1;
            color: #000;
            letter-spacing: -0.3px;
        }
        .company-sub1 {
            font-size: 10.5px;
            font-style: italic;
            font-weight: 600;
            color: #000;
            margin-top: 3px;
            line-height: 1.35;
        }
        .company-sub2 {
            font-size: 10.5px;
            font-style: italic;
            font-weight: 600;
            color: #000;
            line-height: 1.35;
        }
        .header-right {
            text-align: right;
            flex-shrink: 0;
        }
        .logo-img {
            max-width: 115px;
            height: auto;
            display: block;
            margin-left: auto;
        }
        .order-badge-box {
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 3px 12px;
            margin-top: 4px;
            font-weight: 700;
            font-size: 12px;
            text-align: center;
            color: #000;
            display: inline-block;
            min-width: 110px;
        }

        .thick-divider {
            border-top: 2px solid #000;
            margin: 6px 0 8px 0;
        }

        /* Meta details */
        .meta-details {
            font-size: 11.5px;
            line-height: 1.55;
            color: #000;
            margin-bottom: 8px;
        }
        .meta-row-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .meta-label {
            font-weight: 700;
            color: #000;
        }
        .meta-val {
            color: #000;
        }
        .col-label {
            display: inline-block;
            width: 78px;
            font-weight: 700;
        }

        /* Table */
        .transfer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            color: #000;
        }
        .transfer-table th {
            background-color: #d8d8d8 !important;
            border: 1px solid #777 !important;
            padding: 4px 6px;
            font-weight: 700;
            font-size: 11.5px;
            color: #000;
        }
        .transfer-table td {
            border: 1px solid #888 !important;
            padding: 3px 6px;
            font-size: 11px;
            color: #000;
        }
        .grand-total-row td {
            border-top: 1.5px solid #000 !important;
            border-bottom: 1px solid #888 !important;
            background-color: #fff !important;
            padding: 4px 6px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .ton-row td {
            background-color: #85cde2 !important;
            color: #000 !important;
            border: 1px solid #62b1c8 !important;
            padding: 4px 6px;
            font-size: 11.5px;
            font-weight: 700;
        }

        /* Footer & Signatures */
        .footer-signatures {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            padding: 0 15px;
        }
        .sig-box {
            border-top: 1px solid #000;
            width: 105px;
            text-align: center;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #000;
        }
        .page-num {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #555;
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 6mm 5mm;
            }
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .a5-container {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Screen Toolbar --}}
    <div class="no-print-toolbar">
        <div class="toolbar-info">
            <i class="fa fa-file-text-o me-1"></i> Stock Transfer #{{ $transfer->id }} &bull; A5 Portrait Print
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print A5
            </button>
            <a href="{{ route('stock_transfers.print_thermal', $transfer->id) }}" class="btn-toolbar btn-thermal">
                <i class="fa fa-ticket"></i> Thermal Receipt
            </a>
            <button class="btn-toolbar btn-close-tb" onclick="window.close()">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

    @php
        $destName = $transfer->to_shop ? 'Main Shop' : ($transfer->toWarehouse->warehouse_name ?? '—');
        $destAddress = '';
        $destContact = '';

        if ($transfer->to_shop) {
            $destAddress = 'Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.';
            $destContact = '0312-0252899, 022-2780942';
        } else {
            $whName = strtolower($transfer->toWarehouse->warehouse_name ?? '');
            if (str_contains($whName, 'khi') || str_contains($whName, 'karachi')) {
                $destName = 'AL MADINA TRADERS KHI';
                $destAddress = 'Shop # 21, Hyder-e-Karrar Imam Bargah, Pone 5, Orangi Town, Karachi';
                $destContact = '0314-1110014, 0335-3390680';
            } else {
                $destAddress = $transfer->toWarehouse->location ?? '';
                $destContact = $transfer->toWarehouse->remarks ?? '';
            }
        }
    @endphp

    {{-- Sheet Container --}}
    <div class="a5-container">
        <!-- Header -->
        <div class="header-wrap">
            <div>
                <div class="company-title">Al-Madina Traders</div>
                <div class="company-sub1">Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.</div>
                <div class="company-sub2">Mob / Whatsapp: 0312-0252899, Tel: 022-2780942</div>
            </div>
            <div class="header-right">
                <img src="{{ asset('amt-logo.png') }}" alt="AMT" class="logo-img">
                <div class="order-badge-box">Transfer Order</div>
            </div>
        </div>

        <div class="thick-divider"></div>

        <!-- Meta Information -->
        <div class="meta-details">
            <div class="meta-row-flex">
                <div>
                    <span class="col-label">Challan # :</span>
                    <span class="meta-val" style="font-weight: 700; font-size: 13px;">{{ $transfer->id }}</span>
                </div>
                <div>
                    <span class="meta-label">Date :</span>
                    <span class="meta-val" style="font-weight: 700; margin-left: 6px;">{{ \Carbon\Carbon::parse($transfer->entry_date ?? $transfer->created_at)->format('l, F d, Y') }}</span>
                </div>
            </div>
            <div>
                <span class="col-label">Issue To:</span>
                <span class="meta-val" style="font-weight: 700;">{{ $destName }}</span>
            </div>
            <div>
                <span class="col-label">Address:</span>
                <span class="meta-val">{{ $destAddress ?: '—' }}</span>
            </div>
            <div>
                <span class="col-label">Contact #:</span>
                <span class="meta-val">{{ $destContact ?: '—' }}</span>
            </div>
        </div>

        <!-- Table -->
        <table class="transfer-table">
            <thead>
                <tr>
                    <th style="width: 36px; text-align: center;">S #</th>
                    <th style="width: 60px; text-align: center;">Item ID</th>
                    <th style="text-align: left;">Description</th>
                    <th style="width: 75px; text-align: center;">Quantity</th>
                    <th style="width: 95px; text-align: center;">Weight (Kgs)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQty = 0;
                    $totalWeight = 0;
                @endphp
                @foreach($transfer->items as $idx => $item)
                    @php
                        $unitW = (float) preg_replace('/[^0-9.]/', '', $item->product->weight ?? '0');
                        $rowW = ($item->weight && (float)$item->weight > 0) ? (float)$item->weight : ((float)$item->quantity * $unitW);
                        $totalQty += (float)$item->quantity;
                        $totalWeight += (float)$rowW;
                        $qDisplay = (float)$item->quantity == (int)$item->quantity ? number_format($item->quantity, 0) : number_format($item->quantity, 2);
                        $wDisplay = (float)$rowW == (int)$rowW ? number_format($rowW, 0) : number_format($rowW, 2);
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="text-align: center;">{{ $item->product_id }}</td>
                        <td style="text-align: left;">{{ $item->product->name ?? '—' }}</td>
                        <td style="text-align: center;">{{ $qDisplay }}</td>
                        <td style="text-align: center;">{{ $wDisplay }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total-row">
                    <td colspan="3" style="text-align: right;">Grand Total:</td>
                    <td style="text-align: center;">{{ (float)$totalQty == (int)$totalQty ? number_format($totalQty, 0) : number_format($totalQty, 2) }}</td>
                    <td style="text-align: center;">{{ number_format($totalWeight, 2) }}</td>
                </tr>
                <tr class="ton-row">
                    <td colspan="4" style="text-align: right;">Total Weight in Ton(s):</td>
                    <td style="text-align: center;">{{ number_format($totalWeight / 1000, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($transfer->remarks)
            <div style="margin-top: 8px; font-size: 11px;">
                <strong>Remarks:</strong> {{ $transfer->remarks }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="footer-signatures">
            <div class="sig-box">Prepared By</div>
            <div class="sig-box">Checked By</div>
            <div class="sig-box">Authorized By</div>
        </div>

        <div class="page-num">
            Page 1 of 1
        </div>
    </div>

</body>
</html>
