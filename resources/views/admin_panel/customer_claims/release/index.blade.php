@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
    #claimReleaseTable thead th { white-space: nowrap; background-color: #f8f9fa; color: #333; font-weight: 600; vertical-align: middle; font-size: 13px; }
    #claimReleaseTable tbody td { white-space: nowrap; vertical-align: middle; font-size: 13px; }
    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }
    .card-header { background-color: #fff; border-bottom: 1px solid #edf2f9; }
    
    .column-picker-dropdown { position: relative; display: inline-block; }
    .column-picker-menu {
        position: absolute; top: 100%; right: 0; z-index: 1000; display: none; min-width: 200px; padding: 5px 0; margin: 2px 0 0;
        font-size: 14px; text-align: left; list-style: none; background-color: #fff; border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px; box-shadow: 0 6px 12px rgba(0,0,0,.175); max-height: 400px; overflow-y: auto;
    }
    .column-picker-menu.show { display: block; }
    .column-picker-item { display: block; padding: 5px 15px; clear: both; font-weight: 400; line-height: 1.42857143; color: #333; white-space: nowrap; cursor: pointer; }
    .column-picker-item:hover { background-color: #f5f5f5; }
    .column-picker-item input { margin-right: 10px; cursor: pointer; }

    .badge-draft { background-color: #ffc107; color: #000; }
    .badge-posted { background-color: #28a745; color: #fff; }
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
                            <form action="{{ route('customer-claims.release.index') }}" method="GET" class="row g-3 align-items-end">
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
                                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('customer-claims.release.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Customer Claim Release Management</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Release No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Claim No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Party / Customer</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Released From</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Item Details</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Action</label>
                                    </div>
                                </div>
                                <a class="btn btn-success btn-sm px-4 rounded-pill" href="{{ route('customer-claims.release.create') }}">
                                    <i class="fa fa-plus me-1"></i> New Claim Release
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="claimReleaseTable" class="table table-striped table-bordered display w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center">Release No</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Claim No</th>
                                            <th>Party / Customer</th>
                                            <th>Released From</th>
                                            <th>Item Details</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($releases as $rel)
                                        <tr>
                                            <td class="fw-bold text-center text-primary">{{ $rel->release_no }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($rel->release_date)->format('d-M-Y') }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $rel->claim->claim_no ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @if($rel->party_type == 'customer' || $rel->party_type == 'walkin')
                                                    <i class="fa fa-user me-1 text-info" title="Customer"></i>
                                                    {{ $rel->party->customer_name ?? 'N/A' }}
                                                @else
                                                    <i class="fa fa-truck me-1 text-warning" title="Vendor"></i>
                                                    {{ $rel->party->name ?? 'N/A' }}
                                                @endif
                                                <small class="text-muted d-block" style="font-size:10px;">{{ ucfirst($rel->party_type) }}</small>
                                            </td>
                                            <td>
                                                <i class="fa fa-warehouse me-1 text-secondary"></i>
                                                {{ $rel->warehouse_id == 0 ? 'Shop' : ($rel->warehouse->warehouse_name ?? '-') }}
                                            </td>
                                            <td>
                                                <div style="font-size:11px;">
                                                    <span class="fw-bold">{{ $rel->product->name ?? 'Product' }}</span>
                                                    <span class="badge bg-soft-primary text-primary ms-1">Qty: {{ (float)$rel->release_qty }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($rel->status == 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($rel->status != 'Posted')
                                                        {{-- Post --}}
                                                        <form action="{{ route('customer-claims.release.post', $rel->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-2" onclick="return confirm('Post this release?')" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>

                                                        {{-- Edit --}}
                                                        <a href="{{ route('customer-claims.release.create') }}" class="btn btn-sm btn-outline-warning rounded-circle" title="Edit" style="width: 28px; height: 28px; padding: 0; line-height: 26px;">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>

                                                        {{-- Link to Claim --}}
                                                        <a href="{{ route('customer-claims.index') }}?search={{ $rel->claim->claim_no ?? '' }}" class="btn btn-sm btn-outline-info rounded-pill px-2" title="View Claim">
                                                            Claim
                                                        </a>
                                                    @endif

                                                    {{-- Print --}}
                                                    <a href="#" class="btn btn-sm btn-outline-dark rounded-circle" title="Print" style="width: 28px; height: 28px; padding: 0; line-height: 26px;">
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
        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        var dt = $('#claimReleaseTable').DataTable({
            scrollX: true, 
            autoWidth: false, 
            pageLength: 25, 
            order: [[0, 'desc']],
            language: { 
                search: "_INPUT_", 
                searchPlaceholder: "Search releases..." 
            }
        });

        $('#columnPickerMenu input').on('change', function() {
            var colIdx = $(this).data('column');
            dt.column(colIdx - 1).visible($(this).is(':checked'));
            dt.columns.adjust().draw(false);
        });
    });
</script>
@endsection
