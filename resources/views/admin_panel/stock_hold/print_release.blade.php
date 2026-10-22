<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Release - Delivery Challan #{{ $voucher->voucher_no ?: $voucher->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #4a4d50;
            color: #000;
            margin: 0;
            padding: 0;
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
        .btn-close-tb { background: #6c757d; color: #fff; }
        .btn-close-tb:hover { background: #5c636a; }

        /* 🧾 Thermal Receipt Wrapper (80mm) */
        .dc-container {
            width: 76mm;
            max-width: 100%;
            margin: 15px auto;
            background: #fff;
            padding: 4mm 4.5mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35);
            box-sizing: border-box;
            font-size: 11px;
            line-height: 1.35;
        }

        /* Header */
        .header {
            text-align: center;
        }
        .shop-name {
            font-size: 17px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            margin-bottom: 2px;
        }
        .shop-address {
            font-size: 9.5px;
            font-style: italic;
            font-weight: 600;
            color: #000;
            line-height: 1.3;
        }
        .shop-phone {
            font-size: 10px;
            font-weight: 700;
            color: #000;
            margin-top: 1px;
        }
        .thick-divider {
            border-top: 2.5px solid #000;
            margin: 4px 0 6px 0;
        }

        /* Title */
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
            color: #000;
        }
        .sub-title {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 6px;
            color: #000;
        }

        /* Meta details */
        .meta-section {
            margin-bottom: 6px;
            font-size: 11.5px;
            line-height: 1.45;
            color: #000;
        }
        .meta-section strong {
            font-weight: 700;
        }

        /* Customer Box */
        .customer-box {
            border: 1.5px solid #000;
            padding: 4px 6px;
            margin-bottom: 7px;
            box-sizing: border-box;
        }
        .cust-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cust-table tr td {
            padding: 1.5px 0;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
        }
        .cust-lbl {
            width: 78px;
            font-weight: 700;
            color: #000;
            white-space: nowrap;
        }
        .cust-val {
            color: #000;
            font-weight: 700;
            text-align: left;
            word-break: break-word;
        }
        .urdu-val {
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', Arial, sans-serif;
            font-size: 11.5px;
            line-height: 1.45;
        }

        /* Table */
        .dc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 10px;
            color: #000;
        }
        .dc-table thead th {
            background: #000 !important;
            color: #fff !important;
            font-weight: 700;
            padding: 3.5px 4px;
            border: 1px solid #000;
            font-size: 10.5px;
            line-height: 1.2;
        }
        .dc-table tbody td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-weight: 700;
            color: #000;
            font-size: 10px;
        }
        .td-desc {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .brand-name {
            display: inline-block;
            width: 50px;
            font-weight: 700;
            color: #000;
        }
        .product-name {
            font-weight: 700;
            color: #000;
        }
        .td-qty {
            text-align: center;
        }
        .td-loc {
            text-align: center;
        }

        /* Total Row (Enclosed Box) */
        .total-row td {
            border-top: 1.5px solid #000 !important;
            border-bottom: 1.5px solid #000 !important;
            font-weight: 700;
            font-size: 10.5px;
            padding: 3.5px 4px;
        }
        .total-row td.total-label {
            border-left: 1.5px solid #000 !important;
            border-right: none !important;
        }
        .total-row td.total-val {
            border-left: none !important;
            border-right: none !important;
            text-align: center;
        }
        .total-row td.total-empty {
            border-right: 1.5px solid #000 !important;
            border-left: none !important;
        }

        /* Remarks */
        .remarks-title {
            margin-top: 6px;
            font-size: 10.5px;
            font-style: italic;
            font-weight: 700;
            text-decoration: underline;
            color: #000;
        }
        .remarks-content {
            font-size: 10px;
            min-height: 14px;
            color: #000;
            margin-top: 2px;
        }

        /* Signature */
        .signature-section {
            margin-top: 50px;
            margin-bottom: 25px;
            text-align: left;
        }
        .sig-line {
            font-size: 11px;
            letter-spacing: -1px;
            color: #000;
        }
        .sig-label {
            font-size: 10px;
            font-weight: 600;
            margin-top: 2px;
            color: #000;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 2.5mm 3.5mm !important;
                width: 76mm !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .dc-container {
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
        <div class="toolbar-info">
            <i class="fa fa-truck me-1"></i> Stock Release &bull; Delivery Challan #{{ $voucher->voucher_no ?: $voucher->id }}
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print DC
            </button>
            <button class="btn-toolbar btn-close-tb" onclick="window.close()">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

    @php
        $pType = strtolower($voucher->party_type ?? 'customer');
        if ($pType === 'vendor') {
            $partyNameUrdu = $voucher->partyVendor->name ?? 'N/A';
            $partyAddress = $voucher->partyVendor->address ?? '-';
            $partyTransport = '-';
            $partyContact = $voucher->partyVendor->mobile ?? ($voucher->partyVendor->phone ?? '-');
        } else {
            $partyNameUrdu = !empty($voucher->partyCustomer->customer_name_ur)
                ? $voucher->partyCustomer->customer_name_ur
                : ($voucher->partyCustomer->customer_name ?? 'Walk-in Customer');

            $partyAddress = !empty($voucher->partyCustomer->address_ur)
                ? $voucher->partyCustomer->address_ur
                : ($voucher->partyCustomer->address ?? '-');

            $partyTransport = !empty($voucher->partyCustomer->transport_ur)
                ? $voucher->partyCustomer->transport_ur
                : '-';

            $partyContact = !empty($voucher->partyCustomer->mobile)
                ? $voucher->partyCustomer->mobile
                : ($voucher->partyCustomer->mobile_2 ?? '-');
        }

        $releaseDate = !empty($voucher->date)
            ? \Carbon\Carbon::parse($voucher->date)->format('d-m-y')
            : (!empty($voucher->entry_date) ? \Carbon\Carbon::parse($voucher->entry_date)->format('d-m-y') : now()->format('d-m-y'));

        $dcYear = !empty($voucher->date) ? \Carbon\Carbon::parse($voucher->date)->format('Y') : date('Y');
        $dcNumber = $voucher->voucher_no ?: $voucher->id;
        $dcNo = 'AMT-' . $dcYear . '-' . $dcNumber;

        $qtyTotal = $voucher->items->sum('release_qty');

        $voucherWhId = (int)($voucher->warehouse_id ?? 0);
        $voucherLocation = ($voucherWhId === 0)
            ? 'Shop'
            : ($voucher->warehouse->warehouse_name ?? 'Shop');
    @endphp

    <div class="dc-container">
        <!-- Header -->
        <div class="header">
            <div class="shop-name">AL-MADINA TRADERS</div>
            <div class="shop-address">Shop# 2, United Hotel, Qazi Qayoom Road, Hyd</div>
            <div class="shop-phone">0313-2836294, 0313-2331182, 0312-0252899</div>
        </div>

        <div class="thick-divider"></div>

        <div class="title">Stock Release</div>
        <div class="sub-title">Delivery Challan</div>

        <!-- Meta -->
        <div class="meta-section">
            <div><strong>D.C #.</strong> &nbsp;&nbsp;&nbsp;<strong>{{ $dcNo }}</strong></div>
            <div><strong>Date.</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $releaseDate }}</strong></div>
            <div><strong>Location.</strong> &nbsp;<strong>{{ $voucherLocation }}</strong></div>
        </div>

        <!-- Customer Box -->
        <div class="customer-box">
            <table class="cust-table">
                <tr>
                    <td class="cust-lbl">Customer:</td>
                    <td class="cust-val urdu-val">{{ $partyNameUrdu }}</td>
                </tr>
                <tr>
                    <td class="cust-lbl">Address:</td>
                    <td class="cust-val urdu-val">{{ $partyAddress }}</td>
                </tr>
                <tr>
                    <td class="cust-lbl">Transporter:</td>
                    <td class="cust-val urdu-val">{{ $partyTransport ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="cust-lbl">Contact No:</td>
                    <td class="cust-val">{{ $partyContact ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="dc-table">
            <thead>
                <tr>
                    <th style="width: 58%; text-align: left; padding-left: 5px;">Description</th>
                    <th style="width: 18%; text-align: center;">Qty</th>
                    <th style="width: 24%; text-align: center;">Location</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->items as $item)
                @php
                    $brand = optional($item->product->brandRelation)->name ?? '';
                    $pName = $item->product->name ?? ('Product #' . $item->product_id);
                    if ($brand && str_starts_with(strtolower($pName), strtolower($brand))) {
                        $cleanDesc = trim(substr($pName, strlen($brand)));
                    } else {
                        $cleanDesc = $pName;
                    }
                    $whId = (!is_null($item->warehouse_id) && $item->warehouse_id !== '') 
                        ? (int)$item->warehouse_id 
                        : (int)($voucher->warehouse_id ?? 0);
                    $location = ($whId === 0)
                        ? 'Shop'
                        : ($item->warehouse->warehouse_name ?? ($voucher->warehouse->warehouse_name ?? 'Shop'));
                @endphp
                <tr>
                    <td class="td-desc">
                        @if($brand)
                            <span class="brand-name">{{ $brand }}</span>
                        @endif
                        <span class="product-name">{{ $cleanDesc }}</span>
                    </td>
                    <td class="td-qty">{{ number_format($item->release_qty, 0) }}</td>
                    <td class="td-loc">{{ $location }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="total-label">Quantity Total: &nbsp;&gt;&gt;</td>
                    <td class="total-val">{{ number_format($qtyTotal, 0) }}</td>
                    <td class="total-empty"></td>
                </tr>
            </tbody>
        </table>

        <!-- Remarks -->
        <div class="remarks-title">Remarks If any:</div>
        <div class="remarks-content">{{ $voucher->remarks }}</div>

        <!-- Signature -->
        <div class="signature-section">
            <div class="sig-line">---------------------</div>
            <div class="sig-label">Receiver Signature</div>
        </div>
    </div>

</body>
</html>
