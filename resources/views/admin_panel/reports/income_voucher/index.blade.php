@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Income Voucher Report Filters',
    'previewRoute' => 'reports.income-voucher.preview',
    'showReceiptDates' => false,
    'reportTypeOptions' => [
        'destination_account' => 'Destination Account',
        'source_party' => 'Source Party Name',
    ],
    'defaultReportType' => 'destination_account',
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
