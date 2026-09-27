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
    
    #purchaseTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 2px 10px !important;
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
        content: "" !important; /* Hide them if they are too bulky, or just reposition */
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
    
    #purchaseTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 10px !important;
        font-size: 11px;
        color: #333;
    }

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
        <div class="container-fluid pt-1">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-2" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <form action="{{ route('Purchase.home') }}" method="GET">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-success text-white px-3 py-2 fs-6 rounded-pill"><i class="fa fa-shopping-cart me-1"></i> Purchase List</span>
                                        
                                        <div class="input-group input-group-sm" style="width: 220px;">
                                            <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                            <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                        </div>

                                        <div style="min-width: 160px;">
                                            <select name="user_group_id" class="form-select form-select-sm select2">
                                                <option value="">Group: All</option>
                                                @foreach($userGroups as $grp)
                                                    <option value="{{ $grp->id }}" {{ request('user_group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->group_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div style="min-width: 130px;">
                                            <select name="status" class="form-select form-select-sm select2">
                                                <option value="">Status: All</option>
                                                <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                                <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('Purchase.home') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i> Reset</a>
                                    </div>

                                    <div>
                                        <a class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" href="{{ route('add_purchase') }}">
                                            <i class="fa fa-plus me-1"></i> Add Purchase
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="purchaseTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Inv#</th>
                                    <th>DC #</th>
                                    <th>DC Date</th>
                                    <th>Date</th>
                                    <th>Party Type</th>
                                    <th>Party / Customer</th>
                                    <th>Warehouse</th>
                                    <th>Items</th>
                                    <th class="text-center">Item Qty</th>
                                    <th class="text-center">T. Qty</th>
                                    <th class="text-end">Inv Total</th>
                                    <th class="text-end">Disc</th>
                                    <th class="text-end">WHT</th>
                                    <th class="text-end text-success">Net</th>
                                    <th>Created</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($Purchase as $key => $purchase)
                                @php
                                    $partyLabel = ucfirst(strtolower(class_basename($purchase->purchasable_type ?? '')));
                                    if ($partyLabel === '') { $partyLabel = 'Vendor'; }
                                    $partyName = $purchase->purchasable->name
                                        ?? ($purchase->purchasable->customer_name ?? ($purchase->vendor->name ?? 'N/A'));
                                    $warehouseName = ($purchase->warehouse_id == 0 || !$purchase->warehouse)
                                        ? 'Shop'
                                        : ($purchase->warehouse->warehouse_name ?? 'N/A');
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $key+1 }}</td>
                                    <td class="text-center small fw-bold">PJ</td>
                                    <td class="fw-bold text-primary" data-order="{{ (int) preg_replace('/[^0-9]/', '', $purchase->invoice_no) ?: $purchase->id }}">{{ preg_replace('/[^0-9]/', '', $purchase->invoice_no) }}</td>
                                    <td class="small">{{ $purchase->dc ?: '-' }}</td>
                                    <td class="small">{{ $purchase->dc_date ? \Carbon\Carbon::parse($purchase->dc_date)->format('d-M-Y') : '-' }}</td>
                                    <td class="fw-bold text-dark" data-order="{{ $purchase->current_date }}_{{ $purchase->id }}">
                                        {{ \Carbon\Carbon::parse($purchase->current_date)->format('d-M-Y') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info px-1 py-0" style="font-size: 10px;">{{ $partyLabel }}</span>
                                    </td>
                                    <td class="fw-bold text-dark small">{{ $partyName }}</td>
                                    <td class="small text-muted"><i class="fa fa-building-o me-1"></i>{{ Str::limit($warehouseName, 18) }}</td>

                                    <td class="py-1">
                                        @foreach($purchase->items as $item)
                                            <div class="item-detail-row">{{ $item->product->name ?? 'Unknown' }}</div>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @foreach($purchase->items as $item)
                                            <div class="item-detail-row text-primary fw-bold">{{ (float)$item->qty }}</div>
                                        @endforeach
                                    </td>
                                    <td class="text-center fw-bold text-info">
                                        {{ (float)$purchase->items->sum('qty') }}
                                    </td>

                                    <td class="text-end fw-bold">{{ number_format($purchase->subtotal, 0) }}</td>
                                    <td class="text-end text-danger">{{ number_format($purchase->discount, 0) }}</td>
                                    <td class="text-end">{{ number_format($purchase->wht, 0) }}</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($purchase->net_amount, 0) }}</td>
                                    <td class="small text-muted">
                                        @if($purchase->user)
                                            <span class="text-dark small">{{ $purchase->user->name }}</span>
                                        @else
                                            <span class="text-muted small">System</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($purchase->status === 'Posted')
                                            <span class="badge bg-success rounded-pill px-3">Posted</span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($purchase->status === 'Unposted')
                                                <form action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this purchase?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                        <i class="fa fa-send"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                    <i class="fa fa-edit text-dark"></i>
                                                </a>
                                                <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this purchase?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete" style="height: 20px;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('purchase.view', $purchase->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View Purchase" style="height: 20px;">
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

<!-- Hidden Column Picker Template to be placed dynamically in DataTable Header -->
<div id="columnPickerMenuContainer" class="d-none">
    <div class="column-picker-dropdown ms-2">
        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
            <i class="fa fa-columns me-1"></i> Columns
        </button>
        <div class="column-picker-menu shadow" id="columnPickerMenu">
            <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
            <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
            <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
            <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
            <label class="column-picker-item"><input type="checkbox" data-column="4" checked> DC #</label>
            <label class="column-picker-item"><input type="checkbox" data-column="5" checked> DC Date</label>
            <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Date</label>
            <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Party Type</label>
            <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Party / Customer</label>
            <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Warehouse</label>
            <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Items</label>
            <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Item Qty</label>
            <label class="column-picker-item"><input type="checkbox" data-column="12" checked> T. Qty</label>
            <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Inv Total</label>
            <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Disc</label>
            <label class="column-picker-item"><input type="checkbox" data-column="15" checked> WHT</label>
            <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Net</label>
            <label class="column-picker-item"><input type="checkbox" data-column="17" checked> Created</label>
            <label class="column-picker-item"><input type="checkbox" data-column="18" checked> Status</label>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        const storageKey = 'purchase_table_cols_v5';
        
        // Initialize DataTable
        var dt = $('#purchaseTable').DataTable({
            "order": [[2, 'desc']], // Latest Inv# first
            "columnDefs": [
                { "type": "num", "targets": 2 }
            ],
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "scrollX": true,
            "autoWidth": false,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search purchases..."
            },
            dom: '<"d-flex justify-content-between align-items-center p-2"lB<"d-flex align-items-center"f<"column-picker-slot">>>rt<"d-flex justify-content-between align-items-center p-2"ip>',
            buttons: [
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm rounded-pill px-3 me-1' },
                { extend: 'csv', className: 'btn btn-outline-success btn-sm rounded-pill px-3 me-1' },
                { extend: 'excel', className: 'btn btn-outline-primary btn-sm rounded-pill px-3 me-1' }
            ]
        });

        // Inject Column Picker dropdown right next to Search filter box
        $('.column-picker-slot').html($('#columnPickerMenuContainer').html());

        // Toggle Column Picker Menu
        $(document).on('click', '#columnPickerBtn', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
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
        $(document).on('change', '#columnPickerMenu input', function() {
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
