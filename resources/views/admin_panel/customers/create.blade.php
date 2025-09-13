@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="card shadow-sm mt-4">
                <div class="card-header text-white" style="background-color:#7bbcbe;">
                    <h4 class="mb-0 text-white">Add Customer</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label><strong>Customer ID:</strong></label>
                                <input type="text" class="form-control" name="customer_id" readonly value="{{ $latestId }}">
                            </div>
                            <div class="col-md-3">
                                <label><strong>Customer Type :</strong></label>
                                <select class="form-control" name="customer_type">
                                    <option value="Main Customer">Main Customer</option>
                                    <option value="Walking Customer">Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Customer:</strong></label>
                                <input type="text" class="form-control" name="customer_name" value="{{ old('customer_name') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="float-end"><strong>کسٹمر کا نام:</strong></label>
                                <input type="text" class="form-control text-end" dir="rtl" name="customer_name_ur" value="{{ old('customer_name_ur') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label><strong>Zone:</strong></label>
                                <select class="form-control" name="zone">
                                    @foreach ($zones as $zone)
                                    <option value="{{ $zone->zone }}">{{ $zone->zone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Sales Officer:</strong></label>
                                <select class="form-control" name="sales_oficer">
                                    @foreach ($SalesOfficer as $SalesOfficer)
                                    <option value="{{ $SalesOfficer->name }}">{{ $SalesOfficer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label><strong>NTN / CNIC no:</strong></label>
                                <input type="text" class="form-control" name="cnic" value="{{ old('cnic') }}">
                            </div>
                            <div class="col-md-3">
                                <label><strong>Filer Type:</strong></label>
                                <select class="form-control" name="filer_type">
                                    <option value="filer">Filer</option>
                                    <option value="non filer">Non Filer</option>
                                    <option value="exempt">Exempt</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label><strong>Contact Person:</strong></label>
                                <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person') }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Mobile#:</strong></label>
                                <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Email Address:</strong></label>
                                <input type="email" class="form-control" name="email_address" value="{{ old('email_address') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label><strong>Contact Person-2:</strong></label>
                                <input type="text" class="form-control" name="contact_person_2" value="{{ old('contact_person_2') }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Mobile# 2:</strong></label>
                                <input type="text" class="form-control" name="mobile_2" value="{{ old('mobile_2') }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Email Address 2:</strong></label>
                                <input type="email" class="form-control" name="email_address_2" value="{{ old('email_address_2') }}">
                            </div>
                        </div>

                        <!-- Remove Debit and Credit, Add Opening Balance -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label><strong>Opening Balance:</strong></label>
                                <input type="number" class="form-control" name="opening_balance" value="{{ old('opening_balance') }}">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label><strong>Address:</strong></label>
                                <textarea rows="3" class="form-control" name="address">{{ old('address') }}</textarea>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-success px-5">Save Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
