@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Receipt Voucher Report Filters',
    'previewRoute' => 'reports.receipt-voucher.preview',
    'showReceiptDates' => true,
    'reportTypeOptions' => [
        'source_party' => 'Source Party Name',
        'sub_head' => 'Sub Head',
        'all' => 'All (Voucher Wise)',
    ],
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
