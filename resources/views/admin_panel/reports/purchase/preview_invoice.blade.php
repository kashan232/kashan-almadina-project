<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Purchase Note Report (Invoice Wise)</title>
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
        .report-title {
            color: #c2185b;
            font-size: 22px;
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
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: middle;
        }

        /* Supplier Row */
        .customer-row {
            background-color: #e3f2fd;
            border-top: 2px solid #000;
        }
        .customer-row td {
            color: #0d47a1;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
        }

        /* Invoice Meta Row (Date/InvNo) */
        .inv-meta-row td {
            background-color: #fff;
            border-bottom: none;
            padding: 2px 6px;
            font-weight: bold;
        }

        /* Data Row */
        .item-row td {
            border-bottom: 1px solid #ccc;
        }
        .item-row .bold-val {
            font-weight: bold;
        }

        /* Subtotal Row */
        .subtotal-row td {
            font-weight: bold;
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

        /* Grand Total Row */
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 8px 6px;
            border-top: 2px solid #000;
        }
        .grand-qty-box {
            background-color: #cfd8dc;
            text-align: center;
            border: 1px solid #000 !important;
        }
        .grand-val-box {
            background-color: #bbdefb;
            text-align: right;
            border: 1px solid #000 !important;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-left; }

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
            <h1 class="report-title">Purchase Note Report (Invoice Wise)</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="8%">Type</th>
                    <th width="26%" class="text-left">Item Description</th>
                    <th width="10%">Brand</th>
                    <th width="5%">Qty</th>
                    <th width="9%">Retail Price</th>
                    <th width="11%">Retail Amount</th>
                    <th width="9%">Purchase Rate</th>
                    <th width="12%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grand_qty = 0; 
                    $grand_retail_amt = 0; 
                    $grand_purchase_amt = 0; 
                @endphp

                @foreach($invoices as $invoiceNo => $items)
                    @php
                        $firstItem = $items->first();
                        $purchase = $firstItem->purchase;
                        $party = $purchase->purchasable;
                        $partyName = $party ? ($party->name ?? $party->customer_name ?? 'VENDOR / SUPPLIER') : ($purchase->vendor->name ?? 'VENDOR / SUPPLIER');
                        $rawDate = !empty($purchase->entry_date) ? $purchase->entry_date : ($purchase->current_date ?? $purchase->created_at);
                        $purchaseDate = \Carbon\Carbon::parse($rawDate)->format('d-m-y');
                        
                        $inv_qty = 0;
                        $inv_retail_amt = 0;
                        $inv_purchase_amt = 0;
                    @endphp
                    
                    <!-- Supplier / Invoice Heading Row -->
                    <tr class="customer-row">
                        <td><b>Vendor</b></td>
                        <td colspan="3" class="text-left">
                            <b style="color: #000;">{{ strtoupper($partyName) }}</b>
                        </td>
                        @php
                            $displayInvNo = preg_match('/\d+/', $invoiceNo, $matches) ? ltrim($matches[0], '0') : $invoiceNo;
                            if (empty($displayInvNo) || $displayInvNo === '') { $displayInvNo = '0'; }
                            $displayInvNo = str_pad($displayInvNo, 3, '0', STR_PAD_LEFT);
                        @endphp
                        <td><b>Inv No.</b></td>
                        <td class="text-center"><b>{{ $displayInvNo }}</b></td>
                        <td><b>Date.</b></td>
                        <td class="text-center"><b>{{ $purchaseDate }}</b></td>
                    </tr>

                    <!-- Data Rows -->
                    @foreach($items as $item)
                        @php
                            $qty = $item->qty;
                            $retail_p = $item->purchase_retail_price ?? $item->retail_price ?? 0;
                            $retail_a = $retail_p * $qty;
                            $purchase_p = (float) $item->form_rate;
                            $purchase_a = (float) $item->form_line_total;

                            $inv_qty += $qty;
                            $inv_retail_amt += $retail_a;
                            $inv_purchase_amt += $purchase_a;
                        @endphp
                        <tr class="item-row {{ ($item->entry_type ?? 'purchase') !== 'purchase' ? 'return-row' : '' }}">
                            @include('admin_panel.reports.purchase.partials.type_cell', ['item' => $item])
                            <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                            <td class="text-center">{{ $item->product && $item->product->brandRelation ? $item->product->brandRelation->name : '-' }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                            <td class="text-right">{{ number_format($retail_a, 0) }}</td>
                            <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                            <td class="text-right bold-val">{{ number_format($purchase_a, 0) }}</td>
                        </tr>
                    @endforeach

                    <!-- Invoice Total Row -->
                    <tr class="subtotal-row">
                        <td colspan="3" class="text-right"><b>Total:</b></td>
                        <td class="qty-box"><b>{{ number_format($inv_qty) }}</b></td>
                        <td></td>
                        <td class="val-box"><b>{{ number_format($inv_retail_amt, 0) }}</b></td>
                        <td></td>
                        <td class="val-box"><b>{{ number_format($inv_purchase_amt, 0) }}</b></td>
                    </tr>
                    <tr style="height: 6px;"><td colspan="8" style="border:none; padding: 0;"></td></tr>

                    @php
                        $grand_qty += $inv_qty;
                        $grand_retail_amt += $inv_retail_amt;
                        $grand_purchase_amt += $inv_purchase_amt;
                    @endphp
                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="3" class="text-right">Grand Total:</td>
                    <td class="grand-qty-box">{{ number_format($grand_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="grand-val-box">{{ number_format($grand_retail_amt, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="grand-val-box">{{ number_format($grand_purchase_amt, 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div>{{ now()->format('l, F d, Y') }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>

    <!-- SheetJS for Export to Excel -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- html2pdf.js for Vector Clean PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("salesReportTable");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Invoice Wise Purchase Report"});
            XLSX.writeFile(wb, "Invoice_Wise_Purchase_Report.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Invoice_Wise_Purchase_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
