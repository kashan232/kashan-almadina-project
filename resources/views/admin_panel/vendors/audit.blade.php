@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-dark fw-bold">Vendor Closing Balance Reconciliation (Audit)</h4>
                    <p class="text-muted small mb-0">Detailed breakdown of opening, purchases, returns, payments, calculated true balance vs system saved balance.</p>
                </div>
                <div>
                    <a href="{{ route('vendor.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa fa-arrow-left me-1"></i> Back to Vendors
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('vendors.audit') }}" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3 mt-4">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fa fa-filter me-1"></i> Run Audit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle w-100 mb-0" style="font-size: 11px;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>DB ID</th>
                                <th>Vendor ID</th>
                                <th>Vendor Name</th>
                                <th>Initial Opening</th>
                                <th>Total Purchases</th>
                                <th>Pur. Ret</th>
                                <th>Total Payments</th>
                                <th>Receipts</th>
                                <th>Income</th>
                                <th>S. Ret</th>
                                <th>Sales</th>
                                <th>Exp / Dis</th>
                                <th style="background:#0d47a1;color:#fff;">Calculated True Balance</th>
                                <th style="background:#4a148c;color:#fff;">System Saved Balance</th>
                                <th style="background:#e65100;color:#fff;">Difference</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditRows as $r)
                            @php $isOk = abs($r['diff']) < 0.01; @endphp
                            <tr class="{{ !$isOk ? 'table-danger' : '' }}">
                                <td class="text-center text-muted">{{ $r['id'] }}</td>
                                <td class="text-center fw-bold text-primary">{{ $r['vendor_id'] }}</td>
                                <td class="fw-bold text-dark">{{ $r['name'] }}</td>
                                <td class="text-end fw-bold">{{ number_format($r['opening'], 0) }}</td>
                                <td class="text-end text-success">{{ number_format($r['purchases'], 0) }}</td>
                                <td class="text-end text-danger">{{ number_format($r['pur_ret'], 0) }}</td>
                                <td class="text-end text-warning">{{ number_format($r['payments'], 0) }}</td>
                                <td class="text-end text-success">{{ number_format($r['receipts'], 0) }}</td>
                                <td class="text-end">{{ number_format($r['income'], 0) }}</td>
                                <td class="text-end">{{ number_format($r['s_ret'], 0) }}</td>
                                <td class="text-end">{{ number_format($r['sales'], 0) }}</td>
                                <td class="text-end">{{ number_format($r['exp_dis'], 0) }}</td>
                                <td class="text-end fw-bold text-primary" style="background:#f4f8fb;">
                                    {{ number_format($r['calc_balance'], 2) }}
                                </td>
                                <td class="text-end fw-bold text-purple" style="background:#faf8fc;color:#4a148c;">
                                    {{ number_format($r['saved_balance'], 2) }}
                                </td>
                                <td class="text-end fw-bold" style="background:#fffaf0;color:{{ $isOk ? '#2e7d32' : '#c62828' }};">
                                    {{ number_format($r['diff'], 2) }}
                                </td>
                                <td class="text-center">
                                    @if($isOk)
                                        <span class="badge bg-success px-2 py-1" style="font-size:10px;">OK</span>
                                    @else
                                        <span class="badge bg-danger px-2 py-1" style="font-size:10px;">DIFF</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="16" class="text-center p-3 text-muted">No vendor record found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
