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
        padding: 0!important;
    }

    #stockBalancesTable thead th, #adjustmentTable thead th {
        white-space: nowrap !important;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding-top: 4px !important;
        padding-bottom: 4px !important;
        padding-left: 6px !important;
        /* Keep default right padding for DataTables sorting icons */
        font-size: 11px;
    }
    
    #stockBalancesTable tbody td, #adjustmentTable tbody td {
        white-space: nowrap !important;
        vertical-align: middle;
        padding: 4px 8px !important;
        font-size: 12px;
        color: #333;
    }

    .shop-col { background-color: #f0f9ff; }
    .wh-col { background-color: #fff; }
    .total-col { background-color: #f8fafc; font-weight: bold; color: #1e293b; }
    .stock-badge { min-width: 40px; display: inline-block; text-align: center; font-weight: 700; }

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

    .nav-tabs-custom {
        border-bottom: 2px solid #f8f9fa;
        margin-bottom: 0px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 10px 20px;
        position: relative;
        font-size: 13px;
    }
    .nav-tabs-custom .nav-link.active {
        color: #3b82f6;
        background: transparent;
    }
    .nav-tabs-custom .nav-link.active::after {
        content: "";
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #3b82f6;
    }
    
    /* DataTable Buttons Customization */
    .dt-buttons .dt-button {
        padding: 2px 8px !important;
        font-size: 11px !important;
        line-height: 1.5 !important;
        border-radius: 4px !important;
        margin-right: 2px !important;
    }
    
    /* DataTable Search & Length Customization */
    .dataTables_filter input {
        padding: 2px 8px !important;
        height: 26px !important;
        font-size: 11px !important;
        margin-left: 4px !important;
    }
    .dataTables_filter label, .dataTables_length label {
        font-size: 11px !important;
        margin-bottom: 0 !important;
    }
    .dataTables_length select {
        padding: 2px 16px 2px 6px !important;
        height: 26px !important;
        font-size: 11px !important;
    }
    
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-0">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Header Row -->
            <div class="row mb-1">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-1 px-2">
                            <div class="row g-1 align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-cubes me-2 text-primary"></i>Warehouse Stock Management</h6>
                                </div>
                                <div class="col-md-6 text-end">
                                    <a class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm py-1" href="{{ route('warehouse_stocks.create') }}" style="font-size: 11px;">
                                        <i class="fa fa-plus me-1"></i> Manual Update
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="card border-0 shadow-sm mb-2">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs-custom">
                        <li class="nav-item">
                            <a class="nav-link {{ $view == 'balances' ? 'active' : '' }}" href="{{ route('warehouse_stocks.index', ['view' => 'balances']) }}">
                                <i class="fa fa-list-ul me-1"></i> Current Stock Balances
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $view == 'history' ? 'active' : '' }}" href="{{ route('warehouse_stocks.index', ['view' => 'history']) }}">
                                <i class="fa fa-history me-1"></i> Adjustment History
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            @if($view == 'balances')
                {{-- Live Balances Matrix View --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-1 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <span class="fw-bold text-muted small text-uppercase">Stock Balance Matrix</span>
                            </div>
                            <div class="col-md-5">
                                <form action="{{ route('warehouse_stocks.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                                    <input type="hidden" name="view" value="balances">
                                    <select name="filter_warehouse_id[]" class="form-select form-select-sm select2" multiple="multiple" data-placeholder="All Warehouses">
                                        @foreach($allWarehouses as $aw)
                                            <option value="{{ $aw->id }}" {{ in_array($aw->id, request('filter_warehouse_id', [])) ? 'selected' : '' }}>{{ $aw->warehouse_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm py-1 px-2" style="font-size: 11px;"><i class="fa fa-filter"></i> Filter</button>
                                    <a href="{{ route('warehouse_stocks.index', ['view' => 'balances']) }}" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11px;">Reset</a>
                                </form>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-pill" style="font-size: 11px;" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow text-start" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Product Name</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Brand</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Shop Stock</label>
                                        @php $colCounter = 5; @endphp
                                        @foreach($warehouses as $wh)
                                            <label class="column-picker-item"><input type="checkbox" data-column="{{ $colCounter++ }}" checked> {{ $wh->warehouse_name }}</label>
                                        @endforeach
                                        <label class="column-picker-item"><input type="checkbox" data-column="{{ $colCounter++ }}" checked> Total Reserved</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="{{ $colCounter++ }}" checked> Total Physical</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="{{ $colCounter++ }}" checked> Total Available</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0" id="stockBalancesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Product Name</th>
                                        <th>Brand</th>
                                        <th class="text-center shop-col">Shop Stock</th>
                                        @foreach($warehouses as $wh)
                                            <th class="text-center wh-col text-primary" style="border-left: 1px solid #e2e8f0;">{{ $wh->warehouse_name }}</th>
                                        @endforeach
                                        <th class="text-center text-danger" style="background-color: #fff5f5; border-left: 2px solid #e2e8f0; width: 100px;">Total Reserved</th>
                                        <th class="text-center total-col" style="width: 110px;">Total Physical</th>
                                        <th class="text-center" style="background-color: #f0fdf4; color: #166534; font-weight: bold; width: 110px;">Total Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        @php
                                            $shopHoldSum = (float) $product->stockHolds->where('warehouse_id', 0)->sum('hold_qty');
                                            $shopStock = (float)$product->stock - $shopHoldSum;
                                            $whSum = 0;
                                            $physicalWhSum = 0;
                                            $holdSum = (float) $product->stockHolds->sum('hold_qty');
                                        @endphp
                                        <tr>
                                            <td class="text-muted small">#{{ $product->id }}</td>
                                            <td class="fw-bold text-dark">{{ $product->name }}</td>
                                            <td class="text-muted">{{ $product->brandRelation->name ?? '-' }}</td>
                                            
                                            @php
                                                $physicalShopStock = (float)$product->stock;
                                            @endphp
                                            <td class="text-center shop-col">
                                                @if($physicalShopStock != 0)
                                                    <span class="stock-badge {{ $physicalShopStock < 0 ? 'text-danger' : 'text-primary' }}">
                                                        {{ number_format($physicalShopStock, 0) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted" style="opacity: 0.3;">0</span>
                                                @endif
                                            </td>

                                            {{-- Warehouse Columns --}}
                                            @foreach($warehouses as $wh)
                                                @php
                                                    $whStockObj = $product->warehouseStocks->where('warehouse_id', $wh->id)->first();
                                                    $physicalQty = $whStockObj ? (float)$whStockObj->quantity : 0;
                                                    
                                                    $whHoldSum = (float) $product->stockHolds->where('warehouse_id', $wh->id)->sum('hold_qty');
                                                    $availableQty = $physicalQty - $whHoldSum;

                                                    $whSum += $availableQty;
                                                    $physicalWhSum += $physicalQty;
                                                @endphp
                                                <td class="text-center wh-col" style="border-left: 1px solid #f1f5f9;">
                                                    @if($physicalQty != 0)
                                                        <span class="stock-badge {{ $physicalQty < 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($physicalQty, 0) }}</span>
                                                    @else
                                                        <span class="text-muted" style="opacity: 0.3;">0</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            {{-- Total Reserved Column --}}
                                            <td class="text-center" style="background-color: #fffcfc; border-left: 2px solid #f1f5f9;">
                                                @if($holdSum > 0)
                                                    <span class="stock-badge text-danger fw-bold">-{{ number_format($holdSum, 0) }}</span>
                                                @else
                                                    <span class="text-muted" style="opacity: 0.2;">0</span>
                                                @endif
                                            </td>

                                            <td class="text-center total-col fs-6">
                                                @php 
                                                    $availableStock = ($physicalShopStock + $whSum); 
                                                    $systemStock = $availableStock + $holdSum;
                                                @endphp
                                                {{ number_format($systemStock, 0) }}
                                            </td>
                                            <td class="text-center fs-6" style="background-color: #f0fdf4; color: #166534; font-weight: bold;">
                                                {{ number_format($availableStock, 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                {{-- Adjustment History List View --}}
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white py-2 border-bottom">
                        <form action="{{ route('warehouse_stocks.index') }}" method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="view" value="history">
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Date Range</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Warehouse</label>
                                <select name="warehouse_id" class="form-select form-select-sm select2">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fa fa-filter me-1"></i> Filter</button>
                                <a href="{{ route('warehouse_stocks.index', ['view' => 'history']) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Reset</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="adjustmentTable" class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ADJ ID</th>
                                        <th>Date</th>
                                        <th>Warehouse</th>
                                        <th>Items Updated</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stocks as $adj)
                                    <tr>
                                        <td class="fw-bold text-primary">#{{ $adj->adj_id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($adj->date)->format('d-M-Y') }}</td>
                                        <td>{{ $adj->warehouse->warehouse_name ?? '-' }}</td>
                                        <td>
                                            @foreach($adj->items as $item)
                                                <div style="font-size:10.5px; border-bottom: 1px dashed #eee; padding: 1px 0;">
                                                    <strong>{{ $item->product->name ?? 'Unknown' }}</strong>
                                                    <span class="badge bg-light text-dark border ms-1 px-1 py-0">{{ number_format($item->qty, 0) }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td><small>{{ Str::limit($adj->remarks, 30) }}</small></td>
                                        <td>
                                            @if($adj->status == 'Posted')
                                                <span class="badge bg-success rounded-pill px-3">Posted</span>
                                            @else
                                                <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if($adj->status != 'Posted')
                                                    <form action="{{ route('warehouse_stocks.post', $adj->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                            <i class="fa fa-send"></i> Post
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('warehouse_stocks.edit', $adj->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;"><i class="fa fa-pencil text-dark"></i></a>
                                                @endif
                                                <a href="{{ route('warehouse_stocks.print', $adj->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print" style="height: 20px;"><i class="fa fa-print"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

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

        const storageKey = 'wh_stock_balances_cols_v1';

        @if($view == 'balances')
            var dt = $('#stockBalancesTable').DataTable({
                pageLength: 50,
                order: [[1, 'asc']],
                autoWidth: false,
                language: { searchPlaceholder: "Search products..." },
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5',
                    'print'
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
        @else
            $('#adjustmentTable').DataTable({
                pageLength: 50,
                order: [[0, 'desc']],
                autoWidth: false,
                language: { searchPlaceholder: "Search history..." }
            });
        @endif
    });
</script>
@endsection
