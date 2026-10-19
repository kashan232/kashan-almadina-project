<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Stock Release Note - {{ $voucher->voucher_no ?: $voucher->display_no }}</title>
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
            background: #e0e0e0;
            color: #000;
        }

        /* 🎛️ Floating Action Toolbar (Screen only) */
        .no-print-toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            display: flex;
            gap: 8px;
        }
        .btn-toolbar {
            padding: 7px 16px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .btn-print { background: #e91e63; color: #fff; }
        .btn-print:hover { background: #d81b60; }
        .btn-close { background: #455a64; color: #fff; }
        .btn-close:hover { background: #37474f; }

        /* 🧾 Thermal Receipt Wrapper (80mm / 3 inch) */
        .receipt-container {
            width: 76mm;
            max-width: 100%;
            margin: 20px auto;
            background: #fff;
            padding: 3mm 4mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
            box-sizing: border-box;
        }

        /* 🏷️ Header Section */
        .company-header-box {
            border: 1.5px solid #000;
            padding: 3px 4px;
            text-align: center;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #000;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            color: #000;
            margin: 0 0 4px 0;
        }
        .thick-divider {
            border-top: 2.5px solid #000;
            margin: 4px 0 6px 0;
        }

        /* 📋 Meta Information */
        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }
        .meta-date {
            text-align: left;
        }
        .meta-sr {
            text-align: right;
        }
        .customer-section {
            font-size: 12px;
            color: #000;
            margin-bottom: 4px;
        }
        .customer-label {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .customer-name {
            font-weight: bold;
            font-size: 13px;
            line-height: 1.25;
        }

        /* 📊 Items Table */
        .thermal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            color: #000;
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .thermal-table thead th {
            background-color: #000 !important;
            color: #fff !important;
            border: 1px solid #000;
            padding: 3px 4px;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.2;
        }
        .th-sn {
            width: 14%;
            text-align: center;
        }
        .th-desc {
            width: 58%;
            text-align: left;
            padding-left: 5px !important;
        }
        .th-qty {
            width: 28%;
            text-align: right;
            padding-right: 5px !important;
        }
        .thermal-table tbody td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.2;
            color: #000;
        }
        .td-sn {
            text-align: center;
        }
        .td-desc {
            text-align: left;
            padding-left: 5px !important;
            word-break: break-word;
        }
        .td-qty {
            text-align: right;
            padding-right: 5px !important;
            white-space: nowrap;
        }

        /* Total Row */
        .total-row td.td-total-label {
            border: none !important;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            padding-right: 10px;
        }
        .total-row td.td-total-val {
            border: 1.5px solid #000 !important;
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            padding: 3px 5px !important;
        }

        /* ✍️ Receiver Signature */
        .signature-section {
            margin-top: 55px;
            margin-bottom: 25px;
            display: inline-block;
            text-align: center;
        }
        .sig-line {
            font-size: 12px;
            letter-spacing: -1px;
            font-weight: normal;
            color: #000;
        }
        .sig-title {
            font-size: 11px;
            color: #000;
            margin-top: 2px;
        }

        /* 🖨️ Print Styles (Thermal Paper Roll) */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 1.5mm 3mm !important;
                width: 76mm !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .receipt-container {
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

    <!-- Screen Toolbar -->
    <div class="no-print-toolbar">
        <button class="btn-toolbar btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        <button class="btn-toolbar btn-close" onclick="window.close()">Close</button>
    </div>

    <!-- Thermal Print Container -->
    <div class="receipt-container">
        <!-- Header Box -->
        <div class="company-header-box">
            AL MADINA TRADERS
        </div>

        <!-- Subtitle -->
        <div class="doc-subtitle">
            Stock Release Note
        </div>

        <div class="thick-divider"></div>

        <!-- Meta Information -->
        <div class="meta-row">
            <div class="meta-date">
                Date : &nbsp;&nbsp; {{ !empty($voucher->date) ? date('d-m-y', strtotime($voucher->date)) : date('d-m-y') }}
            </div>
            <div class="meta-sr">
                S.R # &nbsp;&nbsp; {{ $voucher->voucher_no ?: $voucher->display_no }}
            </div>
        </div>

        <div class="customer-section">
            <div class="customer-label">Customer:</div>
            <div class="customer-name">
                @if($voucher->party_type == 'vendor')
                    {{ $voucher->partyVendor->name ?? '-' }}
                @else
                    {{ $voucher->partyCustomer->customer_name ?? 'Walkin' }}
                @endif
            </div>
        </div>

        <div class="thick-divider"></div>

        <!-- Items Table -->
        <table class="thermal-table">
            <thead>
                <tr>
                    <th class="th-sn">S#</th>
                    <th class="th-desc">Item Description</th>
                    <th class="th-qty">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($voucher->items as $index => $item)
                    @php $totalQty += (float)$item->release_qty; @endphp
                    <tr>
                        <td class="td-sn">{{ $index + 1 }}</td>
                        <td class="td-desc">{{ $item->product->name ?? 'N/A' }}</td>
                        <td class="td-qty">{{ number_format($item->release_qty, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="td-total-label">Total:</td>
                    <td class="td-total-val">{{ number_format($totalQty, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="sig-line">___________________</div>
            <div class="sig-title">Receiver Signature</div>
        </div>
    </div>

</body>
</html>
