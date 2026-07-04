<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Entry Report (Brand Wise)</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 15px;
        }
        .company-name {
            color: #7b1fa2;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .report-title {
            color: #0d47a1;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
        }
        .date-range span { text-decoration: underline; }
        .gen-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .brand-heading-row td {
            border: none;
            border-bottom: 1px solid #000;
            padding: 10px 6px 4px 6px;
            font-weight: bold;
            font-size: 13px;
            text-decoration: underline;
        }
        .item-total-row td {
            font-weight: bold;
            background-color: #e3f2fd;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .brand-total-row td {
            font-weight: bold;
            background-color: #bbdefb;
            border: 1px solid #000;
            padding: 6px 6px;
            font-size: 12px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
</div>

<div class="report-header">
    <div class="gen-date">{{ now()->format('l, F d, Y') }}</div>
    <div style="text-align:center;margin-bottom:6px;"><x-amt-logo width="110px" style="margin:0 auto;" /></div>
    <div class="company-name">Al-Madina Traders</div>
    <h1 class="report-title">Claim Entry Report</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="10%">CLM #</th>
            <th width="10%">Date</th>
            <th width="22%">Item</th>
            <th width="28%">Customer Name</th>
            <th width="15%">Mfg Date</th>
            <th width="15%">Card No.</th>
        </tr>
    </thead>
    <tbody>
        @if($grouped->isEmpty())
            <tr>
                <td colspan="6" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $brandName => $itemGroups)
            @php $brand_count = 0; @endphp

            <tr class="brand-heading-row">
                <td colspan="6" class="text-left">{{ strtoupper($brandName) }}</td>
            </tr>

            @foreach($itemGroups as $productId => $claims)
                @php
                    $product = $claims->first()->product;
                    $itemName = $product->name ?? 'N/A';
                    $item_count = $claims->count();
                    $brand_count += $item_count;
                @endphp

                @foreach($claims as $claim)
                    @php
                        $displayId = preg_replace('/[^0-9]/', '', $claim->claim_no) ?: $claim->id;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $displayId }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-m-y') }}</td>
                        <td class="text-left">{{ $itemName }}</td>
                        <td class="text-left">{{ $claim->party_name }}</td>
                        <td class="text-center">{{ $claim->mfg_date ?: '-' }}</td>
                        <td class="text-center">{{ $claim->card_no ?: '-' }}</td>
                    </tr>
                @endforeach

                <tr class="item-total-row">
                    <td colspan="5" class="text-right">Total. {{ $itemName }}</td>
                    <td class="text-center">{{ $item_count }}</td>
                </tr>
            @endforeach

            <tr class="brand-total-row">
                <td colspan="5" class="text-right">{{ $brandName }} Total. >>></td>
                <td class="text-center">{{ $brand_count }}</td>
            </tr>
            <tr style="height: 15px;"><td colspan="6" style="border:none;"></td></tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <div></div>
    <div>Page 1 of 1</div>
</div>

</body>
</html>
