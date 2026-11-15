<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Report — Hold Qty Only</title>
    <style>
        @page { size: a5; margin: 4mm 3mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 1mm;
            background: #fff;
        }
        .no-print {
            padding: 8px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 8px;
        }
        .print-sheet {
            width: 148mm;
            max-width: 100%;
            margin: 10px auto;
            display: block;
            background: #fff;
            padding: 0 1mm;
        }
        .sheet-blank { display: none !important; }
        .report-sheet {
            width: 100%;
            display: block;
            padding: 0;
            overflow: visible;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 1px;
            line-height: 1.1;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 2px;
            line-height: 1.15;
        }
        .report-title {
            color: #0d47a1;
            font-size: 10px;
            font-weight: bold;
            margin: 0;
        }
        .report-type {
            font-size: 10px;
            font-weight: bold;
            text-decoration: underline;
            color: #bf360c;
        }
        .date-range { font-size: 9px; font-weight: bold; }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 8px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
            margin-bottom: 4px;
        }
        th {
            background: #cfd8dc;
            border: 1px solid #000;
            padding: 2px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        th.hold-col {
            background: #e65100 !important;
            color: #fff !important;
            width: 18%;
        }
        td {
            border: 1px solid #666;
            padding: 1px 3px;
            font-size: 10px;
        }
        td.hold-col {
            background: #fff3e0;
            color: #bf360c;
            font-weight: 600;
            text-align: right;
        }
        .item-name {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sno { text-align: center; width: 8%; }
        .brand-title td {
            background: #eceff1;
            font-weight: bold;
            font-size: 10px;
            padding: 2px 4px;
            color: #0d47a1;
        }
        .subtotal-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .subtotal-row .hold-col {
            background: #ffe0b2 !important;
        }
        .grand-row td {
            font-weight: bold;
            background: #cfd8dc;
            border-top: 2px solid #000;
        }
        .grand-row .hold-col {
            background: #ffcc80 !important;
        }
        .empty-msg {
            text-align: center;
            padding: 20px;
            font-size: 10px;
        }
        @media print {
            @page {
                size: a5;
                margin: 4mm 3mm 4mm 3mm;
            }
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .no-print { display: none !important; }
            .print-sheet {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }
            .sheet-blank { display: none !important; }
            .report-sheet {
                width: 100% !important;
                max-width: 100% !important;
                flex: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                overflow: visible !important;
            }
            table {
                width: 100% !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                page-break-inside: auto;
                break-inside: auto;
            }
            thead {
                display: table-header-group !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding:8px;background:#f8f9fa;border-bottom:1px solid #ddd;margin-bottom:8px;text-align:center;">
        <button onclick="window.print()" style="padding:6px 18px;font-weight:bold;cursor:pointer;background:#0d6efd;color:#fff;border:none;border-radius:4px;">Print Report</button>
        <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:6px 18px;margin-left:8px;cursor:pointer;background:#6c757d;color:#fff;border:none;border-radius:4px;">Close</button>
        <button onclick="exportReportToExcel()" style="padding:6px 18px;margin-left:8px;font-weight:bold;cursor:pointer;background:#2e7d32;color:#fff;border:none;border-radius:4px;"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
        <button onclick="exportReportToPDF()" style="padding:6px 18px;margin-left:8px;font-weight:bold;cursor:pointer;background:#d32f2f;color:#fff;border:none;border-radius:4px;"><i class="fa fa-file-pdf-o"></i> Export to PDF</button>

        <form id="pdfExportForm" method="POST" action="{{ route('reports.stock.pdf') }}" target="_blank" style="display:none;">
            @csrf
            @foreach(request()->all() as $name => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $name }}[]" value="{{ $v }}">
                    @endforeach
                @elseif($name !== '_token')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
    </div>

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <div class="print-sheet">
        <div class="sheet-blank"></div>
        <div class="report-sheet">
            <div class="company-name">AL-MADINA TRADERS</div>
            <div class="report-header">
                <div class="generated-date">{{ $generated_at->format('l, M j, Y') }}</div>
                <div class="report-title">Stock Report</div>
                <div class="report-type">Hold Qty Only — Current Net Reserved (Brand Wise)</div>
                <div class="date-range">
                    From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
                    To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
                </div>
            </div>

            @forelse($groups as $group)
            <table class="stock-hold-table">
                <thead>
                    <tr>
                        <th class="sno">S#</th>
                        <th style="width:64%;">Item</th>
                        <th class="hold-col">Hold Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="brand-title">
                        <td colspan="3">{{ strtoupper($group['brand_name']) }}</td>
                    </tr>
                    @foreach($group['rows'] as $i => $row)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="item-name" title="{{ $row['product_name'] }}">{{ $row['product_name'] }}</td>
                        <td class="hold-col">{{ $fmt($row['hold_qty']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:right;">{{ strtoupper($group['brand_name']) }} Total:</td>
                        <td class="hold-col">{{ $fmt($group['totals']['hold_qty']) }}</td>
                    </tr>
                </tbody>
            </table>
            @empty
            <p class="empty-msg">No hold stock found for selected filters.</p>
            @endforelse

            @if(!empty($groups))
            <table class="grand-total-table">
                <tbody>
                    <tr class="grand-row">
                        <td class="sno"></td>
                        <td style="text-align:right;">Grand Total:</td>
                        <td class="hold-col">{{ $fmt($grand['hold_qty']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        function exportReportToExcel() {
            var tables = document.querySelectorAll("table.stock-hold-table, table.grand-total-table");
            if (tables.length === 0) return;
            var tempTable = document.createElement("table");
            tables.forEach(function(tbl) {
                var clone = tbl.cloneNode(true);
                Array.from(clone.querySelectorAll("tr")).forEach(function(row) {
                    tempTable.appendChild(row.cloneNode(true));
                });
            });
            var wb = XLSX.utils.table_to_book(tempTable, {sheet: "Hold Stock"});
            var fileName = "Stock_Report_Hold_{{ $from_date }}_to_{{ $to_date }}.xlsx";
            XLSX.writeFile(wb, fileName);
        }
        function exportReportToPDF() {
            var form = document.getElementById("pdfExportForm");
            if (form) {
                form.submit();
            } else {
                window.location.href = "{{ route('reports.stock.pdf') }}" + window.location.search;
            }
        }
    </script>
</body>
</html>
