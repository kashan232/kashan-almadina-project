<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Income Voucher - AMT</title>

    <!-- Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --green: #0b5a2b;
            --purple: #6b0f8a;
            --border: #1f7a2f;
            --muted: #666;
            --box-bg: #fff;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f6f6f6;
            margin: 0;
        }

        .page {
            width: 960px;
            margin: 18px auto;
            padding: 28px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        #watermark {
            position: absolute;
            left: 50%;
            top: 48%;
            transform: translate(-50%, -50%) rotate(-18deg);
            width: 720px;
            opacity: 0.08;
            pointer-events: none;
        }

        header {
            display: flex;
            justify-content: space-between;
        }

        .brand h1 {
            margin: 0;
            font-size: 40px;
            font-weight: 700;
        }

        .receipt-badge {
            border: 2px solid #222;
            padding: 8px 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        hr.sep {
            border-top: 2px solid #000;
            margin: 14px 0 18px;
        }

        .meta-row {
            display: flex;
            gap: 18px;
        }

        .left {
            flex: 1;
            border: 2px solid #000;
            padding: 12px 14px;
        }

        .left .line {
            display: flex;
            margin-bottom: 6px;
        }

        .left .label {
            min-width: 140px;
            font-weight: 700;
        }

        .right {
            width: 260px;
            border: 2px solid var(--border);
            padding: 10px;
        }

        .right .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .payments {
            margin-top: 18px;
        }

        .payments h3 {
            margin: 0 0 8px;
            text-decoration: underline;
        }

        .amount-words {
            margin-top: 6px;
            font-style: italic;
            font-weight: 600;
        }

        .summary {
            margin-top: 14px;
            border: 3px solid #1f7a2f;
            padding: 12px;
        }

        .summary td {
            padding: 6px 4px;
            font-weight: 600;
        }

        .summary td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .footer {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .thank {
            font-weight: 700;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 10px;
        }
        
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .page { box-shadow: none; margin: 0 auto; }
        }
    </style>
</head>

<body>
    <div class="no-print" style="padding-top: 20px;">
        <button onclick="window.print()" style="background:var(--green); color:white; border:none; padding:10px 30px; border-radius:5px; cursor:pointer; font-weight:700;">Print Voucher</button>
    </div>

    <div class="page">
        @if($voucher->status === 'posted')
        <img id="watermark" src="{{ asset('amt-watermark.png') }}" alt="AMT watermark" onerror="this.style.display='none'">
        <div style="position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 140px; color: rgba(0,0,0,0.05); font-weight: bold; pointer-events: none;">POSTED</div>
        @endif

        <header>
            <div class="brand">
                <h1>Al–Madina Traders</h1>
                <p>Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad</p>
                <p>Mobile / Whatsapp: 0312-0252899; Tel: 022-2780942</p>
            </div>
            <div class="logo" style="text-align:right;">
                <img src="{{ asset('amt-logo.png') }}" alt="AMT Logo" style="max-width:200px;" onerror="this.style.display='none'">
                <div style="margin-top:8px;">
                    <span class="receipt-badge">Income VOUCHER</span>
                </div>
            </div>
        </header>

        <hr class="sep">

        <div class="meta-row">
            <div class="left">
                <div class="line">
                    <div class="label">Received In (A/C):</div>
                    <div class="value fw-bold">{{ $party->name ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Account Head:</div>
                    <div class="value">{{ $party->head_name ?? '-' }}</div>
                </div>
                <div class="line">
                    <div class="label">Account Code:</div>
                    <div class="value">{{ $party->account_code ?? '-' }}</div>
                </div>
            </div>

            <div class="right">
                <div style="margin-bottom:8px; font-weight:700;">
                    Voucher No: <span style="float:right; color:var(--green);">{{ $voucher->ivid }}</span>
                </div>
                <div class="meta-item">
                    <span>Voucher Date:</span>
                    <span>{{ \Carbon\Carbon::parse($voucher->entry_date)->format('d-M-Y') }}</span>
                </div>
                <div class="meta-item small text-muted" style="font-weight:normal; font-size:11px;">
                    <span>Created:</span>
                    <span>{{ $voucher->created_at ? $voucher->created_at->format('d-M-Y g:i A') : '-' }}</span>
                </div>
            </div>
        </div>

        <div class="payments">
            <h3>Income Detail(s).</h3>

            @foreach($rows as $key => $row)
            <p style="margin-bottom: 12px; line-height: 1.6;">
                <strong>{{ $key + 1 }} . Amount of Rs. {{ number_format($row['amount'], 2) }}</strong>
                &nbsp;&nbsp; Collected with thanks, Dated:
                <strong>{{ \Carbon\Carbon::parse($voucher->entry_date)->format('l, d F, Y') }}</strong>
                on account of {{ $row['narration'] ?? 'N/A' }} from:
                <strong>{{ $row['party_name'] ?? '-' }} ({{ strtoupper($row['party_type']) }})</strong>
                @if($row['reference']) <em>[Ref: {{ $row['reference'] }}]</em> @endif
            </p>
            @endforeach

            <div class="amount-words">
                Amount in words: <strong id="amountInWords">{{ (int)$voucher->total_amount }}</strong> PKR Only
            </div>
        </div>

        <!-- summary -->
        <div class="summary">
            @php
            $currentBalance = $previousBalance + $voucher->total_amount;
            @endphp

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <td>Account Opening Balance.</td>
                    <td style="color:#6b0f8a;">>>> {{ number_format($previousBalance,2) }}</td>
                </tr>
                <tr>
                    <td>Total Income Collected. (+)</td>
                    <td style="color:#0b5a2b;">>>> {{ number_format($voucher->total_amount,2) }}</td>
                </tr>
                <tr>
                    <td>Account Net Balance.</td>
                    <td style="color:#0b5a2b; font-size: 16px;">>>> {{ number_format($currentBalance,2) }}</td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 30px; border: 1px solid #eee; padding: 10px; border-radius: 5px;">
            <div style="font-weight: 700; margin-bottom: 5px; color: var(--muted); font-size: 11px; text-transform: uppercase;">General Remarks:</div>
            <div style="font-weight: 600;">{{ $voucher->remarks ?: '--' }}</div>
        </div>

        <div class="footer" style="margin-top: 50px;">
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: 700;">Accountant Signature</div>
            </div>
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: 700;">Authorized By</div>
            </div>
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; padding-top: 5px; font-weight: 700;">Receiver's Signature</div>
            </div>
        </div>

        <div class="footer" style="margin-top: 30px; font-size: 10px; color: var(--muted); border-top: 1px solid #f0f0f0; padding-top: 10px;">
            <div>
                System Generated Report | Print Time: {{ now()->format('H:i:s') }} | Date: {{ now()->format('l, d F, Y') }}
            </div>
            <div class="thank">>>> Thank You for your business.</div>
        </div>
    </div>

    <script>
        function numberToWords(num) {
            const a = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if ((num = num.toString()).length > 9) return 'Overflow';
            let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
            if (!n) return;
            let str = '';
            str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
            str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
            str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
            str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
            str += (n[5] != 0) ? ((str != '') ? 'and ' : '') +
                (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) + ' ' : '';
            return str.trim();
        }

        document.addEventListener("DOMContentLoaded", function() {
            let amountText = document.getElementById("amountInWords").innerText;
            let amount = parseInt(amountText);
            if(!isNaN(amount)) {
                let words = numberToWords(amount);
                document.getElementById("amountInWords").innerText = words;
            }
        });
    </script>
</body>
</html>
