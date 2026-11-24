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
    
    /* Clean, natural item rows - NO inner scrollbar! */
    .sale-item-entry {
        font-size: 11px;
        line-height: 1.35;
        white-space: nowrap;
        border-bottom: 1px dashed #e2e8f0;
        padding: 1px 0;
    }
    .sale-item-entry:last-child {
        border-bottom: none;
    }
    .sale-qty-entry {
        font-size: 11px;
        line-height: 1.35;
        border-bottom: 1px dashed #e2e8f0;
        padding: 1px 0;
    }
    .sale-qty-entry:last-child {
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
                            <form id="filterForm" onsubmit="return false;">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-success text-white px-3 py-2 fs-6 rounded-pill"><i class="fa fa-shopping-cart me-1"></i> Sales List</span>
                                        
                                        <div class="input-group input-group-sm" style="width: 220px;">
                                            <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                            <input type="date" name="start_date" id="filter_start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                            <input type="date" name="end_date" id="filter_end_date" class="form-control" value="{{ request('end_date') }}">
                                        </div>

                                        <div style="min-width: 160px;">
                                            <select name="user_group_id" id="filter_user_group_id" class="form-select form-select-sm select2">
                                                <option value="">Group: All</option>
                                                @foreach($userGroups as $grp)
                                                    <option value="{{ $grp->id }}" {{ request('user_group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->group_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div style="min-width: 130px;">
                                            <select name="status" id="filter_status" class="form-select form-select-sm select2">
                                                <option value="">Status: All</option>
                                                <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                                <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                            </select>
                                        </div>

                                        <button type="button" id="btnFilter" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <button type="button" id="btnReset" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></button>
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
                                        <th style="min-width: 160px;">Items</th>
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
                                <tbody></tbody>
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
            { "type": "num", "targets": 2 },
            { "orderable": false, "targets": [0, 1, 7, 8, 9, 10, 18] }
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

        // Initialize DataTable with Server-Side AJAX Processing (Instant 0.1s load time!)
        var dt = $('#saleListingTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('sale.index') }}",
                "type": "GET",
                "data": function(d) {
                    d.start_date = $('#filter_start_date').val();
                    d.end_date = $('#filter_end_date').val();
                    d.user_group_id = $('#filter_user_group_id').val();
                    d.status = $('#filter_status').val();
                }
            },
            "columns": [
                {
                    data: 'index',
                    name: 'index',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return (meta && meta.settings) ? (meta.row + meta.settings._iDisplayStart + 1) : (data || 1);
                    }
                },
                { data: 'type', name: 'type', orderable: false, searchable: false },
                { data: 'inv_no', name: 'inv_no' },
                { data: 'manual_inv', name: 'manual_inv' },
                { data: 'date', name: 'date' },
                { data: 'party_type', name: 'party_type', searchable: false },
                { data: 'customer', name: 'customer' },
                { data: 'group', name: 'group', orderable: false, searchable: false },
                { data: 'warehouse', name: 'warehouse', orderable: false, searchable: false },
                { data: 'items', name: 'items', orderable: false },
                { data: 'item_qty', name: 'item_qty', orderable: false, searchable: false, className: 'text-center' },
                { data: 'total_qty', name: 'total_qty', searchable: false, className: 'text-center' },
                { data: 'inv_total', name: 'inv_total', searchable: false, className: 'text-end' },
                { data: 'discount', name: 'discount', searchable: false, className: 'text-end text-danger' },
                { data: 'ac_allocation', name: 'ac_allocation', searchable: false, className: 'text-end' },
                { data: 'net_payable', name: 'net_payable', searchable: false, className: 'text-end fw-bold text-primary' },
                { data: 'created_by', name: 'created_by', searchable: false },
                { data: 'status', name: 'status', searchable: false, className: 'text-center' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            "order": [[2, 'desc']], // Default sort by Inv#
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
            "scrollX": true,
            "autoWidth": false,
            "columnDefs": columnDefs,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search sales...",
                "processing": '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading data...'
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
            },
            drawCallback: function() {
                // Adjust headers after every page change/filter
                this.api().columns.adjust();
            }
        });

        // Filter button click -> redraw table via fast AJAX
        $('#btnFilter').on('click', function(e) {
            e.preventDefault();
            dt.draw();
        });

        // Enter key in filter fields
        $('#filter_start_date, #filter_end_date').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                dt.draw();
            }
        });

        // Reset button click
        $('#btnReset').on('click', function(e) {
            e.preventDefault();
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
            $('#filter_user_group_id').val('').trigger('change');
            $('#filter_status').val('').trigger('change');
            dt.draw();
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
