@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    
    #example thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
        font-size: 13px;
    }
    
    #example tbody td {
        white-space: nowrap;
        vertical-align: middle;
        font-size: 13px;
    }

    .badge-unposted {
        background-color: #ffc107;
        color: #000;
    }
    .badge-posted {
        background-color: #198754;
        color: #fff;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            <!-- Filters Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('sale.index') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0 fw-bold">Sales & Bookings</h4>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('sale.add') }}">
                            <i class="fa fa-plus me-1"></i> Add Sale
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="saleListingTable" class="table table-striped table-bordered display w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice#</th>
                                    <th>Type</th>
                                    <th>Customer/Vendor</th>
                                    <th>Items Summary</th>
                                    <th class="text-end">Total</th>
                                    <th>Date</th>
                                    <th class="text-center">Status</th>
                                    <th style="width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales as $key => $sale)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td class="fw-bold">{{ $sale->invoice_no }}</td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            <span class="badge bg-info">Vendor</span>
                                        @elseif($sale->p_type === 'customer')
                                            <span class="badge bg-primary">Customer</span>
                                        @elseif($sale->p_type === 'walking')
                                            <span class="badge bg-secondary">Walk-in</span>
                                        @else
                                            <span class="badge bg-light text-dark">{{ ucfirst($sale->p_type ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            {{ $sale->vendor->name ?? 'N/A' }}
                                        @else
                                            {{ $sale->customer->customer_name ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($sale->items && $sale->items->count() > 0)
                                            @foreach($sale->items as $item)
                                                <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                    <strong>{{ $item->product->name ?? 'Product #'.$item->product_id }}</strong>
                                                    <span class="text-success ml-1">Qty: {{ $item->sales_qty ?? 0 }}</span>
                                                    <span class="text-muted ml-1">@ {{ number_format($item->sales_price ?? 0, 0) }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">No items</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($sale->total_balance, 0) }}</td>
                                    <td data-order="{{ $sale->created_at }}">
                                        {{ \Carbon\Carbon::parse($sale->created_at)->format('d-M-Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($sale->entry_status === 'Posted')
                                            <span class="badge badge-posted px-3 py-2 shadow-sm">Posted</span>
                                        @else
                                            <span class="badge badge-unposted px-3 py-2 shadow-sm">Unposted</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($sale->entry_status === 'Unposted')
                                                <a class="btn btn-outline-warning btn-sm rounded-circle" href="{{ route('editBooking.index', $sale->id) }}" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                 <a class="btn btn-outline-dark btn-sm rounded-circle" href="{{ route('booking.print', $sale->id) }}" title="Print">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            @else
                                                <a class="btn btn-outline-warning btn-sm rounded-circle" href="{{ route('sale.edit', $sale->id) }}" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a class="btn btn-outline-dark btn-sm rounded-circle" href="{{ route('sale.invoice', $sale->id) }}" title="Invoice">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#saleListingTable')) {
            $('#saleListingTable').DataTable().destroy();
        }
        
        $('#saleListingTable').DataTable({
            "order": [[1, "desc"]], // Default sort by Invoice# Descending
            "pageLength": 25,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search sales..."
            },
            "columnDefs": [
                { "orderable": false, "targets": [4, 8] } // Disable sort for Items and Actions
            ],
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
                'colvis'
            ]
        });
    });
</script>
@endsection