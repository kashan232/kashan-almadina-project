@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="row align-items-center mb-3">
                <div class="col-lg-6">
                    <h6>Vendor Payments</h6>
                </div>
                <div class="col-lg-6 text-end">
                    <button type="button" class="btn btn-primary btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="openAddModal()">+ Add Payment</button>
                </div>
            </div>

            <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form method="POST" action="{{ route('vendor.payments.store') }}" id="paymentForm">
                        @csrf
                        <input type="hidden" name="payment_id" id="payment_id">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paymentModalLabel">Add/Edit Payment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Vendor</label>
                                        <select name="vendor_id" id="vendor_id" class="form-control" required>
                                            <option value="">Select Vendor</option>
                                            @foreach($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Amount (Closing Balance)</label>
                                        <input type="number" name="amount" id="amount" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Payment Date</label>
                                        <input type="date" name="payment_date" id="payment_date" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Amount Paid</label>
                                        <input type="number" name="amount_paid" id="amount_paid" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Method</label>
                                        <input type="text" name="payment_method" id="payment_method" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Note</label>
                                        <input type="text" name="note" id="note" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success btn-sm">Save</button>
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>



            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $pay)
                            <tr>
                                <td>{{ $pay->vendor->name }}</td>
                                <td>{{ number_format($pay->amount, 2) }}</td>
                                <td>{{ $pay->payment_date }}</td>
                                <td>{{ $pay->payment_method }}</td>
                                <td>{{ $pay->note }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="openEditModal({{ $pay }})">Edit</button>
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
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("vendor_id").addEventListener("change", function() {
            let vendorId = this.value;
            if (vendorId) {
                fetch(`/vendor/${vendorId}/closing-balance`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.closing_balance !== undefined) {
                            document.getElementById("amount").value = data.closing_balance;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                document.getElementById("amount").value = '';
            }
        });
    });
</script>

<script>
    function openAddModal() {
        document.getElementById('paymentForm').reset();
        document.getElementById('payment_id').value = '';
        document.getElementById('paymentModalLabel').innerText = 'Add Payment';
    }

    function openEditModal(payment) {
        document.getElementById('paymentModalLabel').innerText = 'Edit Payment';
        document.getElementById('payment_id').value = payment.id;
        document.getElementById('vendor_id').value = payment.vendor_id;
        document.getElementById('amount').value = payment.amount;
        document.getElementById('payment_date').value = payment.payment_date;
        document.getElementById('payment_method').value = payment.payment_method;
        document.getElementById('note').value = payment.note;
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }
</script>