@extends('admin_panel.layout.app')
@section('content')
<div class="card">
    <div class="card-header"><h5>Stock Transfers</h5></div>
    <div class="card-body">
        <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary mb-3">New Transfer</a>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->fromWarehouse->warehouse_name ?? '—' }}</td>
                    <td>{{ $t->toWarehouse->warehouse_name ?? '—' }}</td>
                    <td>
                        @foreach($t->items as $it)
                            <div>{{ $it->product->name ?? $it->product->item_name }} — {{ $it->quantity }}</div>
                        @endforeach
                    </td>
                    <td><span class="badge bg-{{ $t->status=='pending'?'warning':($t->status=='accepted'?'success':'danger') }}">{{ ucfirst($t->status) }}</span></td>
                    <td>{{ $t->created_at }}</td>
                    <td>
                        <a href="{{ route('stock_transfers.show', $t->id) }}" class="btn btn-sm btn-info">View</a>
                        @if($t->status === 'pending')
                            <form action="{{ route('stock_transfers.accept', $t->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                <button class="btn btn-sm btn-success" onclick="return confirm('Accept and add stock to destination?')">Accept</button>
                            </form>
                            <form action="{{ route('stock_transfers.reject', $t->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Reject and return stock to source?')">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $transfers->links() }}
    </div>
</div>
@endsection
