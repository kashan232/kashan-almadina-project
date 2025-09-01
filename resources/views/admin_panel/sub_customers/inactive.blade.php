@extends('admin_panel.layout.app')
@section('content')
<div class="container mt-4">
    <h3>Inactive Sub Customers</h3>
    <a href="{{ url('sub-customers') }}" class="btn btn-primary mb-3">← Back to Active List</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>SubCustomer ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Zone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subcustomers as $subcustomer)
            <tr>
                <td>{{ $subcustomer->customer_id }}</td>
                <td>{{ $subcustomer->customer_name }}</td>
                <td>{{ $subcustomer->mobile }}</td>
                <td>{{ $subcustomer->zone }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
