@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Payment Voucher Report Filters',
    'previewRoute' => 'reports.payment-voucher.preview',
    'showReceiptDates' => true,
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
