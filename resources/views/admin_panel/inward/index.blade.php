@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
    #gpTable thead th { white-space: nowrap; background-color: #f8f9fa; color: #333; font-weight: 600; vertical-align: middle; }
    #gpTable tbody td { white-space: nowrap; vertical-align: middle; }
    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }
    .card-header { background-color: #fff; border-bottom: 1px solid #edf2f9; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-times-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Filter Section --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('InwardGatepass.home') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Vendor</label>
                                    <input type="text" name="vendor" class="form-control form-control-sm" placeholder="Vendor name" value="{{ request('vendor') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted"   {{ request('status') == 'Posted'   ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('InwardGatepass.home') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table --}}
            <div class="row">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Inward Gatepass Management</h4>
                            <div class="d-flex gap-2">
                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('add_inwardgatepass') }}">
                                    <i class="fa fa-plus me-1"></i> Add Inward
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="gpTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>Invoice#</th>
                                            <th>Date</th>
                                            <th>Branch</th>
                                            <th>Warehouse</th>
                                            <th>Vendor</th>
                                            <th>Transport</th>
                                            <th>Bilty#</th>
                                            <th>Items</th>
                                            <th>Note</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gatepasses as $gp)
                                        <tr>
                                            <td class="fw-bold">{{ $gp->invoice_no }}</td>
                                            <td>{{ \Carbon\Carbon::parse($gp->gatepass_date)->format('d-M-Y') }}</td>
                                            <td>{{ $gp->branch->name ?? '-' }}</td>
                                            <td>{{ $gp->warehouse->warehouse_name ?? '-' }}</td>
                                            <td>{{ $gp->vendor->name ?? '-' }}</td>
                                            <td>{{ $gp->transport_name ?? '-' }}</td>
                                            <td>{{ $gp->gatepass_no ?? '-' }}</td>
                                            <td class="small">
                                                @foreach($gp->items as $item)
                                                    <div style="font-size:11px; border-bottom:1px dashed #eee; padding:2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                        <span class="text-muted">({{ $item->qty }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ $gp->remarks ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($gp->status == 'Posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($gp->status != 'Posted')
                                                        {{-- Post --}}
                                                        <form action="{{ route('InwardGatepass.post', $gp->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>

                                                        {{-- Edit --}}
                                                        <a href="{{ route('InwardGatepass.edit', $gp->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>

                                                        {{-- Delete --}}
                                                        <form action="{{ route('InwardGatepass.destroy', $gp->id) }}" method="POST" class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle delete-btn" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    {{-- Print (always) --}}
                                                    <a href="{{ route('InwardGatepass.pdf', $gp->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print PDF">
                                                        <i class="fa fa-print"></i>
                                                    </a>
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
    $(document).ready(function() {
        $('#gpTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search gatepasses..."
            }
        });

        // Delete confirm
        $(document).on('click', '.delete-btn', function() {
            var form = $(this).closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This gatepass will be deleted!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endsection