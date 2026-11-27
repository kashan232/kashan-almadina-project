@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-dark fw-bold">Warehouse Stock Reconciliation (Audit)</h4>
                    <p class="text-muted small mb-0">Detailed product &amp; warehouse breakdown — Opening, Purchases, Sales, Transfers, Claims, Holds/Releases, True Stock vs Saved Stock.</p>
                </div>
                <div>
                    <a href="{{ route('warehouse_stocks.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa fa-arrow-left me-1"></i> Back to Warehouse Stocks
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('warehouse_stocks.audit') }}" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm select2">
                            <option value="">All Warehouses + Shop</option>
                            <option value="0" {{ (string)$warehouseFilter === '0' ? 'selected' : '' }}>Shop Stock</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" {{ (string)$warehouseFilter === (string)$w->id ? 'selected' : '' }}>
                                    {{ $w->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Brand</label>
                        <select name="brand_id" class="form-select form-select-sm select2">
                            <option value="">All Brands</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$brandFilter === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Item Name</label>
                        <input type="text" name="product_name" class="form-control form-control-sm" placeholder="Search item..." value="{{ request('product_name') }}">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="only_diff" value="1" id="onlyDiffCheck" {{ $onlyDiff ? 'checked' : '' }}>
                            <label class="form-check-label small fw-bold text-danger" for="onlyDiffCheck">Mismatches Only</label>
                        </div>
                    </div>
                    <div class="col-md-12 d-flex gap-2 justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fa fa-filter me-1"></i> Run Audit
                        </button>
                        <a href="{{ route('warehouse_stocks.audit') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="fa fa-refresh me-1"></i> Reset
                        </a>
                        <button type="submit" form="syncStocksForm" class="btn btn-warning btn-sm rounded-pill px-3">
                            <i class="fa fa-database me-1"></i> Sync & Fix Stocks
                        </button>
                    </div>
                </form>
                <form id="syncStocksForm" action="{{ route('warehouse_stocks.sync') }}" method="POST" class="d-none" onsubmit="return confirm('Recalculate and sync all warehouse & shop stocks with the Stock Ledger?');">
                    @csrf
                </form>
            </div>
        </div>

        <!-- Formula & Reconciliation Explanation Card -->
        <div class="card border-0 shadow-sm mb-3 bg-light border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-primary mb-0">
                        <i class="fa fa-calculator me-2"></i> Stock Audit Formula &amp; Balance Reconciliation
                    </h6>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#formulaCollapse" aria-expanded="true">
                        <i class="fa fa-chevron-down"></i> Details
                    </button>
                </div>
                <div class="collapse show" id="formulaCollapse">
                    <div class="row g-2 mt-1" style="font-size: 11.5px;">
                        <div class="col-md-6">
                            <div class="p-2 rounded bg-white border h-100">
                                <div class="fw-bold text-dark mb-1"><i class="fa fa-plus-circle text-success me-1"></i> 1. Calculated True Stock (Ledger Closing):</div>
                                <div class="font-monospace text-muted mb-1">
                                    <strong>True Stock</strong> = Opening + <span class="text-dark fw-bold">[Additions (+)]</span> - <span class="text-danger fw-bold">[Deductions (-)]</span>
                                </div>
                                <ul class="list-unstyled mb-0 ps-2" style="line-height: 1.6;">
                                    <li><strong class="text-dark">Additions (+) :</strong> Purchases (PJ) + Sale Returns (SRJ) + Transfer In (TOG) + Claim In + Hold (SH)</li>
                                    <li><strong class="text-danger">Deductions (-) :</strong> Sales (SJ) + Pur Returns (PRJ) + Transfer Out (TOG) + Claim Out/Rep + Wastage + Release (SR)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 rounded bg-white border h-100">
                                <div class="fw-bold text-dark mb-1"><i class="fa fa-balance-scale text-primary me-1"></i> 2. Saved Stock &amp; Difference Formula:</div>
                                <div class="font-monospace text-muted mb-1">
                                    <strong>System Saved Stock</strong> = <code>warehouse_stocks.quantity</code> (or <code>products.stock</code> for Shop)
                                </div>
                                <div class="font-monospace text-muted mb-1">
                                    <strong>Difference</strong> = Calculated True Stock - System Saved Stock
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-success px-2 py-1"><i class="fa fa-check me-1"></i> Diff = 0 : 100% Reconciled (OK)</span>
                                    <span class="badge bg-danger px-2 py-1 ms-1"><i class="fa fa-exclamation-triangle me-1"></i> Diff ≠ 0 : Mismatch Alert</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $fmtAdd = function($v) {
                $val = (float)$v;
                if (abs($val) < 0.0001) return '-';
                return '<span class="fw-bold text-dark">' . number_format($val, 0) . '</span>';
            };
            $fmtSub = function($v) {
                $val = (float)$v;
                if (abs($val) < 0.0001) return '-';
                return '<span class="text-danger fw-bold">-' . number_format(abs($val), 0) . '</span>';
            };
        @endphp

        @forelse($groupedAudit as $whGroup)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                <div class="fw-bold" style="font-size: 13px;">
                    <i class="fa fa-warehouse me-2 text-warning"></i>
                    {{ $whGroup['warehouse_label'] }}
                    <span class="badge bg-secondary ms-2">{{ $whGroup['total_count'] }} Items</span>
                </div>
                <div>
                    @if($whGroup['mismatch_count'] > 0)
                        <span class="badge bg-danger px-2 py-1">{{ $whGroup['mismatch_count'] }} Mismatches</span>
                    @else
                        <span class="badge bg-success px-2 py-1"><i class="fa fa-check me-1"></i> All OK</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle w-100 mb-0" style="font-size: 11px;">
                        <thead class="table-secondary text-center">
                            <tr>
                                <th rowspan="2">Prod ID</th>
                                <th rowspan="2">Brand</th>
                                <th rowspan="2">Item Description</th>
                                <th rowspan="2">Opening</th>
                                <th colspan="11" style="background:#37474f;color:#fff;">Period Stock Movements ({{ $startDate }} to {{ $endDate }})</th>
                                <th rowspan="2" style="background:#0d47a1;color:#fff;">Calculated True Stock</th>
                                <th rowspan="2" style="background:#4a148c;color:#fff;">System Saved Stock</th>
                                <th rowspan="2" style="background:#e65100;color:#fff;">Difference</th>
                                <th rowspan="2">Status</th>
                            </tr>
                            <tr>
                                <th>Pur</th>
                                <th>S.Ret</th>
                                <th>Trf In</th>
                                <th>CLM In</th>
                                <th>Hold</th>
                                <th style="color:#b71c1c;">Sales</th>
                                <th style="color:#b71c1c;">Pur Ret</th>
                                <th style="color:#b71c1c;">Trf Out</th>
                                <th style="color:#b71c1c;">CLM Out</th>
                                <th style="color:#b71c1c;">Wastage</th>
                                <th style="color:#b71c1c;">Release</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($whGroup['rows'] as $r)
                            @php $isOk = abs($r['diff']) < 0.01; @endphp
                            <tr class="{{ !$isOk ? 'table-danger' : '' }}">
                                <td class="text-center text-muted">{{ $r['product_id'] }}</td>
                                <td class="text-muted">{{ $r['brand_name'] }}</td>
                                <td class="fw-bold text-dark">{{ $r['product_name'] }}</td>
                                <td class="text-end">{!! $fmtAdd($r['opening']) !!}</td>

                                {{-- Additions --}}
                                <td class="text-end">{!! $fmtAdd($r['pur']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['sales_ret']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['trf_in']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['clm_in']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['hold']) !!}</td>

                                {{-- Deductions --}}
                                <td class="text-end">{!! $fmtSub($r['sales']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['pur_ret']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['trf_out']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['clm_out']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['waste']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['release']) !!}</td>

                                <td class="text-end fw-bold text-primary" style="background:#f4f8fb;">
                                    {{ number_format($r['calc_stock'], 0) }}
                                </td>
                                <td class="text-end fw-bold" style="background:#faf8fc;color:#4a148c;">
                                    {{ number_format($r['saved_stock'], 0) }}
                                </td>
                                <td class="text-end fw-bold" style="background:#fffaf0;color:{{ $isOk ? '#2e7d32' : '#c62828' }};">
                                    {{ number_format($r['diff'], 0) }}
                                </td>
                                <td class="text-center">
                                    @if($isOk)
                                        <span class="badge bg-success px-2 py-1" style="font-size:10px;">OK</span>
                                    @else
                                        <span class="badge bg-danger px-2 py-1" style="font-size:10px;">DIFF</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @php
                            $whTotals = [
                                'opening' => 0.0, 'pur' => 0.0, 'sales_ret' => 0.0, 'trf_in' => 0.0,
                                'clm_in' => 0.0, 'hold' => 0.0, 'sales' => 0.0, 'pur_ret' => 0.0,
                                'trf_out' => 0.0, 'clm_out' => 0.0, 'waste' => 0.0, 'release' => 0.0,
                                'calc_stock' => 0.0, 'saved_stock' => 0.0, 'diff' => 0.0,
                            ];
                            foreach($whGroup['rows'] as $r) {
                                foreach(array_keys($whTotals) as $k) {
                                    $whTotals[$k] += (float)($r[$k] ?? 0);
                                }
                            }
                        @endphp
                        <tfoot class="table-dark text-end fw-bold">
                            <tr>
                                <td colspan="3" class="text-center text-uppercase">{{ $whGroup['warehouse_label'] }} Total</td>
                                <td>{!! $fmtAdd($whTotals['opening']) !!}</td>
                                <td>{!! $fmtAdd($whTotals['pur']) !!}</td>
                                <td>{!! $fmtAdd($whTotals['sales_ret']) !!}</td>
                                <td>{!! $fmtAdd($whTotals['trf_in']) !!}</td>
                                <td>{!! $fmtAdd($whTotals['clm_in']) !!}</td>
                                <td>{!! $fmtAdd($whTotals['hold']) !!}</td>
                                <td>{!! $fmtSub($whTotals['sales']) !!}</td>
                                <td>{!! $fmtSub($whTotals['pur_ret']) !!}</td>
                                <td>{!! $fmtSub($whTotals['trf_out']) !!}</td>
                                <td>{!! $fmtSub($whTotals['clm_out']) !!}</td>
                                <td>{!! $fmtSub($whTotals['waste']) !!}</td>
                                <td>{!! $fmtSub($whTotals['release']) !!}</td>
                                <td class="text-primary">{{ number_format($whTotals['calc_stock'], 0) }}</td>
                                <td style="color:#ce93d8;">{{ number_format($whTotals['saved_stock'], 0) }}</td>
                                <td style="color:{{ abs($whTotals['diff']) < 0.01 ? '#81c784' : '#ef5350' }};">{{ number_format($whTotals['diff'], 0) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4 text-muted">
                No stock record found for selected filters.
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
