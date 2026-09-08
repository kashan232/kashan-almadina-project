<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Purchase Note Report (Item Wise)</title>
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
            color: #0d47a1;
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

        .item-heading-row td {
            background-color: #fff;
            border: none;
            padding: 10px 6px 5px 6px;
            font-weight: bold;
            font-size: 12px;
            color: #0d47a1;
        }

        .data-row td { border: 1px solid #999; }
        .bold-val { font-weight: bold; }

        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .qty-box {
            background-color: #c8e6c9;
            text-align: center;
        }
        .val-box {
            background-color: #fff;
            text-align: right;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            padding: 10px 6px;
            border-top: 2px solid #000;
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
        @include('admin_panel.reports.purchase.partials.report_line_styles')
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
        <button id="btnExportExcel" onclick="exportReportToExcel()" style="padding: 10px 25px; background: #2e7d32; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-excel-o"></i> Export to Excel
        </button>
        <button id="btnExportPDF" onclick="exportReportToPDF()" style="padding: 10px 25px; background: #0288d1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: 10px;">
            <i class="fa fa-file-pdf-o"></i> Export to PDF
        </button>
    </div>

    <div id="reportContainer" style="background: #fff; padding: 5px;">
        <div class="report-header">
            <h1 class="report-title">Purchase Note Report (Item Wise)</h1>
            <div class="date-range">
                From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span> 
                To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
            </div>
        </div>

        <table id="salesReportTable">
            <thead>
                <tr>
                    <th width="9%">PUR No.</th>
                    <th width="9%">Date</th>
                    <th width="10%">Type</th>
                    <th width="18%" class="text-left">Vendor Name</th>
                    <th width="7%">Qty</th>
                    <th width="11%">Retail Price</th>
                    <th width="12%">Retail Value</th>
                    <th width="11%">Purchase Rate</th>
                    <th width="13%">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $grand_qty = 0; 
                    $grand_purchase_amt = 0; 
                    $grand_retail_amt = 0;
                @endphp

                @if($grouped->isEmpty())
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 50px;">No Data Found</td>
                    </tr>
                @endif

                @foreach($grouped as $productId => $items)
                    @php
                        $product = $items->first()->product;
                        $item_qty = 0;
                        $item_purchase_amt = 0;
                        $item_retail_amt = 0;
                    @endphp
                    
                    <tr class="item-heading-row">
                        <td colspan="10" class="text-left" style="border-top: 2px solid #000;">
                            ITEM: {{ $product ? strtoupper($product->name) : 'N/A' }} 
                            <span class="ms-3" style="color: #666;">( {{ $product && $product->brandRelation ? strtoupper($product->brandRelation->name) : '-' }} )</span>
                        </td>
                    </tr>

                    @foreach($items as $item)
                        @php
                            $qty = $item->qty;
                            $purchase_p = $item->form_rate;
                            $purchase_a = $item->form_line_total;
                            $purchaseDate = \Carbon\Carbon::parse($item->purchase->current_date)->format('d-m-y');
                            $displayInv = preg_replace('/[^0-9]/', '', $item->purchase->invoice_no) ?: $item->purchase->invoice_no;
                            $party = $item->purchase->purchasable;
                            $partyName = $party
                                ? strtoupper($party->name ?? $party->customer_name ?? 'N/A')
                                : strtoupper($item->purchase->vendor->name ?? 'N/A');

                            $retail_p = $item->purchase_retail_price ?? $item->retail_price ?? 0;
                            $retail_a = $retail_p * $qty;

                            $item_qty += $qty;
                            $item_purchase_amt += $purchase_a;
                            $item_retail_amt += $retail_a;
                        @endphp
                        <tr class="data-row {{ ($item->entry_type ?? 'purchase') !== 'purchase' ? 'return-row' : '' }}">
                            <td class="text-center">{{ $displayInv }}</td>
                            <td class="text-center">{{ $purchaseDate }}</td>
                            @include('admin_panel.reports.purchase.partials.type_cell', ['item' => $item])
                            <td class="text-left">{{ $partyName }}</td>
                            <td class="text-center">{{ number_format($qty) }}</td>
                            <td class="text-right">{{ number_format($retail_p, 0) }}</td>
                            <td class="text-right">{{ number_format($retail_a, 0) }}</td>
                            <td class="text-right">{{ number_format($purchase_p, 0) }}</td>
                            <td class="text-right bold-val">{{ number_format($purchase_a, 0) }}</td>
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td colspan="4" class="text-right">Total:</td>
                        <td class="qty-box">{{ number_format($item_qty) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($item_retail_amt, 0) }}</td>
                        <td style="border:none; background:none;"></td>
                        <td class="val-box">{{ number_format($item_purchase_amt, 0) }}</td>
                    </tr>
                    <tr style="height: 25px;"><td colspan="10" style="border:none;"></td></tr>

                    @php
                        $grand_qty += $item_qty;
                        $grand_purchase_amt += $item_purchase_amt;
                        $grand_retail_amt += $item_retail_amt;
                    @endphp
                @endforeach

                <tr class="grand-total-row">
                    <td colspan="4" class="text-right">Grand Total:</td>
                    <td class="qty-box" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #fce4ec;">{{ number_format($grand_retail_amt, 0) }}</td>
                    <td style="border:none; background:none;"></td>
                    <td class="val-box" style="background-color: #bbdefb;">{{ number_format($grand_purchase_amt, 0) }}</td>
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
            var wb = XLSX.utils.table_to_book(table, {sheet: "Item Wise Purchase Report"});
            XLSX.writeFile(wb, "Item_Wise_Purchase_Report.xlsx");
        }

        function exportReportToPDF() {
            var element = document.getElementById("reportContainer");
            var opt = {
                margin:       [5, 5, 5, 5],
                filename:     'Item_Wise_Purchase_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>
