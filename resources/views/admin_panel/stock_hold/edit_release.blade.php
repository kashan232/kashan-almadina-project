@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 31px !important; border: 1px solid #ced4da; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: 31px !important; padding-left: 8px; }
    .select2-container .select2-selection--single .select2-selection__arrow { height: 31px !important; }
    .input-sm { height: 31px; padding: 2px 8px; font-size: 14px; }
    .table td, .table th { vertical-align: middle !important; padding: 4px !important; }
    
    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea { 
        background-color: #e9ecef !important; cursor: not-allowed !important; 
    }
    .form-locked .remove-row, .form-locked #addItemBtn { display: none !important; }
    
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(0, 128, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: {{ $voucher->status == 'Posted' ? 'block' : 'none' }};
    }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Edit Stock Release</h6>
                    <span id="statusBadge" class="badge {{ $voucher->status == 'Posted' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $voucher->status == 'Posted' ? 'fa-check' : 'fa-pencil' }} me-1"></i> {{ $voucher->status }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> {{ $voucher->voucher_no }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-relase-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                    </a>
                </div>
            </div>

            <form action="{{ route('stock-holds.release.update', $voucher->id) }}" method="POST" id="stockReleaseForm" class="position-relative {{ $voucher->status == 'Posted' ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <div class="posted-watermark {{ $voucher->status == 'Posted' ? 'show' : '' }}" id="postedWatermark">Posted</div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Release Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ $voucher->date }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Release No</label>
                                <input type="text" class="form-control input-sm bg-light" value="{{ $voucher->voucher_no }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Hold Voucher</label>
                                <input type="text" class="form-control input-sm bg-light" value="{{ $voucher->holdVoucher->voucher_no ?? 'N/A' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Party Details</label>
                                @php
                                    $partyName = '';
                                    if($voucher->party_type == 'customer' || $voucher->party_type == 'walkin') $partyName = $voucher->partyCustomer->customer_name ?? 'Walkin';
                                    else $partyName = $voucher->partyVendor->name ?? 'N/A';
                                @endphp
                                <input type="text" class="form-control input-sm bg-light" value="{{ $partyName }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <input type="text" class="form-control input-sm bg-light" value="{{ $voucher->warehouse->warehouse_name ?? 'N/A' }}" readonly>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks }}" placeholder="Any special notes for release...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width:80px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th style="width:120px;">Sale Qty</th>
                                        <th style="width:120px;">Hold Qty</th>
                                        <th style="width:120px;">Release Qty</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    @foreach($voucher->items as $item)
                                    <tr>
                                        <td class="text-center">{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                        <td>{{ $item->product->name ?? 'Product' }}</td>
                                        <td class="text-center"><input type="number" name="sale_qty[]" class="form-control input-sm text-center bg-light" value="{{ (float)$item->sale_qty }}" readonly></td>
                                        <td class="text-center"><input type="number" name="hold_qty[]" class="form-control input-sm text-center bg-light" value="{{ (float)($item->hold->hold_qty ?? 0) }}" readonly></td>
                                        <td class="text-center"><input type="number" name="release_qty[]" class="form-control input-sm text-center release-qty-input" value="{{ (float)$item->release_qty }}" step="any"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            @if($voucher->status != 'Posted')
                            <button type="button" id="updateBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                <i class="fa fa-floppy-o me-1"></i> Update Draft
                            </button>
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Update & Post
                            </button>
                            @endif
                            <a href="{{ route('stock-relase-list') }}" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-times me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function showToast(msg, type = 'success') {
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: type === 'success' ? '#28a745' : '#dc3545', color: '#fff', padding: '12px 20px', borderRadius: '8px'
        }).html(msg);
        $('body').append($toast);
        setTimeout(() => $toast.fadeOut(400, () => $toast.remove()), 3000);
    }

    function save(act) {
        $('#formAction').val(act);
        var $form = $('#stockReleaseForm');
        var btn = act === 'post' ? '#postBtn' : '#updateBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            success: function(res) {
                if(res.success) {
                    showToast('Release updated successfully! Redirecting...');
                    setTimeout(() => window.location.href = "{{ route('stock-relase-list') }}", 1000);
                } else { showToast(res.message, 'error'); }
            },
            error: function(xhr) { showToast('Error updating', 'error'); },
            complete: function() { $(btn).prop('disabled', false).html(act === 'post' ? 'Update & Post' : 'Update Draft'); }
        });
    }

    $('#updateBtn').on('click', () => save('save'));
    $('#postBtn').on('click', () => save('post'));
    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); });
});
</script>
@endsection
