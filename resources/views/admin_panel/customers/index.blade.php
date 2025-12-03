@extends('admin_panel.layout.app')
@section('content')
<style>
    .btn-sm i.fa-toggle-on {
        color: green;
        font-size: 20px;
    }

    .btn-sm i.fa-toggle-off {
        color: gray;
        font-size: 20px;
    }
</style>

<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0">Customer List</h5>
            <div>
                <a href="{{ route('customers.inactive') }}" class="btn btn-sm btn-secondary">Inactive</a>
                <a href="{{ route('customers.ledger') }}" class="btn btn-sm btn-info">Ledger</a>
                <a href="{{ route('customer.payments') }}" class="btn btn-sm btn-primary">Payments</a>
                <a href="{{ route('customers.create') }}" class="btn btn-sm btn-success">+ Add Customer</a>
            </div>
        </div>

        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
            <div class="alert alert-danger">
                <strong>Error:</strong> {{ session('error') }}
            </div>
            @endif
            <div class="table-responsive">
                <table id="example" class="display" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Customer ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Zone</th>
                            <th>Opening Balance</th>
                            <th>Closing Balance</th>
                            <th>Filer Type</th>
                            <th>Status</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->customer_id }}</td>
                            <td>
                                @if($customer->customer_type == 'Main Customer')
                                <span class="badge bg-success">Main Customer</span>
                                @elseif($customer->customer_type == 'Walking Customer')
                                <span class="badge bg-warning text-dark">Walking Customer</span>
                                @else
                                <span class="badge bg-secondary">{{ $customer->customer_type }}</span>
                                @endif
                            </td>
                            <td>{{ $customer->customer_name }}</td>
                            <td>{{ $customer->mobile }}</td>
                            <td>{{ $customer->zone }}</td>
                            <!-- Display the Opening and Closing Balance -->
                            <td>
                                @if ($customer->customerLedger)
                                <span
                                    class="text-success fw-bold">{{ number_format($customer->customerLedger->opening_balance, 2) }}</span>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>
                                @if ($customer->customerLedger)
                                <span
                                    class="text-success fw-bold">{{ number_format($customer->customerLedger->closing_balance, 2) }}</span>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>{{ $customer->filer_type }}</td>
                            <td>
                                @if ($customer->status === 'active')
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="d-flex">
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                    class="btn btn-sm btn-warning">Edit</a>

                                <a href="{{ route('customers.toggleStatus', $customer->id) }}"
                                    class="btn btn-sm {{ $customer->status === 'active' ? 'btn-dark' : 'btn-secondary' }}"
                                    title="Toggle Status">
                                    <i
                                        class="fa-solid {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </a>

                                @if($customer->sales_count == 0)
                                <a href="{{ route('customers.destroy', $customer->id) }}"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this customer?');">
                                    Delete
                                </a>
                                @else
                                <button class="btn btn-sm btn-secondary" disabled title="Cannot delete: customer has sales">
                                    Delete
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No customers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection