@extends('admin_panel.layout.app')
@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="main-content">
    <div class="container-fluid">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Sales</h4>

                <div>
                    <a class="btn btn-primary" href="{{ route('sale.add') }}">Add Sale</a>
                    <a class="btn btn-primary" href="{{ route('Booking.index') }}">Booking</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="example" class="display" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Total Balance</th>
                                <th>Created At</th>
                                <th>Items</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $key => $sale)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->partyType ?? '' }}</td>

                                <td>
                                    @if($sale->partyType === 'vendor')
                                    {{ $sale->vendor->name ?? '' }}
                                    @else
                                    {{ $sale->customer->customer_name ?? '' }}
                                    @endif
                                </td>
                                <td>{{ $sale->total_balance }}</td>
                                <td>{{ $sale->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($sale->items as $item)
                                        @php
                                        $prod = $item->product;
                                        // try common name fields used in products table:
                                        $prodName = $prod->product_name ?? $prod->name ?? $prod->title ?? null;
                                        @endphp
                                        <li>
                                            {{ $prodName ?? $item->item_name ?? 'Product #'.$item->product_id }} -
                                            Qty: {{ $item->sales_qty ?? $item->quantity }} -
                                            Price: {{ $item->sales_price ?? 0 }} -
                                            discount: {{ $item->discount_amount ?? 0 }}
                                        </li>
                                        @endforeach
                                    </ul>
                                </td>

                                {{-- <td> {{  $sale->weight}}</td> --}}
                                <td class="d-flex">
                                    <a class="btn btn-warning btn-sm" href="{{ route('sale.edit', $sale->id) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a class="btn btn-success btn-sm" href="{{ route('sale.return.create', $sale->id) }}">
                                        <i class="bi bi-pencil-square"></i> sale return
                                    </a>
                                    <a class="btn btn-primary btn-sm" href="{{ route('sale.invoice', $sale->id) }}">
                                        <i class="bi bi-printer"></i> Invoice
                                    </a>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#example').DataTable({
            "order": [[5, "desc"]], // Column 5 is Created At - descending (latest first)
            "pageLength": 25,
            "language": {
                "search": "Search Sales:",
                "lengthMenu": "Show _MENU_ sales per page"
            }
        });
    });
</script>

@endsection