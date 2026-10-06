@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-dark fw-bold">Customer Closing Balance Reconciliation (Audit)</h4>
                    <p class="text-muted small mb-0">Detailed breakdown matching Customer Outstanding Report — Opening, Sales, Payments, Receipts, Returns, True Balance vs Saved Balance.</p>
                </div>
                <div>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa fa-arrow-left me-1"></i> Back to Customers
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('customers.audit') }}" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Customer Name..." value="{{ request('customer_name') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Customer Type</label>
                        <select name="customer_type" class="form-select form-select-sm fw-bold">
                            <option value="">All Types (Main & Walking)</option>
                            <option value="Main Customer" {{ request('customer_type') == 'Main Customer' ? 'selected' : '' }}>Main Customer</option>
                            <option value="Walking Customer" {{ request('customer_type') == 'Walking Customer' ? 'selected' : '' }}>Walking Customer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Group</label>
                        <select name="group_id" class="form-select form-select-sm select2">
                            <option value="">All Groups</option>
                            @foreach($userGroups as $group)
                                <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->group_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($isAdmin)
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Created By</label>
                        <select name="created_by" class="form-select form-select-sm select2">
                            <option value="">All Users (Created By)</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12 d-flex gap-2 justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fa fa-filter me-1"></i> Run Audit
                        </button>
                        <a href="{{ route('customers.audit') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="fa fa-refresh me-1"></i> Reset
                        </a>
                    </div>
                </form>
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

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle w-100 mb-0" style="font-size: 11px;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th rowspan="2">DB ID</th>
                                <th rowspan="2">Cust ID</th>
                                <th rowspan="2">Customer Name</th>
                                <th rowspan="2">Opening</th>
                                <th colspan="15" style="background:#37474f;color:#fff;">Period Transactions (Between {{ $startDate }} to {{ $endDate }})</th>
                                <th rowspan="2" style="background:#0d47a1;color:#fff;">Calculated True Balance</th>
                                <th rowspan="2" style="background:#4a148c;color:#fff;">System Saved Balance</th>
                                <th rowspan="2" style="background:#e65100;color:#fff;">Difference</th>
                                <th rowspan="2">Status</th>
                            </tr>
                            <tr>
                                <th>Sales</th>
                                <th>C. Rep</th>
                                <th>Payment</th>
                                <th>Income</th>
                                <th>JV-DR</th>
                                <th>AV-DR</th>
                                <th>CIR</th>
                                <th style="color:#ffcdd2;">Purchase</th>
                                <th>Pur Ret</th>
                                <th style="color:#ffcdd2;">S. Ret</th>
                                <th style="color:#ffcdd2;">CLM CN</th>
                                <th style="color:#ffcdd2;">Receipts</th>
                                <th style="color:#ffcdd2;">Exp / Dis</th>
                                <th style="color:#ffcdd2;">JV-CR</th>
                                <th style="color:#ffcdd2;">AV-CR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditRows as $r)
                            @php $isOk = abs($r['diff']) < 0.01; @endphp
                            <tr class="{{ !$isOk ? 'table-danger' : '' }}">
                                <td class="text-center text-muted">{{ $r['id'] }}</td>
                                <td class="text-center fw-bold text-primary">{{ $r['customer_id'] }}</td>
                                <td class="fw-bold text-dark">{{ $r['name'] }}</td>
                                <td class="text-end">{!! $fmtAdd($r['opening']) !!}</td>
                                
                                {{-- Positive Addition Fields (Black Bold) --}}
                                <td class="text-end">{!! $fmtAdd($r['sales']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['c_rep']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['payments']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['income']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['jv_dr']) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['av_dr'] ?? 0) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['cir']) !!}</td>

                                {{-- Negative Deduction Fields (Red) --}}
                                <td class="text-end">{!! $fmtSub($r['purchase'] ?? 0) !!}</td>
                                <td class="text-end">{!! $fmtAdd($r['pur_ret'] ?? 0) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['s_ret']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['clm_cn'] ?? 0) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['receipts']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['exp_dis']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['jv_cr']) !!}</td>
                                <td class="text-end">{!! $fmtSub($r['av_cr'] ?? 0) !!}</td>

                                <td class="text-end fw-bold text-primary" style="background:#f4f8fb;">
                                    {{ number_format($r['calc_balance'], 2) }}
                                </td>
                                <td class="text-end fw-bold" style="background:#faf8fc;color:#4a148c;">
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
                                <td colspan="21" class="text-center p-3 text-muted">No customer record found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($auditRows))
                        @php
                            $totals = [
                                'opening' => 0.0, 'sales' => 0.0, 'c_rep' => 0.0, 'payments' => 0.0,
                                'income' => 0.0, 'jv_dr' => 0.0, 'av_dr' => 0.0, 'cir' => 0.0,
                                'purchase' => 0.0, 'pur_ret' => 0.0, 's_ret' => 0.0, 'clm_cn' => 0.0,
                                'receipts' => 0.0, 'exp_dis' => 0.0, 'jv_cr' => 0.0, 'av_cr' => 0.0,
                                'calc_balance' => 0.0, 'saved_balance' => 0.0, 'diff' => 0.0
                            ];
                            foreach($auditRows as $r) {
                                foreach(array_keys($totals) as $k) {
                                    $totals[$k] += (float)($r[$k] ?? 0);
                                }
                            }
                        @endphp
                        <tfoot class="table-dark text-end fw-bold">
                            <tr>
                                <td colspan="3" class="text-center text-uppercase">Total Amount</td>
                                <td>{!! $fmtAdd($totals['opening']) !!}</td>
                                <td>{!! $fmtAdd($totals['sales']) !!}</td>
                                <td>{!! $fmtAdd($totals['c_rep']) !!}</td>
                                <td>{!! $fmtAdd($totals['payments']) !!}</td>
                                <td>{!! $fmtAdd($totals['income']) !!}</td>
                                <td>{!! $fmtAdd($totals['jv_dr']) !!}</td>
                                <td>{!! $fmtAdd($totals['av_dr']) !!}</td>
                                <td>{!! $fmtAdd($totals['cir']) !!}</td>
                                <td>{!! $fmtSub($totals['purchase']) !!}</td>
                                <td>{!! $fmtAdd($totals['pur_ret']) !!}</td>
                                <td>{!! $fmtSub($totals['s_ret']) !!}</td>
                                <td>{!! $fmtSub($totals['clm_cn']) !!}</td>
                                <td>{!! $fmtSub($totals['receipts']) !!}</td>
                                <td>{!! $fmtSub($totals['exp_dis']) !!}</td>
                                <td>{!! $fmtSub($totals['jv_cr']) !!}</td>
                                <td>{!! $fmtSub($totals['av_cr']) !!}</td>
                                <td class="text-primary">{{ number_format($totals['calc_balance'], 2) }}</td>
                                <td style="color:#ce93d8;">{{ number_format($totals['saved_balance'], 2) }}</td>
                                <td style="color:{{ abs($totals['diff']) < 0.01 ? '#81c784' : '#ef5350' }};">{{ number_format($totals['diff'], 2) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
