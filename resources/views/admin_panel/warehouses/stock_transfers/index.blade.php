@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    #transferTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    #transferTable tbody td {
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
                            <form action="{{ route('stock_transfers.index') }}" method="GET" class="row g-3 align-items-end">
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
                                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                            <h4 class="card-title mb-0 fw-bold text-dark">Stock Transfer Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> TR ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> From Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> To Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Prepared By</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Action</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('stock_transfers.create') }}">
                                    <i class="fa fa-plus me-1"></i> Add Transfer
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="transferTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>TR ID</th>
                                            <th>Date</th>
                                            <th>From Warehouse</th>
                                            <th>To Warehouse</th>
                                            <th>Items</th>
                                            <th>Amount (Approx)</th>
                                            <th>Prepared By</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transfers as $t)
                                        <tr>
                                            <td class="fw-bold">#{{ $t->id }}</td>
                                            <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d-M-Y') }}</td>
                                            <td>
                                                @if($t->from_shop)
                                                    <span class="badge bg-light text-primary border" style="font-size:11px;">Shop</span>
                                                @else
                                                    {{ $t->fromWarehouse->warehouse_name ?? '—' }}
                                                @endif
                                            </td>
                                            <td>
                                                {{ $t->toWarehouse->warehouse_name ?? '—' }}
                                                @if($t->to_shop)
                                                    <span class="badge bg-light text-primary border ms-1" style="font-size:10px;">Shop</span>
                                                @endif
                                            </td>
                                            <td class="small">
                                                @foreach($t->items as $it)
                                                    <div style="font-size:11px; border-bottom:1px dashed #eee; padding:2px 0;">
                                                        {{ $it->product->name ?? 'Unknown' }}
                                                        <span class="text-muted">({{ $it->quantity }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="text-end">
                                                @php 
                                                    $totalAmt = $t->items->sum(function($item) {
                                                        return $item->quantity * ($item->price ?? 0);
                                                    });
                                                @endphp
                                                {{ number_format($totalAmt, 0) }}
                                            </td>
                                            <td>{{ $t->creator->name ?? '—' }}</td>
                                            <td class="text-center">
                                                @if($t->status == 'Posted' || $t->status == 'accepted')
                                                    <span class="badge bg-success">{{ ucfirst($t->status) }}</span>
                                                @elseif($t->status == 'Unposted' || $t->status == 'pending')
                                                    <span class="badge bg-warning text-dark">{{ ucfirst($t->status) }}</span>
                                                @else
                                                    <span class="badge bg-danger">{{ ucfirst($t->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($t->status == 'Unposted' || $t->status == 'pending')
                                                        {{-- Post/Accept Action --}}
                                                        <form action="{{ route('stock_transfers.post', $t->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                    @endif

                                                    {{-- View --}}
                                                    <a href="{{ route('stock_transfers.show', $t->id) }}" class="btn btn-outline-info btn-sm rounded-circle" title="View Details">
                                                        <i class="fa fa-eye"></i>
                                                    </a>

                                                    {{-- Print --}}
                                                    <a href="{{ route('stock_transfers.print', $t->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
                                                        <i class="fa fa-print"></i>
                                                    </a>

                                                    @if($t->status == 'Unposted' || $t->status == 'pending')
                                                        {{-- Reject/Delete if possible --}}
                                                        <form action="{{ route('stock_transfers.reject', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this transfer?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Reject">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </form>
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
        const storageKey = 'stock_transfer_list_columns_v1';
        
        // Load initial state
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = $(this).data('column');
                if (columns.hasOwnProperty(colIdx)) {
                    $(this).prop('checked', columns[colIdx]);
                    toggleColumn(colIdx, columns[colIdx]);
                }
            });
        }

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            
            toggleColumn(colIdx, isChecked);
            saveState();
        });

        function toggleColumn(index, show) {
            const table = $('#transferTable');
            const cells = table.find(`th:nth-child(${index}), td:nth-child(${index})`);
            if (show) {
                cells.removeClass('column-hidden');
            } else {
                cells.addClass('column-hidden');
            }
        }

        function saveState() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        }

        $('#transferTable').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search transfers..."
            }
        });
    });
</script>
@endsection
