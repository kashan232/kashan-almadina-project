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
    
    #transferTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 13px;
    }
    
    #transferTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 10px !important;
        font-size: 12px;
        color: #333;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                    <i class="fa fa-times-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-clock-o text-warning me-2"></i> Pending Transfer Requests</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="transferTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Items</th>
                                            <th>Created By</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transfers as $t)
                                        <tr>
                                            <td class="text-muted small">GT</td>
                                            <td class="fw-bold text-primary">#{{ $t->id }}</td>
                                            <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-M-Y') }}</td>
                                            <td>
                                                @if($t->from_shop)
                                                    <span class="badge bg-light text-primary border px-2 py-1" style="font-size:10px;">Shop</span>
                                                @else
                                                    <span class="fw-semibold">{{ $t->fromWarehouse->warehouse_name ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $t->toWarehouse->warehouse_name ?? '—' }}</span>
                                                @if($t->to_shop)
                                                    <span class="badge bg-light text-primary border ms-1 px-1 py-0" style="font-size:9px;">Shop</span>
                                                @endif
                                            </td>
                                            <td class="py-1">
                                                @foreach($t->items as $it)
                                                    <div style="font-size:10.5px; border-bottom:1px dashed #eee; padding:1px 0; line-height: 1.2;">
                                                        {{ $it->product->name ?? 'Unknown' }}
                                                        <span class="text-primary fw-bold ms-1">({{ number_format($it->quantity, 0) }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($t->creator)
                                                    <span class="text-dark small">{{ $t->creator->name }}</span>
                                                @else
                                                    <span class="text-muted small">System</span>
                                                @endif
                                            </td>
                                    <td class="text-center">
                                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ $t->status }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <form action="{{ route('stock_transfers.accept', $t->id) }}" method="POST" class="d-inline" id="approve-form-{{ $t->id }}">
                                                        @csrf
                                                        <button type="button" class="btn btn-success btn-xs px-2 py-0" title="Approve" style="font-size: 10px;" onclick="confirmAction('approve-form-{{ $t->id }}', 'Approve Transfer', 'Are you sure you want to approve this transfer? This will impact the stock ledger.', 'success', 'Yes, Approve it!')">
                                                            <i class="fa fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('stock_transfers.reject', $t->id) }}" method="POST" class="d-inline" id="reject-form-{{ $t->id }}">
                                                        @csrf
                                                        <button type="button" class="btn btn-danger btn-xs px-2 py-0" title="Reject" style="font-size: 10px;" onclick="confirmAction('reject-form-{{ $t->id }}', 'Reject Transfer', 'Are you sure you want to reject this transfer?', 'warning', 'Yes, Reject it!')">
                                                            <i class="fa fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('stock_transfers.show', $t->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View" style="height: 20px;"><i class="fa fa-eye"></i></a>
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
    </div>
</div>
@endsection

@section('scripts')
<script>
    function confirmAction(formId, title, text, icon, confirmButtonText) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: icon === 'warning' || icon === 'error' ? '#d33' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    $(document).ready(function() {
        var dt = $('#transferTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[1, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search requests..."
            }
        });
    });
</script>
@endsection
