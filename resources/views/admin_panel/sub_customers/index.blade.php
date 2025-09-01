@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0">Sub Customer List</h5>
            <div>
                <a href="{{ url('sub_customers/payments') }}" class="btn btn-sm btn-info">Payments</a>
                <a href="{{ route('sub_customers.ledger') }}" class="btn btn-sm btn-info">Ledger</a>
                <a href="{{ route('sub_customers.create') }}" class="btn btn-sm btn-success">+ Add Sub Customer</a>
                <a href="{{ route('sub_customers.inactive') }}" class="btn btn-sm btn-success"> Customer inactive</a>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-striped text-center datatable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Main Customer</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Zone</th>
                        <th>Debit <br> Credit</th>
                        <th>Status</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subCustomers as $sub)
                    <tr>
                        <td>{{ $sub->customer_id }}</td>
                        <td>{{ $sub->mainCustomer->customer_name ?? '-' }}</td>
                        <td>{{ $sub->customer_name }}</td>
                        <td>{{ $sub->mobile }}</td>
                        <td>{{ $sub->zone }}</td>
                        <td>
                            <span class="text-success fw-bold">{{ $sub->debit }}</span><br>
                            <span class="text-danger">{{ $sub->credit }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $sub->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($sub->status) }}
                            </span>
                        </td>
                        <td class="d-flex justify-content-center">
                            <a href="{{ route('sub_customers.edit', $sub->id) }}" class="btn btn-sm btn-warning me-1">Edit</a>

                            <a href="{{ route('sub_customers.toggleStatus', $sub->id) }}" 
                               class="btn btn-sm {{ $sub->status === 'active' ? 'btn-dark' : 'btn-secondary' }} me-1"
                               title="Toggle Status">
                                <i class="fa-solid {{ $sub->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                            </a>

                            <a href="{{ route('sub_customers.destroy', $sub->id) }}" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No Sub Customers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$('.datatable').DataTable({
    responsive: true,
    pageLength: 25,
    order: [[0, "desc"]]
});
</script>
@endpush
