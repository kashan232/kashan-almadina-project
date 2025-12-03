@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow border-0">
                        <div class="card-body p-5" id="purchase-invoice-card">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                                <!-- Logo & Company Info -->
                                <div class="d-flex align-items-center">
                                    <!-- <img src="{{ asset('assets/images/almadina_logo.png') }}" alt="Company Logo" height="80" class="me-3"> -->
                                    <div>
                                        <h4 class="mb-1 fw-bold">Al-Madina Battery Traders</h4>
                                        <p class="text-muted mb-1">Hyderabad, Pakistan</p>
                                        <p class="text-muted mb-0">Phone: +92 300 300300 </p>
                                    </div>
                                </div>

                                <!-- Invoice Info -->
                                <div class="text-end">
                                    <h2 class="fw-bold text-uppercase text-primary mb-2">PURCHASE INVOICE</h2>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $purchase->invoice_no }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($purchase->current_date)->format('d M Y') }}</p>
                                    <p class="mb-1"><strong>Warehouse:</strong> {{ $purchase->warehouse->warehouse_name ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Vendor -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="fw-bold text-uppercase text-primary mb-2">
                                            <i class="bi bi-person-lines-fill me-1"></i> Supplier
                                        </h6>
                                        <p class="mb-0">
                                            <strong>{{ $purchase->vendor->name ?? 'N/A' }}</strong> |
                                            Phone: {{ $purchase->vendor->phone ?? 'N/A' }} |
                                            Address: {{ $purchase->vendor->address ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th class="text-start">Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->items as $index => $item)
                                        <tr>
                                            <td>{{ $index+1 }}</td>
                                            <td class="text-start">{{ $item->product->name ?? 'N/A' }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ number_format($item->price, 2) }}</td>
                                            <td>{{ number_format($item->item_discount, 2) }}</td>
                                            <td class="fw-bold">{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totals Summary Box -->
                            <div class="row justify-content-end">
                                <div class="col-md-6">
                                    <div class="border rounded p-1">
                                        <table class="table table-borderless mb-0">
                                            <tr>
                                                <td class="text-end fw-bold">Subtotal:</td>
                                                <td class="fw-bold">{{ number_format($purchase->subtotal, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold">Discount:</td>
                                                <td>{{ number_format($purchase->discount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end fw-bold">WHT:</td>
                                                <td>{{ number_format($purchase->wht, 2) }}</td>
                                            </tr>
                                            <tr class="border-top">
                                                <td class="text-end fw-bold">Net Amount:</td>
                                                <td class="fw-bold text-success">{{ number_format($purchase->net_amount, 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <!-- Footer -->
                            <div class="border-top pt-4 d-flex justify-content-between no-print mt-2">
                                <p class="text-muted small mb-0">This is a computer-generated invoice. No signature required.</p>
                                <a href="javascript:window.print()" class="btn btn-danger btn-sm no-print">
                                    <i class="bi bi-printer"></i> Print Invoice
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
        .rt_nav_header {
            display: none !important;
            /* navbar hide hoga */
        }

        .no-print {
            display: none !important;
            /* jo buttons ya footer aap hide karna chahein */
        }
    }
</style>
@endsection