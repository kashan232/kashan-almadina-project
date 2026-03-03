@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    #adjustmentTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    #adjustmentTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
    }
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
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
                            <form action="{{ route('warehouse_stocks.index') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Warehouse</label>
                                    <select name="warehouse_id" class="form-select form-select-sm">
                                        <option value="">All Warehouses</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('warehouse_stocks.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table Section --}}
            <div class="row">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">📦 Manual Stock Update Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ADJ ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Items Updated</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Action</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('warehouse_stocks.create') }}">
                                    <i class="fa fa-plus me-1"></i> New Update
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="adjustmentTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ADJ ID</th>
                                            <th>Date</th>
                                            <th>Warehouse</th>
                                            <th>Items Updated</th>
                                            <th>Remarks</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stocks as $adj)
                                        <tr>
                                            <td class="fw-bold">{{ $adj->adj_id }}</td>
                                            <td>{{ \Carbon\Carbon::parse($adj->date)->format('d-M-Y') }}</td>
                                            <td>{{ $adj->warehouse->warehouse_name ?? '-' }}</td>
                                            <td class="small">
                                                @foreach($adj->items as $item)
                                                    <div style="font-size:11px; border-bottom:1px dashed #eee; padding:2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                        <span class="text-muted">({{ number_format($item->qty, 0) }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ $adj->remarks ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($adj->status == 'Posted')
                                                    <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($adj->status != 'Posted')
                                                        {{-- Post --}}
                                                        <form action="{{ route('warehouse_stocks.post', $adj->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>

                                                        {{-- Edit --}}
                                                        <a href="{{ route('warehouse_stocks.edit', $adj->id) }}" class="btn btn-outline-warning btn-sm rounded-circle shadow-sm" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                        
                                                        {{-- Delete --}}
                                                        <form action="{{ route('warehouse_stocks.destroy', $adj->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this adjustment?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    {{-- Print (always) --}}
                                                    <a href="{{ route('warehouse_stocks.print', $adj->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle shadow-sm" title="Print">
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
        // Toggle Column Picker Menu
        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        // Column Persistence with LocalStorage
        const storageKey = 'warehouse_stock_adj_columns_v1';
        
        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            
            toggleColumn(colIdx, isChecked);
            saveState();
        });

        function saveState() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        }

        var dt = $('#adjustmentTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search adjustments..."
            }
        });

        // Load initial state
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = $(this).data('column');
                if (columns.hasOwnProperty(colIdx)) {
                    $(this).prop('checked', columns[colIdx]);
                    dt.column(colIdx - 1).visible(columns[colIdx]);
                }
            });
            dt.columns.adjust().draw(false);
        }

        let toggleColumn = function(index, show) {
            dt.column(index - 1).visible(show);
            dt.columns.adjust().draw(false);
        };
    });
</script>
@endsection
