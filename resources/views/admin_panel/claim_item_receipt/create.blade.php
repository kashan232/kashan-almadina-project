@extends('admin_panel.layout.app')

@section('content')
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
    .form-locked .remove-row, .form-locked #addItemBtn, .form-locked #btr_search_btn { display: none !important; }
    
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(0, 128, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Claim Item Receipt Management</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> {{ isset($voucher) ? $voucher->status : 'New Receipt' }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="{{ isset($voucher) ? '' : 'display:none;' }} font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ isset($voucher) ? $voucher->id : 'NEW' }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('claim-item-receipt.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('claim-item-receipt.ajax-save') }}" method="POST" id="receiptForm" class="position-relative {{ (isset($voucher) && $voucher->status == 'Posted') ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="id" value="{{ $voucher->id ?? '' }}">
                <div class="posted-watermark {{ (isset($voucher) && $voucher->status == 'Posted') ? 'show' : '' }}" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Receipt Date</label>
                                <input type="date" name="date" class="form-control input-sm" value="{{ $voucher->date ?? date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Receipt No</label>
                                <input type="text" class="form-control input-sm fw-bold text-primary bg-light" value="{{ $voucher->voucher_no ?? $voucherNo }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-danger mb-1">Deduct From (-) Cr</label>
                                <select name="from_warehouse_id" id="from_warehouse_id" class="form-select input-sm" required>
                                    <option value="">Select Stock Source...</option>
                                    <option value="0" {{ (isset($voucher) && $voucher->from_warehouse_id == 0) ? 'selected' : '' }}>Shop Stock</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->from_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-success mb-1">Add To (+) Dr</label>
                                <select name="to_warehouse_id" id="to_warehouse_id" class="form-select input-sm" required>
                                    <option value="">Select Receipt Wh...</option>
                                    <option value="0" {{ (isset($voucher) && $voucher->to_warehouse_id == 0) ? 'selected' : '' }}>Shop Stock</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->to_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks ?? '' }}" placeholder="Optional notes...">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-primary mb-1">Party Type <span class="text-danger">*</span></label>
                                <select name="party_type" id="party_type" class="form-select input-sm" required>
                                    <option value="">Select Type...</option>
                                    <option value="vendor" {{ (isset($voucher) && $voucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ (isset($voucher) && $voucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-primary mb-1">Supplier / Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select select2" required>
                                    <option value="">Select Party...</option>
                                    @if(isset($voucher))
                                        <option value="{{ $voucher->party_id }}" selected>
                                            @if($voucher->party_type == 'vendor')
                                                {{ $voucher->vendor->name ?? 'N/A' }}
                                            @else
                                                {{ $voucher->customer->customer_name ?? 'N/A' }}
                                            @endif
                                        </option>
                                    @endif
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-3 rounded-pill h-100 shadow-sm">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto"><i class="fa fa-barcode text-primary fs-4"></i></div>
                                        <div class="col">
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="btr_search_input" class="form-control border-primary" placeholder="Enter BTR# to fetch items (e.g. 22225)...">
                                                <button type="button" id="btr_search_btn" class="btn btn-primary px-3">
                                                    <i class="fa fa-search me-1"></i> Find BTR#
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table for Items --}}
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width:150px;">BTR#</th>
                                        <th style="width:100px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th style="width:120px;">Qty Receipt</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    @if(isset($voucher))
                                        @foreach($voucher->items as $item)
                                            <tr>
                                                <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="{{ $item->btr_no }}" readonly></td>
                                                <td class="text-center fw-bold text-primary">{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                                <td>{{ $item->product->name ?? 'N/A' }}</td>
                                                <td class="text-center"><input type="number" name="quantity[]" class="form-control input-sm text-center border-success" value="{{ $item->quantity }}" step="any" min="0"></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end py-2">Grand Total Qty:</th>
                                        <th class="text-center py-2"><span id="total_qty_badge" class="badge bg-secondary">0</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 text-end">
                        <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm fw-bold">
                            <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                        </button>
                        <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm fw-bold" style="{{ isset($voucher) ? '' : 'display:none;' }}">
                            <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                        </button>
                        <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm fw-bold" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? 'display:none;' : '' }}">
                            <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                        </button>
                        <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm fw-bold" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? '' : 'display:none;' }}">
                            <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                        </button>
                        <a href="{{ route('claim-item-receipt.create') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm fw-bold text-white" style="{{ isset($voucher) ? '' : 'display:none;' }}">
                            <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                        </a>
                        <a href="{{ route('claim-item-receipt.index') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm fw-bold text-white">
                            <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    var _savedId = "{{ $voucher->id ?? '' }}";

    function showToast(msg, type = 'success') {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', display: 'flex', alignItems: 'center', gap: '8px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3000);
    }

    // Party List Loading
    $('#party_type').on('change', function() {
        var type = $(this).val();
        if(!type) { $('#party_id').empty().append('<option value="">Select Party...</option>'); return; }
        $.get("{{ url('stock-holds/party/list') }}", { type: type }, function(res) {
            var $p = $('#party_id').empty().append('<option value="">Select Party...</option>');
            res.forEach(item => $p.append(`<option value="${item.id}">${item.text}</option>`));
            $p.trigger('change');
        });
    });

    // BTR Search
    $('#btr_search_btn').on('click', function() {
        var btr = $('#btr_search_input').val();
        if(!btr) return showToast('Please enter a BTR#', 'error');
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.get("{{ route('claim-item-receipt.fetch-btr') }}", { btr: btr }, function(res) {
            if(res.success) {
                res.data.forEach(item => addRow(item.btr_no, item.product_id, item.product_name, item.quantity));
                showToast(res.data.length + ' item(s) attached.');
                $('#btr_search_input').val('');
            } else { showToast(res.message, 'error'); }
        }).always(() => $('#btr_search_btn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> Find BTR#'));
    });

    function addRow(btr, pid, name, qty) {
        var row = `<tr>
            <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="${btr}" readonly></td>
            <td class="text-center fw-bold text-primary">${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td class="text-center"><input type="number" name="quantity[]" class="form-control input-sm text-center border-success" value="${qty}" step="any" min="0"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
        </tr>`;
        $('#itemRows').append(row);
        updateTotal();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateTotal(); });
    $(document).on('input', 'input[name="quantity[]"]', updateTotal);
    function updateTotal() {
        var total = 0;
        $('input[name="quantity[]"]').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#total_qty_badge').text(total.toFixed(2));
    }
    updateTotal();

    function save(act) {
        $('#formAction').val(act);
        if($('#itemRows tr').length === 0) { showToast('Add items first', 'error'); return; }
        var $form = $('#receiptForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            success: function(res) {
                if(res.success) {
                    _savedId = res.id;
                    $('[name="id"]').val(res.id);
                    $('#receiptForm').addClass('form-locked');
                    $('#saveDraftBtn, #postBtn').hide();
                    $('#previewPrintBtn, #editBtn, #newBtn').show();
                    $('#idBadge').text('ID: ' + res.id).show();
                    
                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        showToast('Posted Successfully!');
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        showToast('Draft Saved');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: () => $(btn).prop('disabled', false).html(act==='post'?'<i class="fa fa-send me-1"></i> Save & Post':'<i class="fa fa-floppy-o me-1"></i> Save Draft')
        });
    }

    $('#saveDraftBtn').click(() => save('save'));
    $('#postBtn').click(() => save('post'));
    $('#previewPrintBtn').click(function() {
        if(!_savedId) return showToast('Save as Draft first', 'error');
        window.open("/claim-item-receipt/print/" + _savedId, "_blank");
    });
    $('#editBtn').click(function() {
        $('#receiptForm').removeClass('form-locked');
        $('#saveDraftBtn, #postBtn').show();
        $(this).hide();
        $('#postBtn').html('<i class="fa fa-send me-1"></i> Update & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');
    });

    $(document).on('keydown', function(e) {
        if(e.ctrlKey && e.key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'p') { e.preventDefault(); $('#previewPrintBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'e') { e.preventDefault(); $('#editBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'm') { e.preventDefault(); window.location.href = "{{ route('claim-item-receipt.create') }}"; }
        if(e.key === 'Escape') { window.location.href = "{{ route('claim-item-receipt.index') }}"; }
    });
});
</script>
@endsection
