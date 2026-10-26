<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Thermal Receipt - Transfer #{{ $transfer->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
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
            background: #4a4d50;
            color: #000;
        }

        /* 🎛️ Screen Toolbar */
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
        .btn-print { background: #e91e63; color: #fff; }
        .btn-print:hover { background: #d81b60; }
        .btn-a5 { background: #0d6efd; color: #fff; }
        .btn-a5:hover { background: #0b5ed7; }
        .btn-close-tb { background: #6c757d; color: #fff; }
        .btn-close-tb:hover { background: #5c636a; }

        /* 🧾 Thermal Receipt Wrapper (80mm / 3 inch) */
        .receipt-container {
            width: 76mm;
            max-width: 100%;
            margin: 15px auto;
            background: #fff;
            padding: 3mm 4mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            box-sizing: border-box;
        }

        /* Header */
        .header-top {
            text-align: center;
        }
        .brand-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 19px;
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
        }
        .brand-sub {
            font-size: 9px;
            font-style: italic;
            font-weight: 600;
            margin-top: 2px;
            line-height: 1.25;
        }
        .order-badge {
            display: inline-block;
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 2px 10px;
            margin: 5px auto 3px auto;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .divider-thick {
            border-top: 2px solid #000;
            margin: 5px 0;
        }

        /* Meta */
        .meta-sec {
            font-size: 10px;
            line-height: 1.45;
            margin-bottom: 5px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
        }
        .meta-lbl {
            font-weight: 700;
        }

        /* Thermal Table */
        .th-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-top: 4px;
        }
        .th-table th {
            background-color: #d8d8d8 !important;
            border: 1px solid #000 !important;
            padding: 3px 2px;
            font-weight: 700;
            color: #000;
            text-align: center;
        }
        .th-table td {
            border: 1px solid #000 !important;
            padding: 2.5px 2px;
            color: #000;
        }
        .grand-row td {
            border-top: 1.5px solid #000 !important;
            background: #fdfdfd;
            font-weight: 700;
            padding: 3px 2px;
            font-size: 10px;
        }
        .ton-row td {
            background-color: #85cde2 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            font-weight: 700;
            padding: 3px 2px;
            font-size: 10px;
        }

        /* Signatures */
        .sig-section {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            font-weight: 700;
            padding: 0 4px;
        }
        .sig-box {
            border-top: 1px solid #000;
            width: 45%;
            text-align: center;
            padding-top: 2px;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 1.5mm 2.5mm !important;
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

    {{-- Screen Toolbar --}}
    <div class="no-print-toolbar">
        <div class="toolbar-info">
            <i class="fa fa-ticket me-1"></i> Stock Transfer #{{ $transfer->id }} &bull; Thermal Print (80mm)
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print Thermal
            </button>
            <a href="{{ route('stock_transfers.print', $transfer->id) }}" class="btn-toolbar btn-a5">
                <i class="fa fa-file-text-o"></i> A5 Print
            </a>
            <button class="btn-toolbar btn-close-tb" onclick="window.close()">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

    @php
        $destName = $transfer->to_shop ? 'Shop' : ($transfer->toWarehouse->warehouse_name ?? '—');
        $destAddress = '';
        $destContact = '';

        if (!$transfer->to_shop && $transfer->toWarehouse) {
            $destAddress = $transfer->toWarehouse->location ?: ($transfer->toWarehouse->branch->address ?? '');
            $destContact = $transfer->toWarehouse->remarks ?: ($transfer->toWarehouse->branch->number ?? '');
        }
    @endphp

    <div class="receipt-container">
        <!-- Header -->
        <div class="header-top">
            <h1 class="brand-name">Al-Madina Traders</h1>
            <div class="brand-sub">Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.</div>
            <div class="brand-sub">Mob/Whatsapp: 0312-0252899, Tel: 022-2780942</div>
            <div class="order-badge">Transfer Order</div>
        </div>

        <div class="divider-thick"></div>

        <!-- Meta -->
        <div class="meta-sec">
            <div class="meta-row">
                <span><span class="meta-lbl">Challan #:</span> <strong>{{ $transfer->id }}</strong></span>
                <span><span class="meta-lbl">Date:</span> {{ \Carbon\Carbon::parse($transfer->entry_date ?? $transfer->created_at)->format('d-M-Y') }}</span>
            </div>
            <div><span class="meta-lbl">Issue To:</span> <strong>{{ $destName }}</strong></div>
            @if($destAddress)
                <div><span class="meta-lbl">Address:</span> {{ $destAddress }}</div>
            @endif
            @if($destContact)
                <div><span class="meta-lbl">Contact #:</span> {{ $destContact }}</div>
            @endif
        </div>

        <div class="divider-thick"></div>

        <!-- Items Table -->
        <table class="th-table">
            <thead>
                <tr>
                    <th style="width: 22px;">S#</th>
                    <th style="width: 32px;">ID</th>
                    <th style="text-align: left; padding-left: 3px;">Description</th>
                    <th style="width: 32px;">Qty</th>
                    <th style="width: 44px;">Weight</th>
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

                        $productName = $item->product->name ?? 'Product #' . $item->product_id;
                        $brandName = $item->product->brandRelation->name ?? ($item->product->brand->name ?? '');
                        if ($brandName && str_starts_with(strtolower($productName), strtolower($brandName))) {
                            $productName = trim(substr($productName, strlen($brandName)));
                        }
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="text-align: center;">{{ $item->product_id }}</td>
                        <td style="text-align: left; padding-left: 3px; word-break: break-word;">
                            @if($brandName)
                                <strong>{{ $brandName }}</strong>
                            @endif
                            {{ $productName }}
                        </td>
                        <td style="text-align: center;">{{ $qDisplay }}</td>
                        <td style="text-align: center;">{{ $wDisplay }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-row">
                    <td colspan="3" style="text-align: right; padding-right: 4px;">Grand Total:</td>
                    <td style="text-align: center;">{{ (float)$totalQty == (int)$totalQty ? number_format($totalQty, 0) : number_format($totalQty, 2) }}</td>
                    <td style="text-align: center;">{{ number_format($totalWeight, 2) }}</td>
                </tr>
                <tr class="ton-row">
                    <td colspan="4" style="text-align: right; padding-right: 4px;">Total Wt in Ton(s):</td>
                    <td style="text-align: center;">{{ number_format($totalWeight / 1000, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($transfer->remarks)
            <div style="font-size: 9px; margin-top: 4px;"><strong>Note:</strong> {{ $transfer->remarks }}</div>
        @endif

        <div class="sig-section">
            <div class="sig-box">Prepared By</div>
            <div class="sig-box">Receiver Sign</div>
        </div>

        <div style="text-align: center; font-size: 8px; color: #777; margin-top: 15px;">
            Printed on {{ date('d-M-Y h:i A') }}
        </div>
    </div>

</body>
</html>
