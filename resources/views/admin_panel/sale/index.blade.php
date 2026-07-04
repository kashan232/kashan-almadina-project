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
    
    .main-content-inner, .content-wrapper {
        padding: 0 !important;
    }
    
    /* Thead specific */
    #saleListingTable thead th {
        white-space: normal !important; /* wrap text */
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 1px 4px !important;
        font-size: 11px;
        text-transform: uppercase;
        line-height: 1.2;
    }

    /* Minimize DataTables Sorting Icon Height */
    table.dataTable thead .sorting:before, 
    table.dataTable thead .sorting_asc:before, 
    table.dataTable thead .sorting_desc:before, 
    table.dataTable thead .sorting:after, 
    table.dataTable thead .sorting_asc:after, 
    table.dataTable thead .sorting_desc:after {
        bottom: 2px !important;
        content: "" !important;
    }

    /* Alternative: reposition arrows */
    table.dataTable thead>tr>th.sorting:before, 
    table.dataTable thead>tr>th.sorting_asc:before, 
    table.dataTable thead>tr>th.sorting_desc:before, 
    table.dataTable thead>tr>td.sorting:before, 
    table.dataTable thead>tr>td.sorting_asc:before, 
    table.dataTable thead>tr>td.sorting_desc:before {
        top: 2px !important;
    }
    table.dataTable thead>tr>th.sorting:after, 
    table.dataTable thead>tr>th.sorting_asc:after, 
    table.dataTable thead>tr>th.sorting_desc:after, 
    table.dataTable thead>tr>td.sorting:after, 
    table.dataTable thead>tr>td.sorting_asc:after, 
    table.dataTable thead>tr>td.sorting_desc:after {
        bottom: 2px !important;
    }
    
    #saleListingTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 1px 4px !important;
        font-size: 11px;
        color: #333;
    }

    /* Minimize DataTables elements */
    .dataTables_length select { padding: 0px 10px; height: 26px; font-size: 11px; }
    .dataTables_filter input { padding: 0px 8px; height: 26px; font-size: 11px; }
    .dataTables_info, .dataTables_paginate { font-size: 11px; margin-top: 5px; }

    /* Small Export Buttons */
    .dt-buttons {
        margin-bottom: 5px;
    }
    .dt-button {
        padding: 2px 8px !important;
        font-size: 10px !important;
        border-radius: 4px !important;
        background: #f8f9fa !important;
        border: 1px solid #ddd !important;
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
        z-index: 10000;
        display: none;
        min-width: 220px;
        padding: 8px 0;
        margin-top: 5px;
        font-size: 13px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.1);
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show {
        display: block;
    }
    .column-picker-item {
        display: flex;
        align-items: center;
        padding: 6px 16px;
        clear: both;
        font-weight: 400;
        line-height: 1.5;
        color: #444;
        white-space: nowrap;
        cursor: pointer;
        transition: background 0.2s;
    }
    .column-picker-item:hover {
        background-color: #f8f9fa;
        color: #000;
    }
    .column-picker-item input {
        margin-right: 12px;
        cursor: pointer;
        width: 16px;
        height: 16px;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
        margin-bottom: 0.5rem;
    }
    
    .item-detail-row {
        font-size: 10px;
        border-bottom: 1px dashed #eee;
        padding: 1px 0;
        line-height: 1.2;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-0">
            
            <!-- Filters Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <form action="{{ route('sale.index') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-2">
                                    <h6 class="mb-0 fw-bold text-dark ms-2 small"><i class="fa fa-shopping-cart me-2 text-primary"></i>Sales & Bookings</h6>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                        <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Status</span>
                                        <select name="status" class="form-select form-select-sm select2 border-start-0">
                                            <option value="">All</option>
                                            <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                            <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">User</span>
                                        <select name="created_by" class="form-select form-select-sm select2 border-start-0">
                                            <option value="">All</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        
                                        <a class="btn btn-primary btn-sm rounded-pill px-2 shadow-sm ms-1" href="{{ route('sale.add') }}" style="font-size: 10px;">
                                            <i class="fa fa-plus me-1"></i> Add Sale
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-shopping-cart me-1"></i> Sales Ledger</span>
                    <div class="column-picker-dropdown">
                        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                            <i class="fa fa-columns me-1"></i> Columns
                        </button>
                        <div class="column-picker-menu shadow" id="columnPickerMenu">
                            <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                            <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Manual Inv</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Sale Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Party Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Customer/Vendor</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Items</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Location</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Net Total</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Disc</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Receipts</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Payable Balance</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Created By</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Date</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Status</label>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="saleListingTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Inv#</th>
                                    <th>Manual Inv</th>
                                    <th>Sale Type</th>
                                    <th>Party Type</th>
                                    <th>Customer/Vendor</th>
                                    <th>Items</th>
                                    <th>Location</th>
                                    <th class="text-end">Net Total</th>
                                    <th class="text-end">Disc</th>
                                    <th class="text-end text-success">Receipts</th>
                                    <th class="text-end text-primary">Payable Balance</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales as $key => $sale)
                                <tr>
                                    <td class="text-muted">{{ $key+1 }}</td>
                                    <td class="text-muted small">SJ</td>
                                    <td class="fw-bold text-primary">{{ (int) preg_replace('/[^0-9]/', '', $sale->invoice_no) }}</td>
                                    <td>{{ $sale->manual_invoice ?? '-' }}</td>
                                    <td>
                                        @if($sale->is_sale_order)
                                            <span class="badge bg-danger rounded-pill px-2" style="font-size: 9px;"><i class="fa fa-calendar-check-o me-1"></i> Order</span>
                                        @else
                                            <span class="badge bg-success rounded-pill px-2" style="font-size: 9px;"><i class="fa fa-check-circle me-1"></i> Proper</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            <span class="badge bg-info-subtle text-info border border-info px-1 py-0" style="font-size: 10px;">Vendor</span>
                                        @elseif($sale->p_type === 'customer')
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-1 py-0" style="font-size: 10px;">Customer</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-1 py-0" style="font-size: 10px;">{{ ucfirst($sale->p_type ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            <span class="fw-bold text-dark small">{{ $sale->vendor->name ?? 'N/A' }}</span>
                                        @else
                                            <span class="fw-bold text-dark small">{{ $sale->customer->customer_name ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    
                                    <td class="py-1">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                {{ $item->product->name ?? 'Product #'.$item->product_id }}
                                                <span class="text-primary fw-bold ms-1">({{ number_format($item->sales_qty ?? 0, 0) }})</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="small text-muted">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                @if($item->warehouse_id == 0)
                                                    Shop
                                                @else
                                                    {{ Str::limit($item->warehouse->warehouse_name ?? 'N/A', 15) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>

                                    <td class="text-end fw-bold">{{ number_format($sale->sub_total2 ?? ($sale->items->sum('amount') ?? 0), 0) }}</td>
                                    <td class="text-end text-danger">{{ number_format($sale->discount_amount ?? 0, 0) }}</td>
                                    <td class="text-end text-success">{{ number_format(($sale->receipt1 ?? 0) + ($sale->receipt2 ?? 0), 0) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($sale->total_balance, 0) }}</td>
                                    
                                    <td>
                                        @if($sale->creator)
                                            <span class="text-dark small">{{ $sale->creator->name }}</span>
                                        @else
                                            <span class="text-muted small">System</span>
                                        @endif
                                    </td>
                                    
                                    <td class="small">{{ \Carbon\Carbon::parse($sale->created_at)->format('d-M-Y') }}</td>
                                    <td class="text-center">
                                        @if($sale->entry_status === 'Posted')
                                            <span class="badge bg-success rounded-pill px-3">Posted</span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($sale->entry_status === 'Unposted')
                                                <form action="{{ route('sale.post', $sale->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                        <i class="fa fa-send"></i> Post
                                                    </button>
                                                </form>

                                                <a class="btn btn-outline-warning btn-xs px-1 py-0" href="{{ route('editBooking.index', $sale->id) }}" title="Edit" style="height: 20px;">
                                                    <i class="fa fa-edit text-dark"></i>
                                                </a>

                                                <form action="{{ route('sale.destroy', $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted booking?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete" style="height: 20px;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('sale.view', $sale->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View Sale" style="height: 20px;">
                                                    <i class="fa fa-eye"></i>
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

        const storageKey = 'sale_table_cols_v2';
        
        // Initialize DataTable
        var dt = $('#saleListingTable').DataTable({
            "order": [[2, 'desc']], // Default sort by Inv#
            "pageLength": 25,
            "autoWidth": false,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search sales..."
            },
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5', 'excelHtml5', 'csvHtml5'
            ]
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
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        });
    });
</script>
@endsection
