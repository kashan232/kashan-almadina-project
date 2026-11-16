<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Claim Entry Report (Claim Wise)</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 10mm; }
        .no-print { padding: 10px; background: #f8f9fa; border-bottom: 1px solid #ddd; text-align: center; margin-bottom: 20px; }
        .report-header { text-align: center; position: relative; margin-bottom: 15px; }
        .report-title { color: #0d47a1; font-size: 22px; font-weight: bold; text-decoration: underline; margin: 0; display: inline-block; }
        .date-range { position: absolute; right: 0; top: 5px; font-size: 12px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 20px; }
        th { background-color: #cfd8dc; border: 1px solid #000; padding: 6px 2px; font-size: 10px; font-weight: bold; text-align: center; }
        td { border: 1px solid #999; padding: 4px 6px; vertical-align: middle; }
        .claim-heading-row td { background-color: #fff3e0; border: 1px solid #000; padding: 8px 6px; font-weight: bold; font-size: 12px; color: #e65100; }
        .total-row td { font-weight: bold; border: 1px solid #000; }
        .val-box { text-align: right; }
        .grand-total-row td { font-weight: bold; font-size: 12px; padding: 10px 6px; border-top: 2px solid #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 11px; color: #555; }
        @media print { .no-print { display: none !important; } body { padding: 0; } tr { page-break-inside: avoid; } }
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
    <h1 class="report-title">Claim Entry Report (Claim Wise)</h1>
    <div class="date-range">
        From: <span>{{ \Carbon\Carbon::parse($from_date)->format('d-m-y') }}</span>
        To: <span>{{ \Carbon\Carbon::parse($to_date)->format('d-m-y') }}</span>
    </div>
</div>

<table id="claimReportTable">
    <thead>
        <tr>
            <th>CLM No.</th>
            <th>Date</th>
            <th>Party</th>
            <th>Item Description</th>
            <th>Claim Type</th>
            <th>Replacement</th>
            <th>Fault Found</th>
            <th>Mfg Date</th>
            <th>Card No.</th>
            <th>Qty</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @php 
            $grand_qty = 0;
            $grand_amount = 0; 
        @endphp

        @if($grouped->isEmpty())
            <tr><td colspan="11" style="text-align: center; padding: 50px;">No Data Found</td></tr>
        @endif

        @foreach($grouped as $claimNo => $items)
            @php
                $claim = $items->first();
                $amount = $claim->report_amount;
                $grand_qty += 1;
                $grand_amount += $amount;
                $displayId = preg_replace('/[^0-9]/', '', $claimNo) ?: $claimNo;
            @endphp
            <tr>
                <td class="text-center fw-bold">{{ $displayId }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-m-y') }}</td>
                <td class="text-left">{{ strtoupper($claim->party_name) }}</td>
                <td class="text-left">{{ $claim->product->name ?? 'N/A' }}</td>
                <td class="text-center">{{ $claim->claim_type_label }}</td>
                <td class="text-left">
                    @if($claim->claim_type === 'credit_note' && $claim->replacementProduct)
                        {{ $claim->replacementProduct->name }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-left">{{ $claim->fault_found ?: '-' }}</td>
                <td class="text-center">{{ $claim->mfg_date ?: '-' }}</td>
                <td class="text-center">{{ $claim->card_no ?: '-' }}</td>
                <td class="text-center">1</td>
                <td class="text-right">{{ number_format($amount, 2) }}</td>
            </tr>
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
        var wb = XLSX.utils.table_to_book(table, {sheet: "Claim Wise Claim"});
        var fileName = "Claim_Report_Claim_Wise_{{ $from_date }}_to_{{ $to_date }}.xlsx";
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
