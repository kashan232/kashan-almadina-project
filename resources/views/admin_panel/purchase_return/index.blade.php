@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
    #example thead th { white-space: nowrap; background-color: #f8f9fa; color: #333; font-weight: 600; vertical-align: middle; }
    #example tbody td { white-space: nowrap; vertical-align: middle; }
    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .card-header { background-color: #fff; border-bottom: 1px solid #edf2f9; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('purchase.return.home') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('purchase.return.home') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Purchase Return Management</h4>
                            <a class="btn btn-danger btn-sm px-4 rounded-pill" href="{{ route('purchase.return.add') }}">
                                <i class="fa fa-plus me-1"></i> Add Return
                            </a>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Return No</th>
                                            <th>Original Purchase</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th>Qty</th>
                                            <th>Date</th>
                                            <th>Net Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($PurchaseReturns as $ret)
                                        <tr>
                                            <td>{{ $ret->id }}</td>
                                            <td class="fw-bold">{{ $ret->invoice_no }}</td>
                                            <td>{{ $ret->purchase->invoice_no ?? 'N/A' }}</td>
                                            <td>{{ $ret->purchasable->name ?? ($ret->purchasable->customer_name ?? 'N/A') }}</td>
                                            <td class="small">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-center">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->qty }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($ret->current_date)->format('d-M-Y') }}</td>
                                            <td class="text-end fw-bold">{{ number_format($ret->net_amount, 0) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $ret->status == 'Posted' ? 'success' : 'warning' }}">
                                                    {{ $ret->status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($ret->status == 'Unposted')
                                                        <a href="{{ route('purchase.return.post', $ret->id) }}" class="btn btn-sm btn-primary" title="Post Return" onclick="return confirm('Are you sure you want to POST this return? This will update stock and ledger.')">
                                                            <i class="fa fa-send"></i> Post
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('purchase.return.print', $ret->id) }}" class="btn btn-sm btn-dark" title="Print Return" target="_blank">
                                                        <i class="fa fa-print"></i> Print
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
        $('#example').DataTable({
            destroy: true,
            scrollX: true,
            pageLength: 25,
            order: [[0, 'desc']],
        });
    });
</script>
@endsection
