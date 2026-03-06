@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; margin-bottom: 1rem; }
    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); border: none; }
    .nav-tabs-custom { border-bottom: 2px solid #f8f9fa; margin-bottom: 20px; }
    .nav-tabs-custom .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 10px 20px; position: relative; }
    .nav-tabs-custom .nav-link.active { color: #3b82f6; background: transparent; }
    .nav-tabs-custom .nav-link.active::after { content: ""; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: #3b82f6; }
    
    .stock-badge { min-width: 40px; display: inline-block; text-align: center; font-weight: 700; }
    .shop-col { background-color: #f0f9ff; }
    .wh-col { background-color: #fff; }
    .total-col { background-color: #f8fafc; font-weight: bold; color: #1e293b; }
    
    th { background-color: #f8fafc !important; color: #475569 !important; font-size: 12px; text-transform: uppercase; letter-spacing: 0.025em; }
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

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">📦 Warehouse Stock Management</h4>
                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('warehouse_stocks.create') }}">
                    <i class="fa fa-plus me-1"></i> New Manual Update
                </a>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-tabs-custom mb-4">
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

            @if($view == 'balances')
                {{-- Live Balances Matrix View --}}
                <div class="card shadow-sm mt-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="stockBalancesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Product Name</th>
                                        <th class="text-center shop-col">Shop Stock</th>
                                        @foreach($warehouses as $wh)
                                            <th class="text-center wh-col">{{ $wh->warehouse_name }}</th>
                                        @endforeach
                                        <th class="text-center total-col">Total Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        @php
                                            $shopStock = (float)$product->stock;
                                            $whSum = 0;
                                        @endphp
                                        <tr>
                                            <td class="text-muted small">#{{ $product->id }}</td>
                                            <td class="fw-semibold">{{ $product->name }}</td>
                                            
                                            {{-- Shop Stock Column --}}
                                            <td class="text-center shop-col">
                                                <span class="stock-badge {{ $shopStock <= 0 ? 'text-danger' : 'text-primary' }}">
                                                    {{ number_format($shopStock, 0) }}
                                                </span>
                                            </td>

                                            {{-- Warehouse Columns --}}
                                            @foreach($warehouses as $wh)
                                                @php
                                                    $whStockObj = $product->warehouseStocks->where('warehouse_id', $wh->id)->first();
                                                    $qty = $whStockObj ? (float)$whStockObj->quantity : 0;
                                                    $whSum += $qty;
                                                @endphp
                                                <td class="text-center wh-col">
                                                    @if($qty > 0)
                                                        <span class="stock-badge text-dark">{{ number_format($qty, 0) }}</span>
                                                    @else
                                                        <span class="text-muted" style="opacity: 0.3;">0</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            {{-- Grand Total --}}
                                            <td class="text-center total-col">
                                                <span class="fs-6">{{ number_format($shopStock + $whSum, 0) }}</span>
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
                    <div class="card-header border-bottom py-3">
                        <form action="{{ route('warehouse_stocks.index') }}" method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="view" value="history">
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Range</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Warehouse</label>
                                <select name="warehouse_id" class="form-select form-select-sm">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-filter"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="adjustmentTable" class="table table-striped align-middle mb-0">
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
                                        <td class="fw-bold">#{{ $adj->adj_id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($adj->date)->format('d-M-Y') }}</td>
                                        <td>{{ $adj->warehouse->warehouse_name ?? '-' }}</td>
                                        <td>
                                            @foreach($adj->items as $item)
                                                <div style="font-size:11px;" class="mb-1">
                                                    <strong>{{ $item->product->name ?? 'Unknown' }}</strong>
                                                    <span class="badge bg-light text-dark border ms-1">{{ number_format($item->qty, 0) }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="small">{{ $adj->remarks ?? '-' }}</td>
                                        <td>
                                            @if($adj->status == 'Posted')
                                                <span class="badge bg-success-subtle text-success border border-success px-2">Posted</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2">Unposted</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                @if($adj->status != 'Posted')
                                                    <form action="{{ route('warehouse_stocks.post', $adj->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light" title="Post now"><i class="fa fa-send text-primary"></i></button>
                                                    </form>
                                                    <a href="{{ route('warehouse_stocks.edit', $adj->id) }}" class="btn btn-light" title="Edit"><i class="fa fa-pencil text-warning"></i></a>
                                                @endif
                                                <a href="{{ route('warehouse_stocks.print', $adj->id) }}" target="_blank" class="btn btn-light" title="Print"><i class="fa fa-print"></i></a>
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
        @if($view == 'balances')
            $('#stockBalancesTable').DataTable({
                pageLength: 50,
                order: [[1, 'asc']],
                language: { searchPlaceholder: "Search products..." }
            });
        @else
            $('#adjustmentTable').DataTable({
                order: [[0, 'desc']],
                language: { searchPlaceholder: "Search adjustments..." }
            });
        @endif
    });
</script>
@endsection
