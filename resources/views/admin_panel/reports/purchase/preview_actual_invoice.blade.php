<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Purchase Note Report (Actual Invoice)</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 5mm;
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
        .report-title {
            color: #c2185b;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .date-range span {
            text-decoration: underline;
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
            padding: 5px 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .party-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 4px 4px 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .party-name {
            color: #0d47a1;
            font-weight: bold;
        }

        .meta-row td {
            background-color: #fff;
            border-top: none;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        .data-row td {
            border: 1px solid #999;
        }
        .alloc-row td {
            background-color: #fff;
            border: 1px solid #999;
            font-weight: bold;
        }
        .bold-val {
            font-weight: bold;
        }

        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #c8e6c9;
            text-align: center;
            border: 1px solid #000 !important;
        }
        .val-box {
            background-color: #fff;
            text-align: right;
            border: 1px solid #000 !important;
        }
        .sales-amt-box {
            background-color: #cfd8dc;
            text-align: right;
            border: 1px solid #000 !important;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border: 2px solid #000;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 25px; background: #c2185b; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
        <button id="btnExportExcel" onclick="exportReportToExcel()" style="padding: 10px 25px; background: #2e7d32; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-excel-o"></i> Export to Excel
        </button>
        <button id="btnExportPDF" onclick="exportReportToPDF()" style="padding: 10px 25px; background: #0288d1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-pdf-o"></i> Export to PDF
        </button>
    </div>

    <div id="reportContainer" style="background: #fff; padding: 5px;">
        <div class="report-header">
            <h1 class="report-title">Purchase Note Report (Actual Invoice)</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="28%" class="text-left">Item Description</th>
                    <th width="10%">Brand</th>
                    <th width="6%">Qty</th>
                    <th width="9%">Retail Price</th>
                    <th width="11%">Retail Amount</th>
                    <th width="9%">Purchase Price</th>
                    <th width="11%">Purchase Amount</th>
                    <th width="7%">Add. Disc</th>
                    <th width="9%">Invoice Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grand_qty = 0; 
                    $grand_retail_amt = 0; 
                    $grand_purchase_amt = 0; 
                    $grand_invoice_amt = 0; 
                @endphp

                @if($purchases->isEmpty())
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">No Data Found</td>
                    </tr>
                @endif

                @foreach($purchases as $p)
                    @php
                        $party = $p->purchasable ?? $p->vendor;
                        $partyName = $party ? ($party->name ?? $party->customer_name ?? 'VENDOR / SUPPLIER') : 'VENDOR / SUPPLIER';

                        $invoiceNo = $p->invoice_no ?? '-';
                        $displayInvNo = preg_match('/\d+/', $invoiceNo, $matches) ? ltrim($matches[0], '0') : $invoiceNo;
                        if (empty($displayInvNo) || $displayInvNo === '') { $displayInvNo = '0'; }

                        $rawDate = !empty($p->entry_date) ? $p->entry_date : ($p->current_date ?? $p->created_at);
                        $purchaseDate = \Carbon\Carbon::parse($rawDate)->format('d-m-y');

                        $inv_qty = 0;
                        $inv_retail_amt = 0;
                        $inv_purchase_amt = 0;

                        $whtAmt = (float) ($p->wht ?? 0);
                        $discAmt = (float) ($p->discount ?? 0);
                    @endphp

                    <!-- Main Supplier & Header Bar Row -->
                    <tr class="party-heading-row">
                        <td colspan="5" class="text-left">
                            <span class="party-name">{{ strtoupper($partyName) }}</span>
                        </td>
                        <td colspan="2" class="text-right">Date.&nbsp;&nbsp;<b>{{ $purchaseDate }}</b></td>
                        <td colspan="2" class="text-right">Inv. No.&nbsp;&nbsp;<b>{{ $displayInvNo }}</b></td>
                    </tr>

                    <!-- Optional WHT Row -->
                    @if($whtAmt > 0)
                        <tr class="alloc-row">
                            <td class="text-left" style="color: #0d47a1;">WHT :</td>
                            <td colspan="5"></td>
                            <td class="text-right bold-val">{{ number_format($whtAmt, 0) }}</td>
                            <td></td>
                            <td class="text-right bold-val">{{ number_format($whtAmt, 0) }}</td>
                        </tr>
                    @endif

                    <!-- Optional Discount Received Row -->
                    @if($discAmt > 0)
                        <tr class="alloc-row">
                            <td class="text-left" style="color: #0d47a1;">Discount Received:</td>
                            <td colspan="5"></td>
                            <td class="text-right bold-val">-{{ number_format($discAmt, 0) }}</td>
                            <td></td>
                            <td class="text-right bold-val">-{{ number_format($discAmt, 0) }}</td>
                        </tr>
                    @endif

                    <!-- Account Allocations Rows (Subhead) -->
                    @foreach($p->accountAllocations as $alloc)
                        @php
                            $allocTitle = $alloc->account ? $alloc->account->title : ($alloc->head ? $alloc->head->name : 'Account Allocation');
                            $allocAmt = (float) $alloc->amount;
                        @endphp
                        <tr class="alloc-row">
                            <td class="text-left" style="color: #0d47a1;">{{ $allocTitle }}:</td>
                            <td colspan="5"></td>
                            <td class="text-right bold-val">{{ number_format($allocAmt, 0) }}</td>
                            <td></td>
                            <td class="text-right bold-val">{{ number_format($allocAmt, 0) }}</td>
                        </tr>
                    @endforeach

                    <!-- Product Items Rows -->
                    @foreach($p->items as $item)
                        @php
                            $qty = (float) $item->qty;
                            $retail_p = (float) ($item->purchase_retail_price ?? $item->retail_price ?? 0);
                            $retail_a = $retail_p * $qty;
                            $purchase_p = (float) ($item->purchase_rate > 0 ? $item->purchase_rate : $item->price);
                            $lineTotal = (float) ($item->line_total > 0 ? $item->line_total : ($purchase_p * $qty));

                            $inv_qty += $qty;
                            $inv_retail_amt += $retail_a;
                            $inv_purchase_amt += $lineTotal;
                        @endphp
                        <tr class="data-row">
                            <td class="text-left">{{ $item->product ? $item->product->name : 'N/A' }}</td>
                            <td class="text-center">{{ $item->product && $item->product->brandRelation ? $item->product->brandRelation->name : '-' }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ $retail_p > 0 ? number_format($retail_p, 0) : '' }}</td>
                            <td class="text-right bold-val">{{ $retail_a > 0 ? number_format($retail_a, 0) : '' }}</td>
                            <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                            <td class="text-right bold-val">{{ number_format($lineTotal, 0) }}</td>
                            <td class="text-right"></td>
                            <td class="text-right bold-val">{{ number_format($lineTotal, 0) }}</td>
                        </tr>
                    @endforeach

                    @php
                        $netInvTotal = $inv_purchase_amt + $whtAmt - $discAmt + $p->accountAllocations->sum('amount');

                        $grand_qty += $inv_qty;
                        $grand_retail_amt += $inv_retail_amt;
                        $grand_purchase_amt += $inv_purchase_amt;
                        $grand_invoice_amt += $netInvTotal;
                    @endphp

                    <!-- Invoice Total Row -->
                    <tr class="total-row">
                        <td colspan="2" class="text-right">{{ $purchaseDate }}&nbsp;&nbsp;&nbsp;&nbsp;Total:</td>
                        <td class="qty-box">{{ number_format($inv_qty) }}</td>
                        <td colspan="1" style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($inv_retail_amt, 0) }}</td>
                        <td colspan="1" style="border:none; background:none;"></td>
                        <td class="sales-amt-box">{{ number_format($inv_purchase_amt, 0) }}</td>
                        <td colspan="1" style="border:none; background:none;"></td>
                        <td class="sales-amt-box">{{ number_format($netInvTotal, 0) }}</td>
                    </tr>
                    <tr style="height: 15px;"><td colspan="9" style="border:none;"></td></tr>

                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="2" class="text-right">Grand Total:</td>
                    <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                    <td colspan="1" style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_retail_amt, 0) }}</td>
                    <td colspan="1" style="border:none; background:none;"></td>
                    <td class="sales-amt-box" style="background-color: #bbdefb;">{{ number_format($grand_purchase_amt, 0) }}</td>
                    <td colspan="1" style="border:none; background:none;"></td>
                    <td class="sales-amt-box" style="background-color: #bbdefb;">{{ number_format($grand_invoice_amt, 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- html2pdf for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function exportReportToExcel() {
            var table = document.getElementById("salesReportTable");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Actual Invoice Purchase Report"});
            XLSX.writeFile(wb, "Actual_Invoice_Purchase_Report.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Actual_Invoice_Purchase_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
