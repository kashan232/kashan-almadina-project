@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Sub Customer Payments & Recoveries</h4>
                    <h6>Manage Sub Customer Receivables</h6>
                </div>
                <div class="page-btn d-flex justify-content-end col-lg-6">
                    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="clearPaymentForm()">Add Payment</button>
                </div>
            </div>

            @if (session()->has('success'))
            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <table class="table datanew table-bordered text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sub Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $key => $p)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $p->subCustomer->customer_name ?? 'N/A' }}</td>
                                <td>{{ number_format($p->amount, 2) }}</td>
                                <td>{{ $p->payment_date }}</td>
                                <td>{{ $p->payment_method }}</td>
                                <td>{{ $p->note }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal">
    <div class="modal-dialog">
        <form action="{{ route('sub_customers.payments.store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Sub Customer Payment</h5></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Sub Customer</label>
                        <select name="sub_customer_id" class="form-control" required>
                            <option value="">Select Sub Customer</option>
                            @foreach($subCustomers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label>Payment Date</label><input type="date" name="payment_date" class="form-control" required></div>
                    <div class="mb-2"><label>Amount</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
                    <div class="mb-2"><label>Payment Method</label><input type="text" name="payment_method" class="form-control" placeholder="Cash, Bank, etc."></div>
                    <div class="mb-2"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function clearPaymentForm() {
    $('#paymentModal select[name="sub_customer_id"]').val('');
    $('#paymentModal input[name="payment_date"]').val('');
    $('#paymentModal input[name="amount"]').val('');
    $('#paymentModal input[name="payment_method"]').val('');
    $('#paymentModal textarea[name="note"]').val('');
}
$('.datanew').DataTable({ responsive: true, pageLength: 25 });
</script>
@endpush
