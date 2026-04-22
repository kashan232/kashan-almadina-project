@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow border-0">
                        <div class="card-body p-5">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                                <div>
                                    <h4 class="mb-1 fw-bold">Al-Madina Battery Traders</h4>
                                    <p class="text-muted mb-1">Hyderabad, Pakistan</p>
                                    <p class="text-muted mb-0">Phone: +92 300 300300 </p>
                                </div>
                                <div class="text-end">
                                    <h2 class="fw-bold text-uppercase text-primary mb-2">Claim Acceptance</h2>
                                    <p class="mb-1"><strong>Voucher #:</strong> {{ $voucher->voucher_no }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($voucher->date)->format('d M Y') }}</p>
                                    <p class="mb-1"><strong>Status:</strong> <span class="badge {{ $voucher->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $voucher->status }}</span></p>
                                </div>
                            </div>

                            <!-- Party -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="fw-bold text-uppercase text-primary mb-2">
                                            <i class="fa fa-user me-1"></i> Party Details
                                        </h6>
                                        <p class="mb-0">
                                            @if($voucher->party_type == 'vendor')
                                                <strong>Vendor:</strong> {{ $voucher->vendor->name ?? 'N/A' }} | Phone: {{ $voucher->vendor->phone ?? 'N/A' }}
                                            @else
                                                <strong>Customer:</strong> {{ $voucher->customer->customer_name ?? 'N/A' }} | Phone: {{ $voucher->customer->customer_phone ?? 'N/A' }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>BTR #</th>
                                            <th class="text-start">Product</th>
                                            <th>Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($voucher->items as $index => $item)
                                        <tr>
                                            <td>{{ $index+1 }}</td>
                                            <td>{{ $item->btr_no ?? '-' }}</td>
                                            <td class="text-start">{{ $item->product->name ?? 'N/A' }}</td>
                                            <td class="fw-bold">{{ (float)$item->quantity }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="3" class="text-end">Total Quantity:</td>
                                            <td>{{ (float)$voucher->items->sum('quantity') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Remarks -->
                            @if($voucher->remarks)
                            <div class="mt-4">
                                <h6 class="fw-bold small text-muted text-uppercase mb-2">Remarks:</h6>
                                <p class="mb-0 border p-2 rounded bg-light small">{{ $voucher->remarks }}</p>
                            </div>
                            @endif

                            <!-- Footer -->
                            <div class="border-top pt-4 d-flex justify-content-between no-print mt-5">
                                <p class="text-muted small mb-0">Accepted by: {{ $voucher->creator->name ?? 'System' }} | Printed at: {{ date('d M Y H:i:s') }}</p>
                                <a href="javascript:window.print()" class="btn btn-danger btn-sm no-print">
                                    <i class="fa fa-print me-1"></i> Print Voucher
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
        .rt_nav_header, .no-print, .btn, .main-footer {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { width: 100% !important; pading: 0 !important; }
    }
</style>
@endsection
