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
    }
    
    #example tbody td {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('sale.return.home') }}" method="GET" class="row g-3 align-items-end">
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
                                        <a href="{{ route('sale.return.home') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
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
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Sale Return Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Return No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Original Sale</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Supplier</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Qty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Net Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Action</label>
                                    </div>
                                </div>
                                <a class="btn btn-danger btn-sm px-4 rounded-pill" href="{{ route('sale.return.add') }}">
                                    <i class="fa fa-plus me-1"></i> Add Return
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Return No</th>
                                            <th>Original Sale</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th>Qty</th>
                                            <th>Date</th>
                                            <th>Net Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($SaleReturns as $ret)
                                        <tr>
                                            <td>{{ $ret->id }}</td>
                                            <td class="fw-bold">{{ $ret->invoice_no }}</td>
                                            <td>{{ $ret->sale->invoice_no ?? 'N/A' }}</td>
                                            <td>{{ $ret->party_name }}</td>
                                            <td class="small">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-center">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->sales_qty }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($ret->current_date)->format('d-M-Y') }}</td>
                                            <td class="text-end fw-bold">{{ number_format($ret->total_balance, 0) }}</td>
                                            <td class="text-center">
                                                @if($ret->status === 'Posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($ret->status === 'Unposted')
                                                        <form action="{{ route('sale.return.post', $ret->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('sale.return.edit', $ret->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>

                                                        <form action="{{ route('sale.return.destroy', $ret->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted return?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    <a href="{{ route('sale.return.print', $ret->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print Return">
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
        const storageKey = 'sale_return_table_columns';
        
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

        var dt = $('#example').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search returns..."
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
