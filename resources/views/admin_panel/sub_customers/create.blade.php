@extends('admin_panel.layout.app')
@section('content')

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5>Add Sub Customer</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('sub_customers.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>SubCustomer ID:</label>
                        <input type="text" class="form-control" name="customer_id" readonly value="{{ $latestId }}">
                    </div>
                    <div class="col-md-3">
                        <label>Main Customer:</label>
                        <select name="customer_main_id" class="form-control" required>
                            <option value="">Select Main Customer</option>
                            @foreach($mainCustomers as $c)
                                <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="customer_name" value="{{ old('customer_name') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Name Urdu:</label>
                        <input type="text" class="form-control text-end" dir="rtl" name="customer_name_ur" value="{{ old('customer_name_ur') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Zone:</label>
                        <select name="zone" class="form-control">
                            @foreach($zones as $z)
                                <option value="{{ $z->zone }}">{{ $z->zone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Sales Officer:</label>
                        <select name="sales_oficer" class="form-control">
                            @foreach($SalesOfficer as $s)
                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>NTN / CNIC:</label>
                        <input type="text" class="form-control" name="cnic" value="{{ old('cnic') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Filer Type:</label>
                        <select name="filer_type" class="form-control">
                            <option value="filer">Filer</option>
                            <option value="non filer">Non Filer</option>
                            <option value="exempt">Exempt</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Contact Person:</label>
                        <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Mobile#:</label>
                        <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
                    </div>
                    <div class="col-md-4">
                        <label>Email:</label>
                        <input type="email" class="form-control" name="email_address" value="{{ old('email_address') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Debit (Dr):</label>
                        <input type="number" class="form-control" name="debit" value="{{ old('debit') }}">
                    </div>
                    <div class="col-md-6">
                        <label>Credit (Cr):</label>
                        <input type="number" class="form-control" name="credit" value="{{ old('credit') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Address:</label>
                    <textarea class="form-control" rows="3" name="address">{{ old('address') }}</textarea>
                </div>

                <div class="text-center">
                    <button class="btn btn-success px-5">Save Sub Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
