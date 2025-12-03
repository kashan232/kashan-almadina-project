@extends('admin_panel.layout.app')
@section('content')

<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>🔄 Stock Transfer List</h5>
        <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary btn-sm">New Transfer</a>
    </div>
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card-body">
        <table class="table table-bordered table-striped" id="transferTable">
            <thead>
                <tr>
                    <th>From Warehouse</th>
                    <th>To Warehouse / Shop</th>
                    <th>Product | Quantity</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $transfers->fromWarehouse->warehouse_name }}</td>
                    <td>
                        @if($transfers->to_shop)
                        Shop
                        @else
                        {{ $transfers->toWarehouse ? $transfers->toWarehouse->warehouse_name : '-' }}
                        @endif
                    </td>
                    <td>
                        @foreach($transfers->items as $item)
                        {{ $item->product->name }} (Qty: {{ $item->quantity }})<br>
                        @endforeach
                    </td>
                    <td>{{ $transfers->remarks }}</td>
                </tr>
            </tbody>

        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#transferTable').DataTable();
    });
</script>
@endsection