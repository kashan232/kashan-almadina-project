@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    #wastageTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    #wastageTable tbody td {
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
    .column-hidden {
        display: none !important;
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
                            <form action="{{ route('stock-wastage.index') }}" method="GET" class="row g-3 align-items-end">
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
                                        <option value="Posted"   {{ request('status') == 'Posted'   ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('stock-wastage.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                            <h4 class="card-title mb-0 fw-bold text-dark">Stock Wastage Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> GWN ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Expense Head</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Expense A/C</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Action</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('stock-wastage.create') }}">
                                    <i class="fa fa-plus me-1"></i> Add Wastage
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="wastageTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>GWN ID</th>
                                            <th>Date</th>
                                            <th>Warehouse</th>
                                            <th>Expense Head</th>
                                            <th>Expense A/C</th>
                                            <th>Items</th>
                                            <th>Remarks</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($wastages as $wastage)
                                        <tr>
                                            <td class="fw-bold">{{ $wastage->gwn_id }}</td>
                                            <td>{{ \Carbon\Carbon::parse($wastage->date)->format('d-M-Y') }}</td>
                                            <td>{{ $wastage->warehouse->warehouse_name ?? '-' }}</td>
                                            <td>{{ $wastage->accountHead->name ?? '-' }}</td>
                                            <td>{{ $wastage->account->title ?? '-' }}</td>
                                            <td class="small">
                                                @foreach($wastage->items as $item)
                                                    <div style="font-size:11px; border-bottom:1px dashed #eee; padding:2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                        <span class="text-muted">({{ $item->qty }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ $wastage->remarks ?? '-' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($wastage->total_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($wastage->status == 'Posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($wastage->status != 'Posted')
                                                        {{-- Post --}}
                                                        <form action="{{ route('stock-wastage.post', $wastage->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>

                                                        {{-- Edit --}}
                                                        <a href="{{ route('stock-wastage.edit', $wastage->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>

                                                        {{-- Delete --}}
                                                        <form action="{{ route('stock-wastage.destroy', $wastage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this wastage?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    {{-- Print (always) --}}
                                                    <a href="{{ route('stock-wastage.print', $wastage->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
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
        const storageKey = 'stock_wastage_table_columns_v2';
        
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

        var dt = $('#wastageTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search wastages..."
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
