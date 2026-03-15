@extends('admin_panel.layout.app')

@section('content')
<div class="main-content bg-white">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow border-0 overflow-hidden">
                        <div class="card-body p-5" id="print-area">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                                <div>
                                    <h3 class="fw-bold text-dark mb-1">Al-Madina Battery Traders</h3>
                                    <p class="text-muted mb-0 small">Quality Batteries & Service</p>
                                    <p class="text-muted mb-0 small">Hyderabad, Pakistan</p>
                                </div>
                                <div class="text-end">
                                    <h2 class="fw-bold text-danger mb-2">SALE RETURN</h2>
                                    <p class="mb-1 small"><strong>Return #:</strong> {{ $ret->invoice_no }}</p>
                                    <p class="mb-1 small"><strong>Date:</strong> {{ \Carbon\Carbon::parse($ret->current_date)->format('d-M-Y') }}</p>
                                    <p class="mb-0 small"><strong>Status:</strong> <span class="badge bg-{{ $ret->status == 'Posted' ? 'success' : 'warning' }}">{{ $ret->status }}</span></p>
                                </div>
                            </div>

                            <!-- Details Row -->
                            <div class="row g-4 mb-4">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded shadow-sm h-100">
                                        <h6 class="fw-bold text-muted text-uppercase mb-2 small">Returned From:</h6>
                                        <h5 class="fw-bold mb-1">{{ $ret->party_name }}</h5>
                                        <p class="mb-1 small">Type: {{ ucfirst($ret->party_type) }}</p>
                                        @if($ret->sale)
                                            <p class="mb-0 small text-primary">Original Inv: <strong>{{ $ret->sale->invoice_no }}</strong></p>
                                        @else
                                            <p class="mb-0 small text-danger"><em>Manual Return (No Invoice Link)</em></p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="p-3 bg-light rounded shadow-sm h-100">
                                        <h6 class="fw-bold text-muted text-uppercase mb-2 small">Warehouse:</h6>
                                        <h5 class="fw-bold mb-1">{{ $ret->warehouse->warehouse_name ?? 'N/A' }}</h5>
                                        <p class="mb-0 small">Stored back to inventory</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="bg-dark text-white">
                                        <tr>
                                            <th class="ps-3 py-2">#</th>
                                            <th class="py-2">Product Description</th>
                                            <th class="text-center py-2">Price</th>
                                            <th class="text-center py-2">Disc (%)</th>
                                            <th class="text-center py-2">Qty</th>
                                            <th class="text-center py-2">Amount</th>
                                            <th class="text-end pe-3 py-2">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ret->items as $index => $item)
                                        <tr>
                                            <td class="ps-3">{{ $index + 1 }}</td>
                                            <td class="fw-bold">{{ $item->product->name ?? 'N/A' }}</td>
                                            <td class="text-center">{{ number_format($item->sales_price, 2) }}</td>
                                            <td class="text-center">{{ number_format($item->discount_percent, 2) }}%</td>
                                            <td class="text-center fw-bold">{{ $item->sales_qty }}</td>
                                            <td class="text-center">{{ number_format($item->sales_price, 2) }}</td>
                                            <td class="text-end pe-3 fw-bold">{{ number_format($item->amount, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="row justify-content-end">
                                <div class="col-md-5">
                                    <div class="card border-0 shadow-sm bg-light">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Subtotal:</span>
                                                <span class="fw-bold">{{ number_format($ret->sub_total2, 2) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 text-primary">
                                                <span>Total Discount:</span>
                                                <span class="fw-bold">-{{ number_format($ret->discount_amount, 2) }}</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between text-danger">
                                                <h5 class="fw-bold mb-0">Net Return:</h5>
                                                <h5 class="fw-bold mb-0">PKR {{ number_format($ret->total_balance, 2) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Remarks -->
                            @if($ret->remarks)
                            <div class="mt-4 p-3 border rounded">
                                <h6 class="fw-bold small text-muted text-uppercase mb-2">Remarks:</h6>
                                <p class="mb-0 small text-dark fst-italic">{{ $ret->remarks }}</p>
                            </div>
                            @endif

                            <!-- Signatures -->
                            <div class="row mt-5 pt-5 text-center">
                                <div class="col-4">
                                    <div class="border-top pt-2 mx-4 small fw-bold">Receiver Signature</div>
                                </div>
                                <div class="col-4"></div>
                                <div class="col-4">
                                    <div class="border-top pt-2 mx-4 small fw-bold">Authorized Signature</div>
                                </div>
                            </div>

                            <!-- Print Actions -->
                            <div class="no-print d-flex gap-2 justify-content-center mt-5 pt-4 border-top">
                                <button onclick="window.print()" class="btn btn-danger px-4 shadow-sm">
                                    <i class="fa fa-print me-1"></i> Print Invoice
                                </button>
                                <a href="{{ route('sale.return.home') }}" class="btn btn-dark px-4 shadow-sm">
                                    <i class="fa fa-arrow-left me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        header, .rt_nav_header, .no-print, .main-footer, .navbar-toggler, .top_nav {
            display: none !important;
        }
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .p-5 {
            padding: 0 !important;
        }
        body {
            background: white !important;
        }
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>
@endsection
