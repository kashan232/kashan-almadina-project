@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
    #receiptTable thead th { white-space: nowrap; background-color: #f8f9fa; color: #333; font-weight: 600; vertical-align: middle; font-size: 13px; }
    #receiptTable tbody td { white-space: nowrap; vertical-align: middle; font-size: 13px; }
    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }
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

            {{-- Filter Section --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('claim-item-receipt.index') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('claim-item-receipt.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table --}}
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Claim Item Receipt Management</h4>
                            <a class="btn btn-success btn-sm px-4 rounded-pill" href="{{ route('claim-item-receipt.create') }}">
                                <i class="fa fa-plus me-1"></i> New Claim Receipt
                            </a>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="receiptTable" class="table table-striped table-bordered display w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center">Voucher No</th>
                                            <th class="text-center">Date</th>
                                            <th>Party / Supplier</th>
                                            <th>From (Cr)</th>
                                            <th>To (Dr)</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vouchers as $v)
                                        <tr>
                                            <td class="fw-bold text-center text-primary">{{ $v->voucher_no }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                            <td>
                                                <small class="text-muted d-block" style="font-size:10px;">{{ ucfirst($v->party_type) }}</small>
                                                @if($v->party_type == 'vendor')
                                                    {{ $v->vendor->name ?? 'N/A' }}
                                                @else
                                                    {{ $v->customer->customer_name ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td><span class="text-danger"><i class="fa fa-minus-circle"></i></span> {{ $v->fromWarehouse->warehouse_name ?? 'Shop' }}</td>
                                            <td><span class="text-success"><i class="fa fa-plus-circle"></i></span> {{ $v->toWarehouse->warehouse_name ?? 'Shop' }}</td>
                                            <td class="text-center">
                                                @if($v->status == 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($v->status != 'Posted')
                                                        <form action="{{ route('claim-item-receipt.post', $v->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill" onclick="return confirm('Post this receipt?')">
                                                                Post
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('claim-item-receipt.edit', $v->id) }}" class="btn btn-outline-warning btn-sm rounded-circle">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('claim-item-receipt.print', $v->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle">
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
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#receiptTable').DataTable({
            scrollX: true, autoWidth: false, pageLength: 25, order: [[0, 'desc']]
        });
    });
</script>
@endsection
