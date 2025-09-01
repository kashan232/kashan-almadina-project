@extends('admin_panel.layout.app')
@section('content')

<div class="container mt-4">
    <h3>Outstanding Amounts</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Sub Customer ID</th>
                <th>Name</th>
                <th>Outstanding Amount</th>
                <th>Reason</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($losses as $loss)
            <tr>
                <td>{{ $loss->subCustomer->customer_id ?? '-' }}</td>
                <td>{{ $loss->subCustomer->customer_name ?? '-' }}</td>
                <td>Rs. {{ number_format($loss->amount,2) }}</td>
                <td>{{ $loss->reason }}</td>
                <td>{{ $loss->created_at->format('d-m-Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
