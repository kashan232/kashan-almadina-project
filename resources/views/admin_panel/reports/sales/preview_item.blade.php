<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Sales Note Report (Item Wise)</title>
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
            margin-bottom: 15px;
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
            margin-bottom: 15px;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 4px 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        td {
            border: 1px solid #777;
            padding: 3px 5px;
            vertical-align: middle;
        }

        /* Item Header Row (Product Name & Brand) */
        .item-row-header td {
            background-color: #fff;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 12px;
            padding: 4px 6px;
        }
        .item-title {
            color: #000;
            font-weight: bold;
        }
        .brand-title {
            color: #0d47a1;
            font-weight: bold;
        }

        /* Subtotal Row */
        .subtotal-row td {
            font-weight: bold;
            padding: 4px 5px;
            border: 1px solid #000;
        }
        .qty-box {
            background-color: #e0e0e0;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000 !important;
        }
        .val-box {
            background-color: #e0e0e0;
            text-align: right;
            font-weight: bold;
            border: 1px solid #000 !important;
        }

        /* Grand Total Row */
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 6px 5px;
            border: 2px solid #000;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .footer {
            margin-top: 30px;
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
        @include('admin_panel.reports.sales.partials.report_line_styles')
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
            <h1 class="report-title">Sales Note Report (Item Wise)</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="7%">GSN No.</th>
                    <th width="8%">Date</th>
                    <th width="27%" class="text-left">Party Name</th>
                    <th width="5%">Qty</th>
                    <th width="9%">Retail Price</th>
                    <th width="10%">Retail Amount</th>
                    <th width="9%">Sales Price</th>
                    <th width="10%">Sales Amount</th>
                    <th width="7%">Add. Disc</th>
                    <th width="10%">Invoice Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grand_qty = 0; 
                    $grand_retail_amt = 0; 
                    $grand_sales_amt = 0; 
                    $grand_add_disc = 0;
                    $grand_invoice_amt = 0; 
                @endphp

                @if($grouped->isEmpty())
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">No Data Found</td>
                    </tr>
                @endif

                @foreach($grouped as $productId => $items)
                    @php
                        $product = $items->first()->product;
                        $brandName = $product && $product->brandRelation ? $product->brandRelation->name : '-';
                        $item_qty = 0;
                        $item_retail_amt = 0;
                        $item_sales_amt = 0;
                        $item_add_disc = 0;
                        $item_invoice_amt = 0;
                    @endphp
                    
                    <!-- Item Header Block (Product Name & Brand Name) -->
                    <tr class="item-row-header">
                        <td colspan="3" class="text-left">
                            <span class="item-title">{{ $product ? strtoupper($product->name) : 'N/A' }}</span>
                        </td>
                        <td colspan="7" class="text-left">
                            <span class="brand-title">{{ strtoupper($brandName) }}</span>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    @foreach($items as $item)
                        @php
                            $qty = $item->sales_qty;
                            $retail_p = $item->retail_price ?? 0;
                            $retail_a = $retail_p * $qty;
                            
                            $sales_p = $item->sales_rate > 0 ? $item->sales_rate : ($item->sales_qty != 0 ? ($item->sales_price - (($item->discount_amount ?? 0) / $item->sales_qty)) : $item->sales_price);
                            $sales_a = $item->amount;
                            $add_disc = 0; // Header discount level if explicitly present
                            $invoice_a = $sales_a;

                            $sale = $item->sale;
                            $invNo = $sale->invoice_no ?? '-';
                            $saleDate = \Carbon\Carbon::parse($sale->created_at)->format('d-m-y');
                            $customerObj = $sale->customer;
                            $partyName = $customerObj ? ($customerObj->customer_name ?? $customerObj->name ?? 'CASH CUSTOMER') : 'CASH CUSTOMER';

                            $item_qty += $qty;
                            $item_retail_amt += $retail_a;
                            $item_sales_amt += $sales_a;
                            $item_add_disc += $add_disc;
                            $item_invoice_amt += $invoice_a;
                        @endphp
                        <tr class="item-row @include('admin_panel.reports.sales.partials.data_row_class', ['item' => $item])">
                            <td class="text-center">{{ $invNo }}</td>
                            <td class="text-center">{{ $saleDate }}</td>
                            <td class="text-left">{{ strtoupper($partyName) }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ $retail_p != 0 ? number_format($retail_p, 0) : '' }}</td>
                            <td class="text-right fw-bold">{{ $retail_a != 0 ? number_format($retail_a, 0) : '' }}</td>
                            <td class="text-right">{{ $sales_p != 0 ? number_format($sales_p, 0) : '' }}</td>
                            <td class="text-right fw-bold">{{ $sales_a != 0 ? number_format($sales_a, 0) : '' }}</td>
                            <td class="text-right"></td>
                            <td class="text-right fw-bold">{{ $invoice_a != 0 ? number_format($invoice_a, 0) : '' }}</td>
                        </tr>
                    @endforeach

                    <!-- Item Total Row -->
                    <tr class="subtotal-row">
                        <td colspan="3" class="text-right"><b>Total:</b></td>
                        <td class="qty-box"><b>{{ number_format($item_qty) }}</b></td>
                        <td></td>
                        <td class="val-box"><b>{{ number_format($item_retail_amt, 0) }}</b></td>
                        <td></td>
                        <td class="val-box"><b>{{ number_format($item_sales_amt, 0) }}</b></td>
                        <td></td>
                        <td class="val-box"><b>{{ number_format($item_invoice_amt, 0) }}</b></td>
                    </tr>
                    <tr style="height: 6px;"><td colspan="10" style="border:none; padding: 0;"></td></tr>

                    @php
                        $grand_qty += $item_qty;
                        $grand_retail_amt += $item_retail_amt;
                        $grand_sales_amt += $item_sales_amt;
                        $grand_add_disc += $item_add_disc;
                        $grand_invoice_amt += $item_invoice_amt;
                    @endphp
                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="3" class="text-right">Grand Total:</td>
                    <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                    <td></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_retail_amt, 0) }}</td>
                    <td></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_sales_amt, 0) }}</td>
                    <td></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_invoice_amt, 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <div>{{ now()->format('l, F d, Y') }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>

    <!-- SheetJS for Export to Excel -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.min.js"></script>
    <!-- html2pdf.js for Vector Clean PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function exportReportToExcel() {
            var table = document.getElementById("salesReportTable");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Sales Report Item Wise"});
            XLSX.writeFile(wb, "Sales_Report_Item_Wise_" + "{{ date('Y-m-d') }}" + ".xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [3, 3, 3, 3],
                filename:     "Sales_Report_Item_Wise_" + "{{ date('Y-m-d') }}" + ".pdf",
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
