<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Purchase Report (All)</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 0 5px;
        }
        .report-title {
            color: #c2185b;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }
        .date-range {
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }
        .date-range span {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background-color: #90caf9;
            color: #000;
            border: 1px solid #000;
            padding: 6px 3px;
            font-size: 10.5px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 5px;
            vertical-align: middle;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        /* Total Row */
        .total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 6px 6px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .total-label {
            text-align: right;
            font-weight: bold;
        }
        .total-val-box {
            background-color: #90caf9;
            text-align: right;
            border: 1px solid #000 !important;
            font-weight: bold;
        }
        .total-qty-box {
            background-color: #90caf9;
            text-align: center;
            border: 1px solid #000 !important;
            font-weight: bold;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
        @include('admin_panel.reports.purchase.partials.report_line_styles')
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
            <h1 class="report-title">Purchase Report (All)</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="5%">Type</th>
                    <th width="6%">Inv #</th>
                    <th width="8.5%">Date</th>
                    <th width="16%" class="text-left">Party</th>
                    <th width="16%" class="text-left">Item Description</th>
                    <th width="8.5%">Brand</th>
                    <th width="5%">Qty</th>
                    <th width="7%">Retail Price</th>
                    <th width="10.5%">Retail Amount</th>
                    <th width="7%">Purchase Rate</th>
                    <th width="10.5%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grand_qty = 0; 
                    $grand_retail_amt = 0; 
                    $grand_amount = 0; 
                @endphp

                @foreach($purchaseItems as $item)
                    @php
                        $purchase = $item->purchase;
                        $party = is_object($purchase) ? ($purchase->purchasable ?? $purchase->vendor ?? null) : null;
                        $partyName = $party ? ($party->name ?? $party->customer_name ?? 'VENDOR / SUPPLIER') : 'VENDOR / SUPPLIER';

                        $createdVal = is_object($purchase) && isset($purchase->created_at) ? $purchase->created_at : null;
                        $entryVal = is_object($purchase) && isset($purchase->entry_date) ? $purchase->entry_date : null;
                        $currVal = is_object($purchase) && isset($purchase->current_date) ? $purchase->current_date : null;
                        $rawDate = !empty($entryVal) ? $entryVal : (!empty($currVal) ? $currVal : ($createdVal ? \Carbon\Carbon::parse($createdVal)->toDateString() : now()->toDateString()));
                        $purchaseDate = \Carbon\Carbon::parse($rawDate)->format('d-m-y');

                        $invoiceNo = is_object($purchase) ? ($purchase->invoice_no ?? '') : '';
                        $displayInvNo = preg_match('/\d+/', $invoiceNo, $matches) ? ltrim($matches[0], '0') : $invoiceNo;
                        if (empty($displayInvNo) || $displayInvNo === '') { $displayInvNo = '0'; }

                        $qty = (float) ($item->qty ?? $item->sales_qty ?? 0);
                        $retail_p = (float) ($item->purchase_retail_price ?? $item->retail_price ?? 0);
                        $retail_a = $retail_p * $qty;
                        $purchase_p = (float) ($item->form_rate ?? 0);
                        $amount = (float) ($item->form_line_total ?? $item->amount ?? 0);

                        $grand_qty += $qty;
                        $grand_retail_amt += $retail_a;
                        $grand_amount += $amount;
                    @endphp
                    <tr class="item-row {{ ($item->entry_type ?? 'purchase') !== 'purchase' ? 'return-row' : '' }}">
                        @include('admin_panel.reports.purchase.partials.type_cell', ['item' => $item])
                        <td class="text-center">{{ $displayInvNo }}</td>
                        <td class="text-center">{{ $purchaseDate }}</td>
                        <td class="text-left">{{ strtoupper($partyName) }}</td>
                        <td class="text-left">{{ $item->product ? $item->product->name : 'N/A' }}</td>
                        <td class="text-center">{{ $item->product && $item->product->brandRelation ? $item->product->brandRelation->name : '-' }}</td>
                        <td class="text-center">{{ $qty < 0 ? '('.number_format(abs($qty)).')' : number_format($qty) }}</td>
                        <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                        <td class="text-right">{{ $retail_a < 0 ? '('.number_format(abs($retail_a), 0).')' : number_format($retail_a, 0) }}</td>
                        <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                        <td class="text-right">{{ $amount < 0 ? '('.number_format(abs($amount), 0).')' : number_format($amount, 0) }}</td>
                    </tr>
                @endforeach

                <!-- Grand Total Row -->
                <tr class="total-row">
                    <td colspan="6" class="total-label">Total:</td>
                    <td class="total-qty-box">{{ $grand_qty < 0 ? '('.number_format(abs($grand_qty)).')' : number_format($grand_qty) }}</td>
                    <td></td>
                    <td class="total-val-box">{{ $grand_retail_amt < 0 ? '('.number_format(abs($grand_retail_amt), 0).')' : number_format($grand_retail_amt, 0) }}</td>
                    <td></td>
                    <td class="total-val-box">{{ $grand_amount < 0 ? '('.number_format(abs($grand_amount), 0).')' : number_format($grand_amount, 0) }}</td>
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
            var wb = XLSX.utils.table_to_book(table, {sheet: "Purchase Report All"});
            XLSX.writeFile(wb, "Purchase_Report_All.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Purchase_Report_All.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
