<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Voucher - {{ $voucher->rvid }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    @php
        $isSinglePage = (count($rows) <= 6);
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
            width: 145px;
        }
        .hdr-right .logo-img {
            display: block;
            width: 145px;
            height: 40px;
            object-fit: fill;
            margin-left: auto;
        }
        .dc-box {
            border: 1.5px solid var(--dark-grey);
            padding: 4px 8px;
            width: 145px;
            box-sizing: border-box;
            text-align: center;
            font-weight: 700;
            font-size: 8.5pt;
            position: relative;
            margin-top: 6px;
            margin-left: auto;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .thick-rule {
            border: none;
            border-top: 2px solid var(--dark-grey);
            margin: 4px 0 5px;
        }

        /* CUSTOMER + VOUCHER INFO */
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
            width: 145px;
            min-width: 145px;
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
            width: 72px;
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
            padding: 3px 4px;
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
        table.main tfoot td {
            background: var(--gray) !important;
            border: 1px solid var(--dark-grey);
            padding: 3px 4px;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.25;
            box-sizing: border-box;
        }
        table.main td.c { text-align: center; }
        table.main td.r { text-align: right; }
        table.main th.col-sno,
        table.main td.col-sno {
            width: 8%;
            text-align: center;
            padding: 2.5px 1px;
        }

        /* TOTALS UNDER TABLE (Positioned on the Left) */
        .inv-totals-wrap {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            margin-top: 6px;
        }
        .inv-totals-left {
            flex: 0 0 auto;
            width: 285px;
        }
        table.totals-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.totals-table td {
            border: none !important;
            padding: 1.5px 0;
            font-size: 8.5pt;
            line-height: 1.25;
            box-sizing: border-box;
        }
        table.totals-table td.lbl {
            width: 145px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }
        table.totals-table td.arr {
            width: 35px;
            text-align: center;
            font-weight: 700;
            white-space: nowrap;
        }
        table.totals-table td.val {
            width: 105px;
            text-align: right;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            padding-right: 2px;
        }
        table.totals-table td.lbl.blue, table.totals-table td.arr.blue, table.totals-table td.val.blue { color: var(--blue); }
        table.totals-table td.lbl.purple, table.totals-table td.arr.purple, table.totals-table td.val.purple { color: var(--purple); }
        table.totals-table tr.inv-total-row td {
            padding-top: 3px;
        }
        table.totals-table tr.inv-total-row td.val {
            border-top: 1.5px solid var(--dark-grey) !important;
        }

        /* AMOUNT IN WORDS */
        .words-box-standalone {
            margin-top: 8px;
            padding: 2px 0;
            font-size: 8.5pt;
            font-weight: 700;
            font-style: italic;
            color: #000;
        }

        /* FOOTER */
        .ftr {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 4px;
        }
        .ftr-thanks {
            font-weight: 700;
            font-size: 9.5pt;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
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
            .words-box, .ftr {
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
            <i class="fa fa-file-text-o me-1"></i> Receipt Voucher #{{ $voucher->rvid }} &bull; A5 Portrait Print
        </div>
        <div class="toolbar-actions">
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print Voucher
            </button>
            <button class="btn-toolbar btn-close-tb" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

@php
    $partyLabel = 'Customer';
    $partyName = '-';
    $partyAddress = '';
    $partyPhone = '';

    if (is_numeric($voucher->type)) {
        $partyLabel = 'Account';
        $partyName = $party->name ?? '-';
        $partyAddress = $party->head_name ?? '';
        $partyPhone = $party->phone ?? '';
    } elseif ($voucher->type === 'vendor') {
        $partyLabel = 'Vendor';
        $partyName = $party->name ?? '-';
        $partyAddress = $party->address ?? '';
        $partyPhone = $party->phone ?? '';
    } elseif (in_array($voucher->type, ['customer', 'walkin'], true)) {
        $partyLabel = ($voucher->type === 'walkin') ? 'Walk-in' : 'Customer';
        $partyName = $party->customer_name ?? '-';
        $partyAddress = $party->address ?? '';
        $partyPhone = $party->mobile ?? '';
    }

    $rvNo = trim((string)$voucher->rvid);
    $voucherDisplay = str_starts_with(strtoupper($rvNo), 'AMT-') ? $rvNo : ('AMT-' . $rvNo);
    $voucherDate = \Carbon\Carbon::parse($voucher->receipt_date)->format('d-M-y');

    $discountVal = 0;
    if (!empty($voucher->discount_value)) {
        $dArr = json_decode($voucher->discount_value, true);
        if (is_array($dArr)) {
            $discountVal = array_sum(array_map('floatval', $dArr));
        } elseif (is_numeric($voucher->discount_value)) {
            $discountVal = (float)$voucher->discount_value;
        }
    }
    $amountPayable = $previousBalance - $voucher->total_amount - $discountVal;
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
                        Receipt Voucher
                    </div>
                </div>
            </div>

            <hr class="thick-rule">

            <!-- Info Row -->
            <div class="info-row">
                <div class="info-part-left">
                    <div class="cust-row"><span class="cust-lbl">{{ $partyLabel }}</span><span class="cust-sep">:</span><span class="cust-val">{{ $partyName }}</span></div>
                    @if($partyAddress && $partyAddress !== '—' && $partyAddress !== '-')
                        <div class="cust-row cust-addr-line"><span class="cust-lbl">Address</span><span class="cust-sep">:</span><span class="cust-val">{{ $partyAddress }}</span></div>
                    @endif
                </div>
                <div class="info-part-right">
                    <div><b>Voucher No.</b> {{ $voucherDisplay }}</div>
                    <div><b>Date :</b> {{ $voucherDate }}</div>
                </div>
            </div>

            <hr class="thick-rule">

            <!-- Table -->
            <table class="main">
                <thead>
                    <tr>
                        <th class="col-sno">S no.</th>
                        <th style="width: 58%; text-align: left; padding-left: 6px;">Payment Details / Towards Account</th>
                        <th style="width: 14%;">Reference</th>
                        <th style="width: 20%; text-align: right; padding-right: 6px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $key => $row)
                    @php
                        $accParts = array_filter([$row['account_head'] ?? '', $row['account_name'] ?? '']);
                        $accStr = implode(' - ', $accParts);
                        if (!empty($row['account_code'])) {
                            $accStr .= ' (' . $row['account_code'] . ')';
                        }
                    @endphp
                    <tr>
                        <td class="c">{{ $key + 1 }}</td>
                        <td style="text-align: left; padding: 3px 6px;">
                            <div style="font-weight: 700;">{{ $row['narration'] ?? 'Cash Received' }}</div>
                            @if($accStr)
                                <div style="font-size: 8pt; color: #444; font-weight: normal;">Towards: {{ $accStr }}</div>
                            @endif
                        </td>
                        <td class="c">{{ $row['reference'] ?: '-' }}</td>
                        <td class="r" style="font-weight: 700; font-variant-numeric: tabular-nums; padding-right: 6px;">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 700; padding-right: 8px;">Total :</td>
                        <td class="r" style="font-weight: 700; font-variant-numeric: tabular-nums; padding-right: 6px;">{{ number_format($voucher->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Totals Section (On the Left) -->
            <div class="inv-totals-wrap">
                <div class="inv-totals-left">
                    <table class="totals-table">
                        <tr>
                            <td class="lbl blue">Add: Previous Balance.</td>
                            <td class="arr blue">&gt;&gt;&gt;</td>
                            <td class="val blue">{{ number_format($previousBalance, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="lbl purple">Less: Payment Received.</td>
                            <td class="arr purple">&gt;&gt;&gt;</td>
                            <td class="val purple">{{ number_format($voucher->total_amount, 2) }}</td>
                        </tr>
                        @if($discountVal > 0)
                        <tr>
                            <td class="lbl purple">Less: Discount.</td>
                            <td class="arr purple">&gt;&gt;&gt;</td>
                            <td class="val purple">{{ number_format($discountVal, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="inv-total-row">
                            <td class="lbl blue">BALANCE PAYABLE.</td>
                            <td class="arr blue">&gt;&gt;&gt;</td>
                            <td class="val blue">{{ number_format($amountPayable, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Amount in Words -->
            <div class="words-box-standalone">
                Amount in Words: <span class="amount-in-words" data-amount="{{ $voucher->total_amount }}"></span>
            </div>
        </div><!-- /.inv-main-body -->

        <!-- Footer Area (Signature + Thank you aligned to page bottom) -->
        <div class="inv-footer-area">
            <div class="ftr">
                <div class="ftr-thanks">
                    Thank You for Payment.
                </div>
                <div class="sig">
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorized Signature</div>
                </div>
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
