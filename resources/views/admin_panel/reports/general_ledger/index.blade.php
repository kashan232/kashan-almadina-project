@extends('admin_panel.layout.app')

@section('content')


<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fas fa-book-open me-2 text-primary"></i>General Ledger Search</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-muted small text-uppercase">Ledger Parameters</span>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('general-ledger.preview') }}" method="GET" id="ledgerForm" target="_blank">
                                <input type="hidden" name="report_mode" id="report_mode" value="details">
                                <div class="row g-3">
                                    <!-- Nature of Account -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Nature of Account</label>
                                        <select name="nature_id" id="nature_id" class="form-select form-select-sm select2">
                                            <option value="">Select Nature (Main Head)</option>
                                            @foreach($heads as $head)
                                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Head of Account -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Head of Account</label>
                                        <select name="head_id" id="head_id" class="form-select form-select-sm select2">
                                            <option value="">Select Head</option>
                                        </select>
                                    </div>

                                    <!-- A/c Code -->
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">A/c Code</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                                            <input type="text" name="ac_code" id="ac_code" class="form-control" placeholder="Enter Code">
                                        </div>
                                    </div>

                                    <!-- A/c Title -->
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">A/c Title (Account/Vendor/Customer)</label>
                                        <select name="ac_id" id="ac_id" class="form-control form-control-sm select2">
                                            <option value="">Search by Title or Name...</option>
                                            <optgroup label="Accounts">
                                                @foreach($accounts as $acc)
                                                    <option value="{{ $acc->id }}" data-type="account" data-code="{{ $acc->account_code }}" data-tel="">{{ $acc->title }} ({{ $acc->account_code }})</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Customers">
                                                @foreach($customers as $cust)
                                                    <option value="{{ $cust->id }}" data-type="customer" data-code="{{ $cust->customer_id }}" data-tel="{{ $cust->mobile }}">{{ $cust->customer_name }} ({{ $cust->customer_id }})</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Vendors">
                                                @foreach($vendors as $vend)
                                                    <option value="{{ $vend->id }}" data-type="vendor" data-code="{{ $vend->vendor_id }}" data-tel="{{ $vend->mobile }}">{{ $vend->name }} ({{ $vend->vendor_id }})</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <input type="hidden" name="ac_type" id="ac_type">
                                    </div>

                                    <!-- Tel Number -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Tel Number</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                            <input type="text" name="tel_no" id="tel_no" class="form-control" readonly placeholder="Auto-filled">
                                        </div>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Start Date</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="fas fa-calendar-alt"></i></span>
                                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ date('Y-m-01') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">End Date</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="fas fa-calendar-check"></i></span>
                                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">Orientation</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light"><i class="fas fa-print"></i></span>
                                            <select name="orientation" id="orientation" class="form-select">
                                                <option value="portrait" selected>Portrait</option>
                                                <option value="landscape">Landscape</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm flex-grow-1" onclick="generateReport('details')">
                                            <i class="fas fa-list-ul me-1"></i> Details
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm px-3 shadow-sm flex-grow-1 text-white" onclick="generateReport('summary')">
                                            <i class="fas fa-chart-pie me-1"></i> Summary
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    });

    // Initialize Select2 for Unified Search (A/c Title)
    // Initialize Select2 for A/c Title (Pre-loaded)
    $('#ac_id').select2({
        placeholder: 'Search by Title or Name...',
        width: '100%'
    });

    // Handle Title Selection
    $('#ac_id').on('select2:select', function(e) {
        var data = $(this).find(':selected').data();
        $('#ac_code').val(data.code);
        $('#tel_no').val(data.tel);
        $('#ac_type').val(data.type);
    });

    // Handle Nature Selection (Nature of Account)
    $('#nature_id').on('change', function() {
        var headId = $(this).val();
        if (headId) {
            $.ajax({
                url: "{{ url('general-ledger/get-accounts-by-head') }}/" + headId,
                type: "GET",
                success: function(data) {
                    $('#head_id').empty().append('<option value="">Select Head</option>');
                    $.each(data, function(key, value) {
                        $('#head_id').append('<option value="' + value.id + '" data-code="' + value.account_code + '">' + value.title + ' (' + value.account_code + ')</option>');
                    });
                }
            });
        }
    });

    // Handle Head Selection (Head of Account)
    $('#head_id').on('change', function() {
        var option = $(this).find('option:selected');
        var code = option.data('code');
        var id = $(this).val();
        var title = option.text().split(' (')[0];
        
        if (id) {
            $('#ac_code').val(code);
            $('#ac_type').val('account');
            
            // Set values in A/c Title select2
            var newOption = new Option(option.text(), id, true, true);
            $('#ac_id').append(newOption).trigger('change');
            $('#tel_no').val('');
        }
    });

    // Handle Code Input (Press Tab or Blur)
    $('#ac_code').on('keydown', function(e) {
        if (e.which == 9) { // Tab key
            lookupByCode($(this).val());
        }
    });

    $('#ac_code').on('blur', function() {
        if ($(this).val()) {
            lookupByCode($(this).val());
        }
    });

    function lookupByCode(code) {
        if (!code) return;
        
        $.ajax({
            url: "{{ route('general-ledger.lookup-by-code') }}",
            type: "GET",
            data: { code: code },
            success: function(data) {
                if (data.found) {
                    $('#tel_no').val(data.tel);
                    $('#ac_type').val(data.type);
                    
                    // Set values in A/c Title select2
                    var newOption = new Option(data.title + ' (' + code + ')', data.id, true, true);
                    $('#ac_id').empty().append(newOption).trigger('change');

                    if (data.type == 'account' && data.head_id) {
                        $('#nature_id').val(data.head_id).trigger('change');
                        // Wait a bit for head_id to populate then select it? 
                        // Actually it's better to just leave it as is if code is primary.
                    }
                } else {
                    // toastr.error('Account code not found');
                    $('#tel_no').val('');
                    $('#ac_type').val('');
                    $('#ac_id').val(null).trigger('change');
                }
            }
        });
    }
});

function generateReport(reportType) {
    var acId = $('#ac_id').val();
    if (!acId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select an account/party first.',
            confirmButtonColor: '#d33',
        });
        return;
    }

    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();

    if (startDate && endDate) {
        if (new Date(endDate) < new Date(startDate)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Range',
                text: 'Ending date must be greater than or equal to Start date',
                confirmButtonColor: '#d33',
            });
            return;
        }
    }
    
    $('#report_mode').val(reportType);
    $('#ledgerForm').submit();
}
</script>
@endsection
