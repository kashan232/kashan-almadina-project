@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 0.5rem;
    }
    
    .main-content-inner, .content-wrapper {
        padding: 0 !important;
    }
    
    /* Thead specific */
    #saleListingTable thead th {
        white-space: nowrap !important;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 4px 6px !important;
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
        padding: 2px 6px !important;
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
    
    /* Compact Items Column Styling */
    .items-list-container {
        max-height: 50px;
        overflow-y: auto;
        scrollbar-width: thin;
    }
    .items-list-container::-webkit-scrollbar {
        width: 3px;
    }
    .items-list-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }
    .item-detail-row {
        font-size: 10px;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
        border-bottom: 1px dashed #e2e8f0;
        padding: 1px 0;
    }
    .item-detail-row:last-child {
        border-bottom: none;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-0">
            
            <!-- Filter Section -->
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <form action="{{ route('sale.index') }}" method="GET">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-success text-white px-3 py-2 fs-6 rounded-pill"><i class="fa fa-shopping-cart me-1"></i> Sales List</span>
                                        
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
                                        <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                    </div>

                                    <div>
                                        <a class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" href="{{ route('sale.add') }}">
                                            <i class="fa fa-plus me-1"></i> Add Sale
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column Picker Menu Template (Detached and injected via JS) -->
            <div id="columnPickerMenuContainer" class="d-none">
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
                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Date</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Party Type</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Customer</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Group</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Warehouse</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Items</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Item Qty</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> T. Qty</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Inv Total</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Disc</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="15" checked> A/C Allocation</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Net Payable</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="17" checked> Created</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="18" checked> Status</label>
                    </div>
                </div>
            </div>

            <!-- Sales Table Card -->
            <div class="card shadow-sm border-0 position-relative">
                <!-- Initial Clean Loading State to Prevent Layout Shift -->
                <div id="tableLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                    <span class="text-muted small fw-semibold">Loading sales data...</span>
                </div>

                <div id="tableContentArea" style="opacity: 0; transition: opacity 0.25s ease-in-out;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="saleListingTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 35px;">#</th>
                                        <th style="width: 45px;">Type</th>
                                        <th style="width: 65px;">Inv#</th>
                                        <th style="width: 75px;">Manual Inv</th>
                                        <th style="width: 85px;">Date</th>
                                        <th style="width: 75px;">Party Type</th>
                                        <th style="min-width: 140px;">Customer</th>
                                        <th style="width: 80px;">Group</th>
                                        <th style="width: 100px;">Warehouse</th>
                                        <th style="min-width: 160px; max-width: 220px;">Items</th>
                                        <th class="text-center" style="width: 65px;">Item Qty</th>
                                        <th class="text-center" style="width: 60px;">T. Qty</th>
                                        <th class="text-end" style="width: 85px;">Inv Total</th>
                                        <th class="text-end" style="width: 65px;">Disc</th>
                                        <th class="text-end" style="width: 85px;">A/C Allocation</th>
                                        <th class="text-end text-primary" style="width: 90px;">Net Payable</th>
                                        <th style="width: 75px;">Created</th>
                                        <th class="text-center" style="width: 75px;">Status</th>
                                        <th class="text-center" style="width: 120px; min-width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sales as $key => $sale)
                                    @php
                                        $partyName = $sale->party_name ?? ($sale->p_type === 'vendor' ? ($sale->vendor->name ?? 'N/A') : ($sale->customer->customer_name ?? 'N/A'));
                                        $whNames = $sale->wh_names ?? '-';
                                        $displayInv = $sale->display_inv_no ?? (int) preg_replace('/[^0-9]/', '', $sale->invoice_no);
                                        $displayDate = $sale->display_date ?? \Carbon\Carbon::parse($sale->entry_date ?? $sale->created_at)->format('d-M-Y');
                                        $totalQty = $sale->item_qty_sum ?? $sale->items->sum('sales_qty');
                                        $invTotal = $sale->display_inv_total ?? (((float)$sale->sub_total2 > 0) ? (float)$sale->sub_total2 : (float)$sale->items->sum('amount'));
                                        $saleReceipts = $sale->display_receipts ?? (($sale->receipt1 ?? 0) + ($sale->receipt2 ?? 0));
                                        $netPayable = $sale->display_net_payable ?? (((float)$sale->total_balance != 0) ? (float)$sale->total_balance : ($invTotal - (float)($sale->discount_amount ?? 0)));
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $key+1 }}</td>
                                        <td class="text-muted small">SJ</td>
                                        <td class="fw-bold text-primary">{{ $displayInv }}</td>
                                        <td>{{ $sale->manual_invoice ?? '-' }}</td>
                                        <td class="small">{{ $displayDate }}</td>
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
                                            <span class="fw-bold text-dark small">{{ $partyName }}</span>
                                        </td>
                                        <td>
                                            @if(!empty($sale->group_names))
                                                @foreach($sale->group_names as $gName)
                                                    <span class="badge bg-secondary-subtle text-dark border px-1 py-0" style="font-size: 10px;">{{ $gName }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-muted" style="font-size: 10px;"><i class="fa fa-building-o me-1"></i>{{ Str::limit($whNames ?: '-', 20) }}</div>
                                        </td>
                                        <td class="py-1">
                                            <div class="items-list-container">
                                                @foreach($sale->items as $item)
                                                    <div class="item-detail-row" title="{{ $item->product->name ?? 'Product #'.$item->product_id }}">{{ $item->product->name ?? 'Product #'.$item->product_id }}</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center py-1">
                                            <div class="items-list-container">
                                                @foreach($sale->items as $item)
                                                    <div class="item-detail-row text-primary fw-bold">{{ number_format($item->sales_qty ?? 0, 0) }}</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-info">{{ number_format($totalQty, 0) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($invTotal, 0) }}</td>
                                        <td class="text-end text-danger">{{ number_format($sale->discount_amount ?? 0, 0) }}</td>
                                        <td class="text-end">{{ number_format($saleReceipts, 0) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($netPayable, 0) }}</td>
                                        <td>
                                            @if($sale->user)
                                                <span class="text-dark small">{{ $sale->user->name }}</span>
                                            @else
                                                <span class="text-muted small">System</span>
                                            @endif
                                        </td>
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
                                                    <form action="{{ url('/sale/' . $sale->id . '/post') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                            <i class="fa fa-send"></i> Post
                                                        </button>
                                                    </form>

                                                    <a class="btn btn-outline-warning btn-xs px-1 py-0" href="{{ url('/Booking/edit/' . $sale->id) }}" title="Edit" style="height: 20px;">
                                                        <i class="fa fa-edit text-dark"></i>
                                                    </a>

                                                    <a href="{{ url('/sale/invoice/' . $sale->id) }}" target="_blank" class="btn btn-outline-primary btn-xs px-1 py-0" title="Print Invoice" style="height: 20px; font-size: 10px; line-height: 18px;">
                                                        <i class="fa fa-print"></i> Inv
                                                    </a>

                                                    <a href="{{ url('/sale/dc/' . $sale->id) }}" target="_blank" class="btn btn-outline-secondary btn-xs px-1 py-0" title="Delivery Challan (DC)" style="height: 20px; font-size: 10px; line-height: 18px;">
                                                        <i class="fa fa-truck"></i> DC
                                                    </a>

                                                    <form action="{{ url('/sale/' . $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted booking?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete" style="height: 20px;">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="{{ url('/sale/invoice/' . $sale->id) }}" target="_blank" class="btn btn-outline-primary btn-xs px-1 py-0" title="Print Invoice" style="height: 20px; font-size: 10px; line-height: 18px;">
                                                        <i class="fa fa-print"></i> Inv
                                                    </a>
                                                    <a href="{{ url('/sale/dc/' . $sale->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Delivery Challan (DC)" style="height: 20px; font-size: 10px; line-height: 18px;">
                                                        <i class="fa fa-truck"></i> DC
                                                    </a>
                                                    <a href="{{ url('/sale/' . $sale->id . '/view') }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View Sale" style="height: 20px;">
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
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        const storageKey = 'sale_table_cols_v5';
        let savedCols = {};
        try {
            const raw = localStorage.getItem(storageKey);
            if (raw) savedCols = JSON.parse(raw);
        } catch (e) {
            savedCols = {};
        }

        // Move Column Picker element to slot to avoid duplicate DOM elements and IDs
        const pickerEl = $('#columnPickerMenuContainer').children().detach();
        $('#columnPickerMenuContainer').remove();

        // Build columnDefs with pre-defined visibility from localStorage
        const columnDefs = [
            { "type": "num", "targets": 2 }, // Invoice# sorting
            { "orderable": false, "targets": [9, 10, 18] } // Items, Item Qty, Actions
        ];

        // Apply saved column visibility directly into columnDefs BEFORE DataTables initializes
        for (let i = 1; i <= 18; i++) {
            const isVisible = savedCols.hasOwnProperty(i) ? Boolean(savedCols[i]) : true;
            columnDefs.push({
                targets: i - 1,
                visible: isVisible
            });
            // Update checkbox state in detached picker
            pickerEl.find('input[data-column="' + i + '"]').prop('checked', isVisible);
        }

        // Initialize DataTable with correct column visibility on first paint
        var dt = $('#saleListingTable').DataTable({
            "order": [[2, 'desc']], // Default sort by Inv#
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "scrollX": true,
            "autoWidth": false,
            "columnDefs": columnDefs,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search sales..."
            },
            dom: '<"d-flex justify-content-between align-items-center p-2"<"d-flex align-items-center gap-2"l<"column-picker-slot">>Bf>rt<"d-flex justify-content-between align-items-center p-2"ip>',
            buttons: [
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm rounded-pill px-3 me-1' },
                { extend: 'csv', className: 'btn btn-outline-success btn-sm rounded-pill px-3 me-1' },
                { extend: 'excel', className: 'btn btn-outline-primary btn-sm rounded-pill px-3 me-1' }
            ],
            initComplete: function() {
                // Insert picker element into designated slot
                $('.column-picker-slot').append(pickerEl);

                // Reveal table seamlessly without layout shifts
                $('#tableLoadingState').remove();
                $('#tableContentArea').css('opacity', '1');

                // Adjust headers to match body
                this.api().columns.adjust();
            }
        });

        // Toggle Column Picker Menu
        $(document).on('click', '#columnPickerBtn', function(e) {
            e.stopPropagation();
            $(this).closest('.column-picker-dropdown').find('.column-picker-menu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('.column-picker-menu').removeClass('show');
            }
        });

        // Handle Checkbox Change cleanly
        $(document).on('change', '.column-picker-menu input[type="checkbox"]', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            
            // Toggle column visibility
            dt.column(colIdx - 1).visible(isChecked);
            dt.columns.adjust();
            
            // Persist to localStorage
            const state = {};
            $('.column-picker-menu input[type="checkbox"]').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        });

        // Window resize adjustment
        $(window).on('resize', function() {
            dt.columns.adjust();
        });
    });
</script>
@endsection
