<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Claim Entry Report (Claim Type Wise)</title>
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
        .date-range span { text-decoration: underline; }
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
        .type-heading-row td {
            background-color: #e3f2fd;
            border: 1px solid #000;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 13px;
            color: #0d47a1;
        }
        .total-row td {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .val-box { background-color: #fff; text-align: right; }
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
    </style>
</head>
<body>

<div class="no-print" style="padding:8px;background:#f8f9fa;border-bottom:1px solid #ddd;margin-bottom:15px;text-align:center;">
    <button onclick="window.print()" style="padding:6px 18px;font-weight:bold;cursor:pointer;background:#0d6efd;color:#fff;border:none;border-radius:4px;">Print Report</button>
    <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:6px 18px;margin-left:8px;cursor:pointer;background:#6c757d;color:#fff;border:none;border-radius:4px;">Close</button>
    <button onclick="exportReportToExcel()" style="padding:6px 18px;margin-left:8px;font-weight:bold;cursor:pointer;background:#2e7d32;color:#fff;border:none;border-radius:4px;"><i class="fa fa-file-excel-o"></i> Export to Excel</button>
    <button onclick="exportReportToPDF()" style="padding:6px 18px;margin-left:8px;font-weight:bold;cursor:pointer;background:#d32f2f;color:#fff;border:none;border-radius:4px;"><i class="fa fa-file-pdf-o"></i> Export to PDF</button>

    <form id="pdfExportForm" method="POST" action="{{ route('reports.claim.pdf') }}" target="_blank" style="display:none;">
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

<div class="report-header">
    <h1 class="report-title">Claim Entry Report (Claim Type Wise)</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table id="claimReportTable">
    <thead>
        <tr>
            <th width="4%">S#</th>
            <th width="8%">ID</th>
            <th width="8%">Date</th>
            <th width="8%">Item ID</th>
            <th width="18%">Item Description</th>
            <th width="14%">Party</th>
            <th width="10%">Fault Found</th>
            <th width="8%">Mfg Date</th>
            <th width="8%">Card No.</th>
            <th width="6%">Qty</th>
            <th width="12%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php 
            $grand_qty = 0;
            $grand_amount = 0; 
        @endphp

        @if($grouped->isEmpty())
            <tr>
                <td colspan="11" style="text-align: center; padding: 50px;">No Data Found</td>
            </tr>
        @endif

        @foreach($grouped as $claimType => $items)
            @php
                $first = $items->first();
                $typeName = strtoupper($first->claim_type_label);
                $type_qty = $items->count();
                $type_total = 0;
                $sn = 0;
            @endphp

            <tr class="type-heading-row">
                <td colspan="11" class="text-left">{{ $typeName }}</td>
            </tr>

            @foreach($items as $claim)
                @php
                    $sn++;
                    $amount = $claim->report_amount;
                    $type_total += $amount;
                    $displayId = preg_replace('/[^0-9]/', '', $claim->claim_no) ?: $claim->id;
                @endphp
                <tr>
                    <td class="text-center">{{ $sn }}</td>
                    <td class="text-center">{{ $displayId }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-m-y') }}</td>
                    <td class="text-center">{{ $claim->product_id }}</td>
                    <td class="text-left">{{ $claim->product->name ?? 'N/A' }}</td>
                    <td class="text-left">{{ $claim->party_name }}</td>
                    <td class="text-left">{{ $claim->fault_found ?: '-' }}</td>
                    <td class="text-center">{{ $claim->mfg_date ?: '-' }}</td>
                    <td class="text-center">{{ $claim->card_no ?: '-' }}</td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="9" class="text-right">{{ $typeName }} Total:</td>
                <td class="text-center">{{ number_format($type_qty) }}</td>
                <td class="val-box">{{ number_format($type_total, 2) }}</td>
            </tr>
            <tr style="height: 20px;"><td colspan="11" style="border:none;"></td></tr>

            @php 
                $grand_qty += $type_qty;
                $grand_amount += $type_total; 
            @endphp
        @endforeach

        <tr class="grand-total-row">
            <td colspan="9" class="text-right">Grand Total:</td>
            <td class="text-center" style="background-color: #cfd8dc;">{{ number_format($grand_qty) }}</td>
            <td class="val-box" style="background-color: #cfd8dc;">{{ number_format($grand_amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    <div>{{ now()->format('l, F d, Y') }}</div>
    <div>Generated by ERP System</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    function exportReportToExcel() {
        var table = document.getElementById("claimReportTable");
        if (!table) return;
        var wb = XLSX.utils.table_to_book(table, {sheet: "Claim Type Wise Claim"});
        var fileName = "Claim_Report_Claim_Type_Wise_{{ $from_date }}_to_{{ $to_date }}.xlsx";
        XLSX.writeFile(wb, fileName);
    }
    function exportReportToPDF() {
        var form = document.getElementById("pdfExportForm");
        if (form) {
            form.submit();
        } else {
            window.location.href = "{{ route('reports.claim.pdf') }}" + window.location.search;
        }
    }
</script>
</body>
</html>
