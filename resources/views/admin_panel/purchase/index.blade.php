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
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <form action="{{ route('Purchase.home') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-shopping-cart me-2 text-primary"></i>Purchase Management</h6>
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
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Cashier</span>
                                        <select name="user_id" class="form-select form-select-sm select2 border-start-0">
                                            <option value="">All</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
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
                                <div class="col-md-2 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('Purchase.home') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        
                                        <a class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('add_purchase') }}">
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-shopping-cart me-1"></i> Purchase Ledger</span>
                    <div class="column-picker-dropdown">
                        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                            <i class="fa fa-columns me-1"></i> Columns
                        </button>
                        <div class="column-picker-menu shadow" id="columnPickerMenu">
                            <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                            <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Inv#</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Source</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Supplier</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Items</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Total Qty</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Warehouse</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Subtotal</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Disc</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="12" checked> WHT</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Net</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Created By</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Status</label>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="purchaseTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Inv#</th>
                                    <th>Source</th>
                                    <th>Supplier</th>
                                    <th>Items</th>
                                    <th class="text-center">Total Qty</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">Disc</th>
                                    <th class="text-end">WHT</th>
                                    <th class="text-end text-success">Net</th>
                                    <th>Created By</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($Purchase as $key => $purchase)
                                <tr>
                                    <td class="text-muted">{{ $key+1 }}</td>
                                    <td class="fw-bold text-dark" data-order="{{ $purchase->current_date }}_{{ $purchase->id }}">
                                        {{ \Carbon\Carbon::parse($purchase->current_date)->format('d-M-Y') }}
                                    </td>
                                    <td class="text-center small fw-bold">PJ</td>
                                    <td class="fw-bold text-primary">{{ preg_replace('/[^0-9]/', '', $purchase->invoice_no) }}</td>
                                    <td class="text-center">
                                        @if($purchase->inward_id)
                                            <span class="badge bg-info-subtle text-info border border-info px-2 py-0" style="font-size: 9px;">Inward ({{ $purchase->inward_id }})</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-0" style="font-size: 9px;">Direct</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark small">{{ $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? ($purchase->vendor->name ?? 'N/A')) }}</span>
                                    </td>
                                    
                                    <td class="py-1">
                                        @foreach($purchase->items as $item)
                                            <div class="item-detail-row">
                                                {{ $item->product->name ?? 'Unknown' }}
                                                <span class="text-primary fw-bold ms-1">({{ (float)$item->qty }})</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="text-center fw-bold text-info">
                                        {{ (float)$purchase->items->sum('qty') }}
                                    </td>
                                    <td class="small text-muted">
                                        @if($purchase->warehouse_id == 0 || !$purchase->warehouse)
                                            Shop
                                        @else
                                            {{ Str::limit($purchase->warehouse->warehouse_name ?? 'N/A', 15) }}
                                        @endif
                                    </td>
                                    
                                    <td class="text-end fw-bold">{{ number_format($purchase->subtotal, 0) }}</td>
                                    <td class="text-end text-danger">{{ number_format($purchase->discount, 0) }}</td>
                                    <td class="text-end">{{ number_format($purchase->wht, 0) }}</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($purchase->net_amount, 0) }}</td>
                                    <td class="small text-muted">{{ $purchase->user->name ?? 'N/A' }}</td>
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
                                                 <form action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline">
                                                     @csrf
                                                     <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                         <i class="fa fa-send"></i> Post
                                                     </button>
                                                 </form>
                                                 
                                                 <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-warning btn-mini" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                 </a>

                                                 <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted purchase?')">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit" class="btn btn-danger btn-mini" title="Delete">
                                                         <i class="fa fa-trash"></i>
                                                     </button>
                                                 </form>
                                             @endif
                                             
                                             <a href="{{ route('purchase.invoice', $purchase->id) }}" class="btn btn-dark btn-mini" title="Print Invoice">
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

        const storageKey = 'purchase_table_cols_v4';
        
        // Initialize DataTable
        var dt = $('#purchaseTable').DataTable({
            "order": [[1, 'desc']], // Default sort by Date DESC
            "pageLength": 25,
            "scrollX": true,
            "autoWidth": false,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search purchases..."
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
