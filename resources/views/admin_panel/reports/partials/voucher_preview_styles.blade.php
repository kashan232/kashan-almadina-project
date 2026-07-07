@include('admin_panel.reports.partials.report_global_zoom')
<style>
    @page { size: A4 landscape; margin: 5mm; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #000;
        margin: 0;
        padding: 8mm;
        background-color: #fff;
    }
    .no-print {
        padding: 10px;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;
        text-align: center;
        margin-bottom: 20px;
    }
    .company-name {
        text-align: center;
        color: #8e24aa;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 4px;
    }
    .report-header {
        text-align: center;
        position: relative;
        margin-bottom: 12px;
    }
    .report-title {
        color: #0d47a1;
        font-size: 20px;
        font-weight: bold;
        margin: 0 0 6px 0;
    }
    .date-range { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
    .date-range span { text-decoration: underline; }
    .generated-date {
        position: absolute;
        right: 0;
        top: 0;
        font-size: 11px;
        color: #555;
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
        padding: 6px 4px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
    }
    td {
        border: 1px solid #999;
        padding: 4px 6px;
        vertical-align: middle;
    }
    .group-heading-row td {
        background-color: #fff;
        border: none;
        border-top: 2px solid #000;
        padding: 10px 6px 4px 6px;
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
    }
    .data-row td { border: 1px solid #999; }
    .amount-cell { font-weight: bold; text-align: right; }
    .total-row td {
        font-weight: bold;
        border: 1px solid #000;
        padding: 5px 6px;
    }
    .total-label { text-align: right; color: #0d47a1; text-transform: uppercase; }
    .amount-box {
        background-color: #cfd8dc;
        text-align: right;
        font-weight: bold;
    }
    .grand-total-row td {
        font-weight: bold;
        font-size: 12px;
        padding: 8px 6px;
        border-top: 2px solid #000;
    }
    .grand-label { text-align: right; color: #0d47a1; }
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
</style>
