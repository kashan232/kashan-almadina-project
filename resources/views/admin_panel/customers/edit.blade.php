@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="card shadow-sm mt-4">
                <div class="card-header text-white" style="background-color:#7bbcbe;">
                    <h4 class="mb-0 text-white">Edit Customer</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                        @csrf
                        {{-- If you prefer PUT route, include: @method('PUT') --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label><strong>Customer ID:</strong></label>
                                <input type="text" class="form-control" name="customer_id" readonly value="{{ old('customer_id', $customer->customer_id) }}">
                            </div>
                            <div class="col-md-3">
                                <label><strong>Customer Type :</strong></label>
                                <select class="form-control" name="customer_type">
                                    <option value="Main Customer" {{ old('customer_type', $customer->customer_type) == 'Main Customer' ? 'selected' : '' }}>Main Customer</option>
                                    <option value="Walking Customer" {{ old('customer_type', $customer->customer_type) == 'Walking Customer' ? 'selected' : '' }}>Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Customer:</strong></label>
                                <input type="text" class="form-control" name="customer_name" value="{{ old('customer_name', $customer->customer_name) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="float-end"><strong>کسٹمر کا نام:</strong></label>
                                <input type="text" class="form-control text-end" dir="rtl" name="customer_name_ur" value="{{ old('customer_name_ur', $customer->customer_name_ur) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label><strong>Zone:</strong></label>
                                <select class="form-control" name="zone">
                                    @foreach ($zones ?? [] as $zone)
                                        <option value="{{ $zone->zone }}" {{ old('zone', $customer->zone) == $zone->zone ? 'selected' : '' }}>{{ $zone->zone }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label><strong>Sales Officer:</strong></label>
                                <select class="form-control" name="sales_oficer">
                                    @foreach ($SalesOfficer ?? [] as $so)
                                        <option value="{{ $so->name }}" {{ old('sales_oficer', $customer->sales_oficer) == $so->name ? 'selected' : '' }}>{{ $so->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label><strong>NTN / CNIC no:</strong></label>
                                <input type="text" class="form-control" name="cnic" value="{{ old('cnic', $customer->cnic) }}">
                            </div>
                            <div class="col-md-3">
                                <label><strong>Filer Type:</strong></label>
                                <select class="form-control" name="filer_type">
                                    <option value="filer" {{ old('filer_type', $customer->filer_type) == 'filer' ? 'selected' : '' }}>Filer</option>
                                    <option value="non filer" {{ old('filer_type', $customer->filer_type) == 'non filer' ? 'selected' : '' }}>Non Filer</option>
                                    <option value="exempt" {{ old('filer_type', $customer->filer_type) == 'exempt' ? 'selected' : '' }}>Exempt</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label><strong>Contact Person:</strong></label>
                                <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Mobile#:</strong></label>
                                <input type="text" class="form-control" name="mobile" value="{{ old('mobile', $customer->mobile) }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Email Address:</strong></label>
                                <input type="email" class="form-control" name="email_address" value="{{ old('email_address', $customer->email_address) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label><strong>Contact Person-2:</strong></label>
                                <input type="text" class="form-control" name="contact_person_2" value="{{ old('contact_person_2', $customer->contact_person_2) }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Mobile# 2:</strong></label>
                                <input type="text" class="form-control" name="mobile_2" value="{{ old('mobile_2', $customer->mobile_2) }}">
                            </div>
                            <div class="col-md-4">
                                <label><strong>Email Address 2:</strong></label>
                                <input type="email" class="form-control" name="email_address_2" value="{{ old('email_address_2', $customer->email_address_2) }}">
                            </div>
                        </div>

                        


                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label><strong>Opening Balance:</strong></label>
                                <input type="number" class="form-control" name="opening_balance" value="{{ old('opening_balance', $customer->opening_balance) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="float-end"><strong>Transporter (اردو):</strong></label>
                                <input rows="3" class="form-control text-end" dir="rtl" name="transport_ur" value="{{ old('transport_ur', $customer->transport_ur) }}">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label><strong>Address:</strong></label>
                                <textarea rows="3" class="form-control" name="address">{{ old('address', $customer->address) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="float-end"><strong>پتہ (اردو):</strong></label>
                                <textarea rows="3" class="form-control text-end" dir="rtl" name="address_ur">{{ old('address_ur', $customer->address_ur) }}</textarea>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary px-5">Update Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
