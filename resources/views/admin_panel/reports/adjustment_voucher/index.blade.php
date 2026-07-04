@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Adjustment Voucher Report Filters',
    'previewRoute' => 'reports.adjustment-voucher.preview',
    'showReceiptDates' => false,
    'reportTypeOptions' => [
        'source_party' => 'Source Party Name',
        'destination_account' => 'Destination Account',
    ],
    'defaultReportType' => 'source_party',
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
