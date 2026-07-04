<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice - {{ $sale->invoice_no }}</title>
    <style>
        :root {
            --blue: #000080;
            --purple: #800080;
            --gray: #d9d9d9;
            --black: #000;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: var(--black);
            background: #ddd;
        }
        .no-print {
            position: fixed; top: 10px; right: 10px; z-index: 999;
            padding: 8px 16px; background: #000; color: #fff;
            border: none; border-radius: 4px; cursor: pointer;
        }
        .print-sheet {
            width: 297mm; margin: 0 auto; background: #fff; display: flex;
        }
        .invoice-copy {
            width: 50%;
            padding: 3mm 3.5mm 2mm;
            position: relative;
            overflow: hidden;
        }
        .invoice-blank { width: 50%; }
        .wm {
            position: absolute; left: 50%; top: 60%;
            transform: translate(-50%, -50%);
            width: 80%; opacity: 0.07; pointer-events: none; z-index: 0;
        }
        .inv-body { position: relative; z-index: 1; }

        /* HEADER — name↔logo, address↔DC (barabar rows) */
        .hdr {
            display: grid;
            grid-template-columns: 1fr auto;
            grid-template-rows: auto auto;
            column-gap: 10px;
            row-gap: 4px;
            align-items: start;
        }
        .hdr-left {
            grid-column: 1;
            grid-row: 1 / span 2;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
        }
        .hdr-left .co-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 30pt;
            font-weight: 700;
            line-height: 1.05;
        }
        .hdr-left .co-lines {
            margin-top: auto;
        }
        .hdr-left .co-line {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            font-weight: 700;
            font-style: italic;
            line-height: 1.3;
        }
        .hdr-right .logo-img {
            grid-column: 2;
            grid-row: 1;
            display: block;
            width: 128px;
            height: 38px;
            object-fit: contain;
            object-position: right top;
            justify-self: end;
        }
        .hdr-right {
            display: contents;
        }
        .dc-box {
            grid-column: 2;
            grid-row: 2;
            justify-self: end;
            border: 1.5px solid var(--black);
            padding: 7px 12px 14px;
            width: 128px;
            text-align: center;
            font-weight: 700;
            font-size: 9pt;
            position: relative;
        }
        .dc-box .est {
            position: absolute; right: 6px; bottom: 2px;
            font-size: 7pt; font-weight: 400;
        }

        .rule {
            border: none;
            border-top: 3px solid var(--black);
            margin: 6px 0 3px;
        }

        /* CUSTOMER + INV */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            font-size: 8.5pt;
        }
        .info-part { flex: 1; }
        .info-part:not(.right) div {
            font-size: 16px;
            font-weight: bold;
        }
        .info-part.right {
            text-align: right;
            flex: 0 0 auto;
            min-width: 130px;
            border: 1px solid #000;
            padding: 5px 0;
        }
        .info-part b { font-weight: 700; }
        .info-part div + div { margin-top: 1px; }

        /* MAIN TABLE */
        table.main {
            width: 100%;
            border-collapse: collapse;
        }
        table.main th,
        table.main td {
            border: 1px solid var(--black);
            padding: 3px 5px;
            font-size: 8pt;
            line-height: 1.25;
            vertical-align: middle;
        }
        table.main th {
            background: var(--gray);
            font-weight: 700;
            text-align: center;
        }
        table.main td.c { text-align: center; }
        table.main td.r { text-align: right; }
        table.main th.col-sno,
        table.main td.col-sno {
            width: 7%;
            padding: 3px 2px;
        }
        table.main th.col-qty,
        table.main td.col-qty {
            width: 8%;
            min-width: 8ch;
            padding: 3px 6px;
        }
        table.main th.col-desc,
        table.main td.col-desc {
            width: 58%;
            padding: 3px 8px;
        }
        .desc-line {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .desc-brand {
            flex: 0 0 auto;
            min-width: 35%;
            text-align: left;
        }
        .desc-item {
            flex: 0 1 auto;
            text-align: left;
        }
        table.main th.col-rate,
        table.main td.col-rate {
            width: 13.5%;
            min-width: 13ch;
            padding: 3px 6px;
            font-variant-numeric: tabular-nums;
        }
        table.main th.col-amt,
        table.main td.col-amt {
            width: 13.5%;
            min-width: 13ch;
            padding: 3px 6px;
            font-variant-numeric: tabular-nums;
        }

        table.main tr.sum td {
            border: none !important;
            padding: 2px 5px;
            font-size: 8pt;
            line-height: 1.25;
        }
        table.main tr.sum-first td {
            border: none !important;
        }
        table.main tr.sum .qty-cell {
            font-weight: 700;
            vertical-align: top;
            padding-top: 4px;
            border: none !important;
        }
        table.main tr.sum .lbl {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
            border: none !important;
        }
        table.main tr.sum .val {
            text-align: right;
            font-weight: 700;
            border: none !important;
        }
        table.main tr.sum .lbl.blue, table.main tr.sum .val.blue { color: var(--blue); }
        table.main tr.sum .lbl.purple { color: var(--purple); }
        table.main tr.sum .val.purple { color: var(--purple); }
        table.main tr.sum-inv td {
            border: none !important;
        }
        table.main tr.sum-last td {
            border: none !important;
        }

        /* RECEIPT VOUCHER */
        .rv-wrap {
            margin-top: 8px;
            border: 1px solid var(--black);
        }
        .rv-title {
            background: var(--gray);
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 3px 4px;
            border: none;
            border-bottom: 1px solid var(--black);
            line-height: 1.25;
        }
        table.rv {
            width: 100%;
            border-collapse: collapse;
        }
        table.rv th,
        table.rv td {
            border: none;
            border-bottom: 1px solid var(--black);
            padding: 3px 6px;
            font-size: 8pt;
            line-height: 1.25;
        }
        table.rv th {
            background: var(--gray);
            font-weight: 700;
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
            border-top: none;
            border-bottom: 1px solid var(--black);
            color: var(--blue);
            font-weight: 700;
        }
        .words-box {
            border: none;
            border-bottom: 1px solid var(--black);
            padding: 4px 6px;
            font-size: 7.5pt;
            font-style: italic;
            font-weight: 600;
            line-height: 1.3;
        }

        /* FOOTER + SIGNATURE */
        .ftr {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8pt;
            line-height: 1.3;
        }
        .sig {
            width: 42%;
            text-align: center;
        }
        .sig-space {
            height: 14mm;
            min-height: 14mm;
        }
        .sig-line {
            border-top: 1px solid var(--black);
            margin-bottom: 3px;
        }
        .sig-label {
            font-size: 8pt;
            font-weight: 600;
        }
        .pg { font-size: 7pt; margin-top: 4px; }
        .urdu {
            margin-top: 8px;
            font-size: 7pt;
            direction: rtl;
            text-align: justify;
            line-height: 1.35;
        }

        @page { size: A4 landscape; margin: 2mm; }
        @media print {
            body { background: #fff; margin: 0; }
            .no-print { display: none !important; }
            .print-sheet { width: 100%; margin: 0; }
            .invoice-copy { padding: 2.5mm 3mm 2mm; }
        }
        @media screen and (max-width: 900px) {
            .print-sheet { flex-direction: column; width: 100%; }
            .invoice-copy { width: 100%; }
            .invoice-blank { display: none; }
        }
    </style>
</head>
<body>
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
        $month = (int) $entryDate->format('n');
        $year = (int) $entryDate->format('Y');
        $fyStart = $month >= 7 ? $year : $year - 1;
        $invDisplay = sprintf(
            '%02d-%02d-%s',
            $fyStart % 100,
            ($fyStart + 1) % 100,
            $sale->invoice_no
        );
    }

    $amountTotal = (float)($sale->sub_total2 ?: $sale->items->sum('amount'));
    $prevBal = (float)($sale->previous_balance ?? 0);
    $orderDisc = (float)($sale->discount_amount ?? 0);
    $invoiceTotal = (float)($sale->total_balance ?? 0);
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
            $label = $parts ? strtoupper(implode(', ', $parts)) : 'RECEIPT';
        }
        $receiptRows[] = ['label' => strtoupper($label), 'amount' => $amt];
    }
    $showReceiptVoucher = count($receiptRows) > 0;
    $receiptTotal = collect($receiptRows)->sum('amount');
    $amountPayable = $invoiceTotal - $receiptTotal;
    $wordsRaw = number_format($showReceiptVoucher ? $amountPayable : $invoiceTotal, 2, '.', '');
    $sumRows = 4;
@endphp

<button class="no-print" onclick="window.print()">Print Invoice</button>

<div class="print-sheet">
    <div class="invoice-copy">
        <img class="wm" src="{{ asset('amt-watermark.png') }}" alt="">
        <div class="inv-body">
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

            <hr class="rule">

            <div class="info-row">
                <div class="info-part">
                    <div><b>Customer:</b> {{ $partyName }}</div>
                    @if($partyAddress)
                        <div><b>Address:</b> {{ $partyAddress }}</div>
                    @endif
                </div>
                <div class="info-part right">
                    <div><b>Inv No.</b> {{ $invDisplay }}</div>
                    <div><b>Date :</b> {{ $saleDate }}</div>
                </div>
            </div>

            <hr class="rule">

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

                    <tr class="sum sum-first">
                        <td colspan="3" rowspan="{{ $sumRows }}" class="qty-cell">
                            Quantity Total: {{ number_format($qtyTotal, 0) }}
                        </td>
                        <td class="lbl blue">AMOUNT TOTAL. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($amountTotal, 2) }}</td>
                    </tr>
                    <tr class="sum">
                        <td class="lbl blue">Add: Previous Balance. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($prevBal, 2) }}</td>
                    </tr>
                    <tr class="sum">
                        <td class="lbl purple">Less: Additional Discount. &gt;&gt;&gt;</td>
                        <td class="val purple">{{ $orderDisc > 0 ? number_format($orderDisc, 2) : '' }}</td>
                    </tr>
                    <tr class="sum sum-inv sum-last">
                        <td class="lbl blue">INVOICE TOTAL. &gt;&gt;&gt;</td>
                        <td class="val blue">{{ number_format($invoiceTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            @if($showReceiptVoucher)
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
                        <tr>
                            <td class="purple">Less: {{ $row['label'] }}. &gt;&gt;&gt;</td>
                            <td class="r purple">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                        @for($i = count($receiptRows); $i < 2; $i++)
                        <tr>
                            <td class="purple">Less: . &gt;&gt;&gt;</td>
                            <td class="r purple"></td>
                        </tr>
                        @endfor
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
            @endif

            <div class="ftr">
                <div>
                    <div>Thank You for Business with us.</div>
                    <div class="pg">Page 1 of 1</div>
                </div>
                <div class="sig">
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorized Signature</div>
                </div>
            </div>

            <div class="urdu">
                گارنٹی صرف manufacturing fault پر ہوگی۔ سیلڈ بیٹری واپس یا تبدیل نہیں ہوگی۔ شکریہ
            </div>
        </div>
    </div>
    <div class="invoice-blank"></div>
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
