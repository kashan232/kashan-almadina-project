<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice - {{ $sale->invoice_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --green: #1f7a2f; --blue: #0d6efd; --purple: #6b0f8a; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page { box-shadow: none; margin: 0; width: 100%; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', Arial, sans-serif; background: #f4f4f4; color: #000; font-size: 10pt; }
        .page {
            width: 960px; max-width: 100%; margin: 16px auto; padding: 24px 28px;
            background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.08); position: relative;
        }
        #watermark {
            position: absolute; left: 50%; top: 46%; transform: translate(-50%, -50%) rotate(-18deg);
            width: 680px; opacity: 0.07; pointer-events: none; z-index: 0;
        }
        .content { position: relative; z-index: 1; }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .brand h1 { font-size: 34px; font-weight: 700; margin-bottom: 4px; }
        .brand p { font-size: 9pt; line-height: 1.45; }
        .logo-side { text-align: right; }
        .logo-side img { max-width: 170px; height: auto; }
        .doc-box {
            border: 2px solid #222; padding: 6px 14px; text-align: center;
            font-weight: 700; margin-top: 8px; display: inline-block;
        }
        .doc-box small { display: block; font-weight: 400; font-size: 8pt; margin-top: 2px; }
        hr.sep { border: none; border-top: 2px solid #000; margin: 12px 0 14px; }
        .info-row { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px; font-size: 9.5pt; }
        .customer-info { line-height: 1.55; }
        .inv-meta { min-width: 210px; border-left: 1px solid #000; padding-left: 14px; }
        .inv-meta div { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #000; padding: 5px 6px; font-size: 9pt; }
        table.items th { background: #d9d9d9; font-weight: 700; text-align: center; }
        table.items td.num { text-align: right; }
        table.items td.center { text-align: center; }
        .bottom-wrap { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 6px; gap: 20px; }
        .qty-total { font-weight: 700; font-size: 9pt; margin-top: 8px; }
        .summary { width: 52%; margin-left: auto; font-size: 9pt; line-height: 1.6; }
        .summary-line { display: flex; justify-content: space-between; gap: 10px; }
        .summary-line.bold { font-weight: 700; margin-top: 4px; }
        .summary-line .blue { color: var(--blue); font-weight: 700; }
        .summary-line .purple { color: var(--purple); font-weight: 700; }
        .rv-box { margin-top: 14px; border: 2px solid var(--green); padding: 10px 12px; }
        .rv-box h3 { margin: 0 0 8px; text-decoration: underline; font-size: 10pt; }
        table.rv { width: 100%; border-collapse: collapse; }
        table.rv td { padding: 4px 6px; font-size: 9pt; border-bottom: 1px dotted #999; }
        table.rv td:last-child { text-align: right; font-weight: 600; }
        .amount-words { margin-top: 8px; font-size: 9pt; font-style: italic; font-weight: 600; }
        .footer {
            margin-top: 18px; display: flex; justify-content: space-between;
            align-items: flex-end; font-size: 9pt;
        }
        .signature { width: 220px; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-bottom: 4px; }
        .urdu-footer { margin-top: 10px; font-size: 8.5pt; line-height: 1.5; direction: rtl; text-align: right; }
        .page-no { font-size: 8pt; margin-top: 8px; }
        .print-btn {
            position: fixed; top: 12px; right: 12px; z-index: 99;
            padding: 10px 18px; background: #000; color: #fff; border: none; border-radius: 5px; cursor: pointer;
        }
    </style>
</head>
<body>
@php
    use App\Models\Account;
    use App\Models\AccountHead;

    $pType = strtolower($sale->partyType ?? 'customer');
    if ($pType === 'vendor') {
        $partyName = $sale->vendor->name ?? 'N/A';
    } else {
        $partyName = $sale->customer->customer_name ?? 'Walk-in Customer';
    }
    $partyAddress = trim($sale->address ?? ($sale->customer->address ?? ''));
    $saleDate = $sale->entry_date
        ? \Carbon\Carbon::parse($sale->entry_date)->format('d-M-y')
        : ($sale->created_at ? $sale->created_at->format('d-M-y') : now()->format('d-M-y'));

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
        $amt = (float)($amts[$i] ?? 0);
        if ($amt <= 0) continue;
        $acc = Account::find($accs[$i] ?? null);
        $head = AccountHead::find($heads[$i] ?? null);
        $label = trim($narrs[$i] ?? '');
        if (!$label) {
            $parts = array_filter([$head->name ?? null, $acc->title ?? null]);
            $label = $parts ? implode(', ', $parts) : 'Receipt';
        }
        $receiptRows[] = ['label' => $label, 'amount' => $amt];
    }
    if (empty($receiptRows)) {
        if ((float)$sale->receipt1 > 0) $receiptRows[] = ['label' => 'Receipt', 'amount' => (float)$sale->receipt1];
        if ((float)$sale->receipt2 > 0) $receiptRows[] = ['label' => 'Receipt', 'amount' => (float)$sale->receipt2];
    }
    $receiptTotal = collect($receiptRows)->sum('amount');
    $amountPayable = $invoiceTotal - $receiptTotal;

    $invDisplay = $sale->manual_invoice ?: ('AMT-' . $sale->invoice_no);
@endphp

<button class="print-btn no-print" onclick="window.print()">Print Invoice</button>

<div class="page">
    <img id="watermark" src="{{ asset('amt-watermark.png') }}" alt="" onerror="this.style.display='none'">
    <div class="content">
        <header>
            <div class="brand">
                <h1>Al-Madina Traders</h1>
                <p>Shop#2, United Hotel, Qazi Qayoom Road, Hyderabad</p>
                <p>Mob / Whatsapp: 0312-0252899, Tel: 022-2780942</p>
            </div>
            <div class="logo-side">
                <img src="{{ asset('amt-logo.png') }}" alt="AMT Logo" onerror="this.style.display='none'">
                <div class="doc-box">
                    Delivery Challan
                    <small>Estimate</small>
                </div>
            </div>
        </header>

        <hr class="sep">

        <div class="info-row">
            <div class="customer-info">
                <div><strong>Customer:</strong> {{ $partyName }}</div>
                @if($partyAddress)
                    <div><strong>Address:</strong> {{ $partyAddress }}</div>
                @endif
            </div>
            <div class="inv-meta">
                <div><strong>Inv No.</strong> <span>{{ $invDisplay }}</span></div>
                <div><strong>Date</strong> <span>{{ $saleDate }}</span></div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:6%;">S no.</th>
                    <th style="width:8%;">Qty</th>
                    <th style="width:46%;">Description</th>
                    <th style="width:20%;">Rate</th>
                    <th style="width:20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                @php
                    $rate = $item->sales_rate > 0
                        ? $item->sales_rate
                        : ($item->sales_qty > 0 ? ($item->amount / $item->sales_qty) : $item->sales_price);
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ number_format($item->sales_qty, 0) }}</td>
                    <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
                    <td class="num">{{ number_format($rate, 2) }}</td>
                    <td class="num">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="bottom-wrap">
            <div class="qty-total">Quantity Total: {{ number_format($qtyTotal, 0) }}</div>
            <div class="summary">
                <div class="summary-line">
                    <span>AMOUNT TOTAL:</span>
                    <span>{{ number_format($amountTotal, 2) }}</span>
                </div>
                @if($prevBal != 0)
                <div class="summary-line">
                    <span>Add: Previous Balance:</span>
                    <span class="blue">{{ number_format($prevBal, 2) }}</span>
                </div>
                @endif
                @if($orderDisc > 0)
                <div class="summary-line">
                    <span>Less: Additional Discount:</span>
                    <span>{{ number_format($orderDisc, 2) }}</span>
                </div>
                @endif
                <div class="summary-line bold">
                    <span>INVOICE TOTAL:</span>
                    <span class="blue">{{ number_format($invoiceTotal, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="rv-box">
            <h3>Receipt Voucher</h3>
            <table class="rv">
                <tr>
                    <td>INVOICE TOTAL:</td>
                    <td>{{ number_format($invoiceTotal, 2) }}</td>
                </tr>
                @foreach($receiptRows as $row)
                <tr>
                    <td>Less: {{ $row['label'] }}:</td>
                    <td class="purple">{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td><strong>AMOUNT PAYABLE:</strong></td>
                    <td class="blue"><strong>{{ number_format($amountPayable, 2) }}</strong></td>
                </tr>
            </table>
            <div class="amount-words">
                <span id="amountInWords">{{ number_format($amountPayable, 2, '.', '') }}</span> Rupees Only.
            </div>
        </div>

        <div class="footer">
            <div>
                <div>Thank You for Business with us.</div>
                <div class="page-no">Page 1 of 1</div>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                Authorized Signature
            </div>
        </div>

        <div class="urdu-footer">
            گارنٹی صرف manufacturing fault پر ہوگی۔ سیلڈ بیٹری واپس یا تبدیل نہیں ہوگی۔
        </div>
    </div>
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
    const el = document.getElementById('amountInWords');
    if (!el) return;
    const raw = parseFloat(el.textContent.replace(/,/g, '')) || 0;
    const rupees = Math.floor(raw);
    const paisas = Math.round((raw - rupees) * 100);
    let words = numberToWords(rupees) + ' Rupees';
    if (paisas > 0) words += ' and ' + numberToWords(paisas) + ' Paisas';
    el.textContent = words;
});
</script>
</body>
</html>
