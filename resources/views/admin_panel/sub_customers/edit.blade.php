@extends('admin_panel.layout.app')
@section('content')

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-white">
            <h5>Edit Sub Customer</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('sub_customers.update', $sub->id) }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>SubCustomer ID:</label>
                        <input type="text" class="form-control" name="customer_id" readonly value="{{ $sub->customer_id }}">
                    </div>
                    <div class="col-md-3">
                        <label>Main Customer:</label>
                        <select name="customer_main_id" class="form-control" required>
                            @foreach($mainCustomers as $c)
                                <option value="{{ $c->id }}" {{ $c->id == $sub->customer_main_id ? 'selected' : '' }}>{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="customer_name" value="{{ $sub->customer_name }}">
                    </div>
                    <div class="col-md-3">
                        <label>Name Urdu:</label>
                        <input type="text" class="form-control text-end" dir="rtl" name="customer_name_ur" value="{{ $sub->customer_name_ur }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Zone:</label>
                        <input type="text" class="form-control" name="zone" value="{{ $sub->zone }}">
                    </div>
                    <div class="col-md-3">
                        <label>Sales Officer:</label>
                        <input type="text" class="form-control" name="sales_oficer" value="{{ $sub->sales_oficer }}">
                    </div>
                    <div class="col-md-3">
                        <label>NTN / CNIC:</label>
                        <input type="text" class="form-control" name="cnic" value="{{ $sub->cnic }}">
                    </div>
                    <div class="col-md-3">
                        <label>Filer Type:</label>
                        <select name="filer_type" class="form-control">
                            <option value="filer" {{ $sub->filer_type=='filer' ? 'selected' : '' }}>Filer</option>
                            <option value="non filer" {{ $sub->filer_type=='non filer' ? 'selected' : '' }}>Non Filer</option>
                            <option value="exempt" {{ $sub->filer_type=='exempt' ? 'selected' : '' }}>Exempt</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Contact Person:</label>
                        <input type="text" class="form-control" name="contact_person" value="{{ $sub->contact_person }}">
                    </div>
                    <div class="col-md-4">
                        <label>Mobile#:</label>
                        <input type="text" class="form-control" name="mobile" value="{{ $sub->mobile }}">
                    </div>
                    <div class="col-md-4">
                        <label>Email:</label>
                        <input type="email" class="form-control" name="email_address" value="{{ $sub->email_address }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Debit (Dr):</label>
                        <input type="number" class="form-control" name="debit" value="{{ $sub->debit }}">
                    </div>
                    <div class="col-md-6">
                        <label>Credit (Cr):</label>
                        <input type="number" class="form-control" name="credit" value="{{ $sub->credit }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Address:</label>
                    <textarea class="form-control" rows="3" name="address">{{ $sub->address }}</textarea>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary px-5">Update Sub Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
