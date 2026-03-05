@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <h5 class="fw-bold mb-0"><i class="fa fa-list me-2 text-primary"></i> Stock Hold List</h5>
                <a href="{{ route('create-stock-hold') }}" class="btn btn-primary btn-sm rounded-pill px-4">
                    <i class="fa fa-plus me-1"></i> Create New Hold
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-dark text-white text-center">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th style="width: 120px;">Date</th>
                                    <th>Party / Customer</th>
                                    <th>Warehouse</th>
                                    <th>Items Details</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($vouchers as $v)
                                    <tr>
                                        <td class="fw-bold">HOLD-{{ $v->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                        <td class="text-start">
                                            @if($v->party_type == 'customer' || $v->party_type == 'walkin')
                                                <i class="fa fa-user me-1 text-info"></i> {{ $v->partyCustomer->customer_name ?? 'Walkin' }}
                                            @else
                                                <i class="fa fa-truck me-1 text-warning"></i> {{ $v->partyVendor->name ?? '-' }}
                                            @endif
                                            <div class="small text-muted">{{ ucfirst($v->party_type) }}</div>
                                        </td>
                                        <td>{{ $v->warehouse->warehouse_name ?? '-' }}</td>
                                        <td class="text-start">
                                            @foreach($v->items as $item)
                                                <span class="badge bg-light text-dark border me-1 mb-1">
                                                    {{ $item->product->name ?? 'Product' }} ({{ (float)$item->hold_qty }})
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($v->status == 'Posted')
                                                <span class="badge bg-success rounded-pill px-3">Posted</span>
                                            @else
                                                <span class="badge bg-info text-white rounded-pill px-3">Unposted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                @if($v->status != 'Posted')
                                                    <a href="{{ route('stock-holds.edit', $v->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('stock-holds.post', $v->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Post this hold?')" title="Post">
                                                            <i class="fa fa-send"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="#" class="btn btn-sm btn-outline-dark" title="Print">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection