<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjustment Voucher - {{ $voucher->avid }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #333; margin: 0; padding: 20px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; box-sizing: border-box; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; position: relative; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 22px; letter-spacing: 2px; }
        .header .ivid { position: absolute; top: 0; right: 0; font-weight: bold; font-size: 16px; border: 1px solid #333; padding: 5px 10px; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 120px; }
        .info-table .value { border-bottom: 1px dotted #999; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #333; padding: 8px; text-align: center; }
        .items-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        .items-table .text-left { text-align: left; }
        .items-table .text-right { text-align: right; }

        .footer { margin-top: 30px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { width: 33%; text-align: center; padding-top: 50px; }
        .footer-table .sig-line { border-top: 1px solid #333; display: inline-block; width: 80%; padding-top: 5px; font-weight: bold; font-size: 11px; }

        .remarks { margin-top: 20px; font-style: italic; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 100px; color: rgba(0,0,0,0.05); font-weight: bold; pointer-events: none; text-transform: uppercase; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #333; color: #white; border: none; border-radius: 4px; font-weight: bold; color:white;">PRINT NOW</button>
    </div>

    <div class="container">
        @if($voucher->status == 'posted') <div class="watermark">POSTED</div> @endif

        <div class="header">
            <h2>Adjustment Voucher</h2>
            <div class="ivid">{{ $voucher->avid }}</div>
            <div style="margin-top: 5px; font-weight: bold;">AL-MADINA BATTERY</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Party Type:</td>
                <td class="value">{{ $party->head_name ?? (is_numeric($voucher->party_type) ? "Account Head" : ucfirst($voucher->party_type)) }}</td>
                <td class="label">Date:</td>
                <td class="value">{{ date('d-M-Y', strtotime($voucher->entry_date)) }}</td>
            </tr>
            <tr>
                <td class="label">Party Name:</td>
                <td class="value">{{ $party->name ?? '-' }}</td>
                <td class="label">Reference:</td>
                <td class="value">-</td>
            </tr>
            <tr>
                <td class="label">Code / ID:</td>
                <td class="value">{{ $party->phone ?? $party->id ?? '-' }}</td>
                <td class="label">Prev Balance:</td>
                <td class="value">{{ number_format($previousBalance, 2) }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%" class="text-left">Narration</th>
                    <th width="30%" class="text-left">Account (Deposit To)</th>
                    <th width="15%">Ref#</th>
                    <th width="15%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left">{{ $row['narration'] }}</td>
                    <td class="text-left">
                        <div style="font-weight: bold;">{{ $row['account_name'] }}</div>
                        <div style="font-size: 10px; color: #666;">Head: {{ $row['head_name'] }}</div>
                    </td>
                    <td>{{ $row['reference'] }}</td>
                    <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #f9f9f9;">
                    <td colspan="4" class="text-right">GRAND TOTAL</td>
                    <td class="text-right">{{ number_format($voucher->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($voucher->remarks)
        <div class="remarks">
            <strong>Remarks:</strong><br>
            {{ $voucher->remarks }}
        </div>
        @endif

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td><span class="sig-line">Prepared By</span></td>
                    <td><span class="sig-line">Checked By</span></td>
                    <td><span class="sig-line">Approved By</span></td>
                </tr>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px;">
            Printed on {{ date('d-M-Y h:i A') }} | Powered by Antigravity AI
        </div>
    </div>
</body>
</html>
