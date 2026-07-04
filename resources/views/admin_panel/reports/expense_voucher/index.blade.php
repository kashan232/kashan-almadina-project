@extends('admin_panel.layout.app')

@section('content')
@include('admin_panel.reports.partials.voucher_filter_form', [
    'filterTitle' => 'Expense Voucher Report Filters',
    'previewRoute' => 'reports.expense-voucher.preview',
    'showReceiptDates' => false,
])
@endsection

@section('scripts')
@include('admin_panel.reports.partials.voucher_filter_scripts')
@endsection
