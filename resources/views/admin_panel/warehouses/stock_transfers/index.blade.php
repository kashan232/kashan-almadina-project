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

    /* Column Picker Styles */
    .column-picker-dropdown {
        position: relative;
        display: inline-block;
    }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 1000;
        display: none;
        min-width: 200px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0,0,0,.175);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show {
        display: block;
    }
    .column-picker-item {
        display: block;
        padding: 5px 15px;
        clear: both;
        font-weight: 400;
        line-height: 1.42857143;
        color: #333;
        white-space: nowrap;
        cursor: pointer;
    }
    .column-picker-item:hover {
        background-color: #f5f5f5;
    }
    .column-picker-item input {
        margin-right: 10px;
        cursor: pointer;
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

            <!-- Top Actions & Filter -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <form action="{{ route('stock_transfers.index') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-exchange me-2 text-primary"></i>Stock Transfers</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                        <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-select form-select-sm select2">
                                        <option value="">All Status</option>
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted"   {{ request('status') == 'Posted'   ? 'selected' : '' }}>Posted</option>
                                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-3 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        <a class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('stock_transfers.create') }}">
                                            <i class="fa fa-plus me-1"></i> Add Transfer
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Transfer History</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Type</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Inv#</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Date</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> From</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> To</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Items</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Prepared By</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Status</label>
                                </div>
                            </div>
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
                                            <th>Prepared By</th>
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
                                            <td><small>{{ $t->creator->name ?? '—' }}</small></td>
                                            <td class="text-center">
                                                @if($t->status == 'Posted' || $t->status == 'accepted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @elseif($t->status == 'Unposted' || $t->status == 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                                @else
                                                    <span class="badge bg-danger rounded-pill px-3">{{ ucfirst($t->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($t->status == 'Unposted' || $t->status == 'pending')
                                                        <form action="{{ route('stock_transfers.post', $t->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('stock_transfers.edit', $t->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;"><i class="fa fa-pencil text-dark"></i></a>
                                                        <form action="{{ route('stock_transfers.reject', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this transfer?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Reject" style="height: 20px;"><i class="fa fa-times"></i></button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('stock_transfers.show', $t->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View" style="height: 20px;"><i class="fa fa-eye"></i></a>
                                                    <a href="{{ route('stock_transfers.print', $t->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print" style="height: 20px;"><i class="fa fa-print"></i></a>
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
        $('.select2').select2({ width: '100%' });

        // Toggle Column Picker Menu
        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        const storageKey = 'stock_transfer_cols_v1';
        
        var dt = $('#transferTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[1, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search transfers..."
            }
        });

        // Apply saved column visibility
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                dt.column(colIdx - 1).visible(checked);
            });
            dt.columns.adjust().draw(false);
        }

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            
            dt.column(colIdx - 1).visible(isChecked);
            dt.columns.adjust().draw(false);
            
            const state = {};
            if (savedState) Object.assign(state, JSON.parse(savedState));
            state[colIdx] = isChecked;
            localStorage.setItem(storageKey, JSON.stringify(state));
        });
    });
</script>
@endsection
