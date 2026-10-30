<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice - {{ $sale->invoice_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    @php
        $isSinglePage = ($sale->items->count() <= 6);
    @endphp
    <style>
        :root {
            --blue: #000080;
            --purple: #800080;
            --gray: #d9d9d9;
            --black: #000;
            --dark-grey: #555;
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
            color: var(--black);
            background: #525659;
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
        .btn-dc { background: #198754; color: #fff; }
        .btn-dc:hover { background: #157347; }
        .btn-close-tb { background: #6c757d; color: #fff; }
        .btn-close-tb:hover { background: #5c636a; }

        /* 📄 A5 Portrait Paper Sheet Container */
        .invoice-sheet {
            width: 148mm;
            min-height: 210mm;
            margin: 15px auto;
            background: #fff;
            padding: 5mm 6mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35);
            box-sizing: border-box;
            position: relative;
        }
        .wm {
            position: absolute;
            left: 50%;
            top: 55%;
            transform: translate(-50%, -50%);
            width: 75%;
            opacity: 0.05;
            filter: grayscale(100%);
            pointer-events: none;
            z-index: 0;
        }
        .inv-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: calc(210mm - 10mm);
            box-sizing: border-box;
        }
        .inv-main-body {
            flex: 1 0 auto;
        }
        .inv-footer-area {
            flex-shrink: 0;
            margin-top: auto;
            padding-top: 15px;
        }

        /* HEADER */
        .hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .hdr-left .co-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 25pt;
            font-weight: 700;
            line-height: 1.05;
            color: #000;
        }
        .hdr-left .co-lines {
            margin-top: 3px;
        }
        .hdr-left .co-line {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            font-weight: 700;
            font-style: italic;
            line-height: 1.3;
            color: #000;
        }
        .hdr-right {
            text-align: right;
            flex-shrink: 0;
            width: 140px;
        }
        .hdr-right .logo-img {
            display: block;
            width: 140px;
            height: 32px;
            object-fit: fill;
            margin-left: auto;
        }
        .dc-box {
            border: 1.5px solid var(--dark-grey);
            padding: 3px 8px 11px;
            width: 140px;
            box-sizing: border-box;
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            position: relative;
            margin-top: 3px;
            margin-left: auto;
        }
        .dc-box .est {
            position: absolute;
            right: 4px;
            bottom: 1px;
            font-size: 6.5pt;
            font-weight: 400;
        }

        .thick-rule {
            border: none;
            border-top: 2px solid var(--dark-grey);
            margin: 4px 0 5px;
        }

        /* CUSTOMER + INV INFO */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            font-size: 9pt;
            margin-bottom: 2px;
        }
        .info-part-left {
            flex: 1;
            line-height: 1.45;
        }
        .info-part-left .cust-row {
            display: flex;
            align-items: flex-start;
        }
        .info-part-left .cust-lbl {
            font-weight: 700;
            width: 60px;
            min-width: 60px;
            flex-shrink: 0;
        }
        .info-part-left .cust-sep {
            font-weight: 700;
            margin-right: 6px;
            flex-shrink: 0;
        }
        .info-part-left .cust-val {
            font-weight: 700;
            flex: 1;
        }
        .info-part-left .cust-addr-line {
            margin-top: 3px;
        }
        .info-part-right {
            text-align: left;
            flex: 0 0 auto;
            border: 1.5px solid var(--dark-grey);
            padding: 3px 8px;
            width: 140px;
            min-width: 140px;
            box-sizing: border-box;
            line-height: 1.45;
            font-size: 9pt;
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            white-space: nowrap;
        }
        .info-part-right b {
            font-weight: 700;
            display: inline-block;
            width: 48px;
        }

        /* MAIN TABLE */
        table.main {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.main th,
        table.main td {
            border: 1px solid var(--dark-grey);
            padding: 2.5px 3px;
            font-size: 8.5pt;
            line-height: 1.25;
            vertical-align: middle;
            box-sizing: border-box;
        }
        table.main th {
            background: var(--gray) !important;
            font-weight: 700;
            text-align: center;
        }
        table.main td.c { text-align: center; }
        table.main td.r { text-align: right; }
        table.main th.col-sno,
        table.main td.col-sno {
            width: 8%;
            text-align: center;
            padding: 2.5px 1px;
        }
        table.main th.col-qty,
        table.main td.col-qty {
            width: 11%;
            text-align: center;
            padding: 2.5px 1px;
        }
        table.main th.col-desc,
        table.main td.col-desc {
            width: 47%;
            padding: 2.5px 4px;
        }
        .desc-line {
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: hidden;
        }
        .desc-brand {
            flex: 0 0 auto;
            min-width: 45px;
            font-weight: 700;
            text-align: left;
            margin-right: 4px;
        }
        .desc-item {
            flex: 1 1 auto;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table.main th.col-rate,
        table.main td.col-rate {
            width: 16%;
            padding: 2.5px 3px;
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }
        table.main th.col-amt,
        table.main td.col-amt {
            width: 18%;
            padding: 2.5px 3px;
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        /* TOTALS UNDER TABLE */
        .inv-totals-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        .inv-totals-left {
            font-weight: 700;
            font-size: 8.5pt;
            padding-top: 2px;
            white-space: nowrap;
            flex: 1 1 auto;
        }
        .inv-totals-right {
            flex: 0 0 auto;
            width: 60%;
        }
        table.totals-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.totals-table td {
            border: none !important;
            padding: 1.5px 2px;
            font-size: 8.5pt;
            line-height: 1.25;
            box-sizing: border-box;
        }
        table.totals-table td.lbl {
            width: 63%;
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
            padding-right: 6px;
        }
        table.totals-table td.val {
            width: 37%;
            text-align: right;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            padding-right: 3px;
        }
        table.totals-table td.lbl.blue, table.totals-table td.val.blue { color: var(--blue); }
        table.totals-table td.lbl.purple, table.totals-table td.val.purple { color: var(--purple); }
        table.totals-table tr.inv-total-row td {
            padding-top: 3px;
        }
        table.totals-table tr.inv-total-row td.val {
            border-top: 1.5px solid var(--dark-grey) !important;
        }

        /* RECEIPT VOUCHER */
        .rv-wrap {
            margin-top: 6px;
            border: 1px solid var(--dark-grey);
        }
        .rv-title {
            background: var(--gray) !important;
            text-align: center;
            font-weight: 700;
            font-size: 9pt;
            padding: 3px 4px;
            border-bottom: 1px solid var(--dark-grey);
            line-height: 1.25;
        }
        table.rv {
            width: 100%;
            border-collapse: collapse;
        }
        table.rv th,
        table.rv td {
            border: none;
            padding: 2.5px 6px;
            font-size: 8.5pt;
            line-height: 1.25;
        }
        table.rv th {
            background: var(--gray) !important;
            font-weight: 700;
            border-bottom: 1px solid var(--dark-grey);
        }
        table.rv th:first-child,
        table.rv td:first-child {
            text-align: left;
        }
        table.rv th:last-child,
        table.rv td.r {
            text-align: right;
            font-weight: 700;
        }
        table.rv td.blue { color: var(--blue); font-weight: 700; }
        table.rv td.purple { color: var(--purple); font-weight: 700; }
        table.rv tr.rv-pay td {
            border-top: 1px solid var(--black);
            color: var(--blue);
            font-weight: 700;
        }
        .words-box {
            border: none !important;
            padding: 4px 0;
            font-size: 8.5pt;
            font-style: italic;
            font-weight: 600;
            line-height: 1.35;
        }
        .words-box-standalone {
            border: none !important;
            margin-top: 6px;
            background: transparent !important;
        }

        /* FOOTER & SIGNATURE */
        .ftr {
            margin-top: 0;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8.5pt;
            line-height: 1.3;
        }
        .ftr-thanks {
            font-weight: 700;
            font-size: 9.5pt;
        }
        .sig {
            width: 38%;
            text-align: center;
        }
        .sig-space {
            height: 9mm;
        }
        .sig-line {
            border-top: 1px solid var(--dark-grey);
            margin-bottom: 3px;
        }
        .sig-label {
            font-size: 8.5pt;
            font-weight: 600;
            font-style: italic;
        }

        /* URDU DISCLAIMER BOX */
        .urdu-disclaimer-box {
            margin-top: 8px;
            border: none;
            border-top: 1px solid var(--dark-grey);
            padding: 4px 0 2px;
            direction: rtl;
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', Arial, sans-serif;
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.9;
            color: #000;
        }
        .urdu-disclaimer-box .urdu-line {
            display: block;
            margin-bottom: 2px;
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 5mm 6mm;
            }
            html, body {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .invoice-sheet {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
                overflow: visible !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            @if($isSinglePage)
            .inv-content {
                min-height: calc(210mm - 28mm) !important;
            }
            @else
            .inv-content {
                min-height: auto !important;
            }
            @endif
            .inv-footer-area {
                padding-top: 6px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .sig-space {
                height: 7mm !important;
            }
            .urdu-disclaimer-box {
                margin-top: 4px !important;
                padding: 2px 0 0 !important;
                margin-bottom: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .urdu-disclaimer-box .urdu-line:last-child {
                margin-bottom: 0 !important;
            }
            .rv-wrap, .words-box, .ftr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.main tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Screen Toolbar -->
    <div class="no-print-toolbar">
        <div class="toolbar-info">
            <i class="fa fa-file-text-o me-1"></i> Sale Invoice #{{ $sale->invoice_no }} &bull; A5 Portrait Print
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print Invoice
            </button>
            <a href="{{ route('sale.dc', $sale->id) }}" class="btn-toolbar btn-dc">
                <i class="fa fa-truck"></i> Delivery Challan
            </a>
            <button class="btn-toolbar btn-close-tb" onclick="window.close()">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

@php
    use App\Models\Account;
    use App\Models\AccountHead;

    $pType = strtolower($sale->partyType ?? 'customer');
    $partyName = $pType === 'vendor'
        ? ($sale->vendor->name ?? 'N/A')
        : ($sale->customer->customer_name ?? 'Walk-in Customer');
    $partyAddress = trim($sale->address ?? ($sale->customer->address ?? ''));
    $entryDate = $sale->entry_date
        ? \Carbon\Carbon::parse($sale->entry_date)
        : ($sale->created_at ?? now());
    $saleDate = $entryDate->format('d-M-y');

    if (!empty($sale->manual_invoice)) {
        $invDisplay = $sale->manual_invoice;
    } else {
        $invNo = trim((string)$sale->invoice_no);
        $invDisplay = str_starts_with(strtoupper($invNo), 'AMT-') ? $invNo : ('AMT-' . $invNo);
    }

    $sumItemsAmount = (float)$sale->items->sum('amount');
    $amountTotal = ((float)$sale->sub_total2 > 0) ? (float)$sale->sub_total2 : $sumItemsAmount;
    $prevBal = (float)($sale->previous_balance ?? 0);
    $orderDisc = (float)($sale->discount_amount ?? 0);
    $calculatedInvoiceTotal = $amountTotal + $prevBal - $orderDisc;
    $invoiceTotal = $calculatedInvoiceTotal;
    $qtyTotal = $sale->items->sum('sales_qty');

    $receiptRows = [];
    $heads = json_decode($sale->receipt_heads ?? '[]', true) ?: [];
    $accs = json_decode($sale->receipt_accounts ?? '[]', true) ?: [];
    $amts = json_decode($sale->receipt_amounts_json ?? '[]', true) ?: [];
    $narrs = json_decode($sale->receipt_narrations ?? '[]', true) ?: [];
    $maxRv = max(count($accs), count($amts));
    for ($i = 0; $i < $maxRv; $i++) {
        $accId = $accs[$i] ?? null;
        $amt = (float)($amts[$i] ?? 0);
        if (empty($accId) || $amt <= 0) continue;
        $acc = Account::find($accId);
        $head = AccountHead::find($heads[$i] ?? null);
        $label = trim($narrs[$i] ?? '');
        if (!$label) {
            $parts = array_filter([$head->name ?? null, $acc->title ?? null]);
            $label = $parts ? strtoupper(implode(', ', $parts)) : 'CASH RECEIVED';
        }
        $receiptRows[] = ['label' => strtoupper($label), 'amount' => $amt];
    }
    $receiptTotal = collect($receiptRows)->sum('amount');
    if ($receiptTotal <= 0 && !empty($sale->receipt1) && (float)$sale->receipt1 > 0) {
        $receiptRows[] = ['label' => 'CASH RECEIVED', 'amount' => (float)$sale->receipt1];
        $receiptTotal = (float)$sale->receipt1;
    }
    $hasReceipt = ($receiptTotal > 0);
    $amountPayable = $invoiceTotal - $receiptTotal;
    $wordsRaw = number_format($hasReceipt ? $amountPayable : $invoiceTotal, 2, '.', '');
    $sumRows = 4;
@endphp

<div class="invoice-sheet">
    <img class="wm" src="{{ asset('amt-watermark.png') }}" alt="">
    <div class="inv-content">
        <div class="inv-main-body">
            <!-- Header -->
            <div class="hdr">
                <div class="hdr-left">
                    <div class="co-name">Al-Madina Traders</div>
                    <div class="co-lines">
                        <div class="co-line">Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.</div>
                        <div class="co-line">Mob / Whatsapp: 0312-0252899 , Tel: 022-2780942</div>
                    </div>
                </div>
                <div class="hdr-right">
                    <img class="logo-img" src="{{ asset('amt-logo.png') }}" alt="AMT">
                    <div class="dc-box">
                        Delivery Challan
                        <span class="est">Estimate</span>
                    </div>
                </div>
            </div>

            <hr class="thick-rule">

            <!-- Info Row -->
            <div class="info-row">
                <div class="info-part-left">
                    <div class="cust-row"><span class="cust-lbl">Customer</span><span class="cust-sep">:</span><span class="cust-val">{{ $partyName }}</span></div>
                    @if($partyAddress)
                        <div class="cust-row cust-addr-line"><span class="cust-lbl">Address</span><span class="cust-sep">:</span><span class="cust-val">{{ $partyAddress }}</span></div>
                    @endif
                </div>
                <div class="info-part-right">
                    <div><b>Inv No.</b> {{ $invDisplay }}</div>
                    <div><b>Date :</b> {{ $saleDate }}</div>
                </div>
            </div>

            <hr class="thick-rule">

            <!-- Table -->
        <table class="main">
            <thead>
                <tr>
                    <th class="col-sno">S no.</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-desc">Description</th>
                    <th class="col-rate">Rate</th>
                    <th class="col-amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                @php
                    $rate = $item->sales_rate > 0
                        ? $item->sales_rate
                        : ($item->sales_qty > 0 ? ($item->amount / $item->sales_qty) : $item->sales_price);
                    $productName = $item->product->name ?? 'Product #' . $item->product_id;
                    $brandName = $item->product->brandRelation->name ?? '';
                    if ($brandName && str_starts_with(strtolower($productName), strtolower($brandName))) {
                        $productName = trim(substr($productName, strlen($brandName)));
                    }
                @endphp
                <tr>
                    <td class="c col-sno">{{ $index + 1 }}</td>
                    <td class="c col-qty">{{ number_format($item->sales_qty, 0) }}</td>
                    <td class="col-desc">
                        <div class="desc-line">
                            @if($brandName)
                                <span class="desc-brand">{{ $brandName }}</span>
                            @endif
                            <span class="desc-item">{{ $productName }}</span>
                        </div>
                    </td>
                    <td class="r col-rate">{{ number_format($rate, 2) }}</td>
                    <td class="r col-amt">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section (Quantity Total on Left, Financial Totals on Right) -->
        <div class="inv-totals-wrap">
            <div class="inv-totals-left">
                Quantity Total: {{ number_format($qtyTotal, 0) }}
            </div>
            <div class="inv-totals-right">
                <table class="totals-table">
                    <tr>
                        <td class="lbl blue">AMOUNT TOTAL. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($amountTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl blue">Add: Previous Balance. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($prevBal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl purple">Less: Additional Discount. &gt;&gt;&gt;</td>
                        <td class="val purple">{{ $orderDisc > 0 ? number_format($orderDisc, 2) : '' }}</td>
                    </tr>
                    <tr class="inv-total-row">
                        <td class="lbl blue">INVOICE TOTAL. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($invoiceTotal, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($hasReceipt)
        <!-- Receipt Voucher Section -->
        <div class="rv-wrap">
            <div class="rv-title">Receipt Voucher</div>
            <table class="rv">
                <thead>
                    <tr>
                        <th style="width:62%;">Narration</th>
                        <th style="width:38%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="blue">INVOICE TOTAL. &gt;&gt;&gt;</td>
                        <td class="r blue">{{ number_format($invoiceTotal, 2) }}</td>
                    </tr>
                    @foreach($receiptRows as $row)
                    @php
                        $labelClean = ucwords(strtolower($row['label']));
                        $prefix = (stripos($row['label'], 'CASH') !== false) ? 'Less ' : 'Less: ';
                    @endphp
                    <tr>
                        <td class="purple">{{ $prefix }}{{ $labelClean }}. &gt;&gt;&gt;</td>
                        <td class="r purple">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="rv-pay">
                        <td class="blue">AMOUNT PAYABLE. &gt;&gt;&gt;</td>
                        <td class="r blue">{{ number_format($amountPayable, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="words-box">
                <span class="amount-in-words" data-amount="{{ $wordsRaw }}"></span>
            </div>
        </div>
        @else
        <!-- Standalone Amount in Words (when no Receipt Voucher) -->
        <div class="words-box words-box-standalone">
            <span class="amount-in-words" data-amount="{{ $wordsRaw }}"></span>
        </div>
        @endif
    </div><!-- /.inv-main-body -->

    <!-- Footer Area (Signature + Urdu Disclaimer aligned to page bottom) -->
    <div class="inv-footer-area">
        <div class="ftr">
            <div class="ftr-thanks">
                Thank You for Business with us.
            </div>
            <div class="sig">
                <div class="sig-space"></div>
                <div class="sig-line"></div>
                <div class="sig-label">Authorized Signature</div>
            </div>
        </div>

        <div class="urdu-disclaimer-box">
            <div class="urdu-line">اگر بیٹری اسٹیمپ کی تاریخ کے 3 ماہ کے اندر فروخت نہ ہو، تو بیٹری تبدیل کروائیں ورنہ کلیم قبول نہیں ہوگا۔</div>
            <div class="urdu-line">اسٹیمپ کے بغیر کلیم قابل قبول نہیں ہوگا۔ اوساکا کا کلیم صرف کلیم سنٹر پر ہوگا۔</div>
        </div>
    </div><!-- /.inv-footer-area -->
</div><!-- /.inv-content -->
</div>

<script>
function numberToWords(num) {
    const a = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
        'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    const b = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    num = Math.floor(Math.abs(num));
    if (num === 0) return 'Zero';
    if (('' + num).length > 9) return 'Overflow';
    const n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
    if (!n) return '';
    let str = '';
    str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
    str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
    str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
    str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
    str += (n[5] != 0) ? ((str !== '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
    return str.trim();
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.amount-in-words').forEach(function(el) {
        const raw = parseFloat(el.dataset.amount) || 0;
        const rupees = Math.floor(Math.abs(raw));
        const paisas = Math.round((Math.abs(raw) - rupees) * 100);
        let words = numberToWords(rupees) + ' Rupees';
        if (paisas > 0) words += ' and ' + numberToWords(paisas) + ' Paisas';
        words += ' Only.';
        el.textContent = words;
    });
});
</script>
</body>
</html>
