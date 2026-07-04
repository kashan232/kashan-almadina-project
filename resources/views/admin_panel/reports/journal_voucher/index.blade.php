@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Journal Voucher Report Filters',
    'previewRoute' => 'reports.journal-voucher.preview',
    'showReceiptDates' => false,
    'reportTypeOptions' => ['party' => 'Party Name'],
    'defaultReportType' => 'party',
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
