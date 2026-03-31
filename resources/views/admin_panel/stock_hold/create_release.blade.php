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
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Stock Release Management</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> New Release
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="display:none;font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: NEW
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-relase-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('stock-holds.release.bulk_store') }}" method="POST" id="stockReleaseForm" class="position-relative">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="hold_voucher_id" id="hold_voucher_id">
                <div class="posted-watermark" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Release Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Release No</label>
                                <input type="text" name="release_no" class="form-control input-sm" value="{{ $releaseNo }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-primary">Search Hold ID / Voucher</label>
                                <select id="hold_select" class="form-select select2" required>
                                    <option value="">Select Hold Voucher</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Party Details</label>
                                <input type="text" id="party_name_display" class="form-control input-sm bg-light" placeholder="Calculated from Hold..." readonly>
                                <input type="hidden" name="vendor_type" id="vendor_type">
                                <input type="hidden" name="vendor_id" id="vendor_id">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <input type="text" id="warehouse_name_display" class="form-control input-sm bg-light" placeholder="From Hold..." readonly>
                                <input type="hidden" name="warehouse_id" id="warehouse_id">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Any special notes for release...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
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
                                <tbody id="itemRows"></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Total Release Items:</th>
                                        <th class="text-center"><span id="total_items_badge" class="badge bg-secondary">0</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>
                            <a href="{{ route('stock-holds.release.add') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="display:none;">
                                <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <a href="{{ route('stock-relase-list') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    var _savedVoucherId = null;

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

    // Initialize Hold Select
    $('#hold_select').select2({
        ajax: {
            url: "{{ route('stock-holds.list.json') }}",
            dataType: 'json', delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; }
        }
    });

    // Hold Voucher Details Loading
    $('#hold_select').on('change', function() {
        var id = $(this).val();
        if(!id) return;
        $('#hold_voucher_id').val(id);
        
        $.get("{{ url('stock-holds/voucher') }}/" + id + "/details", function(res) {
            $('#vendor_type').val(res.party_type);
            $('#vendor_id').val(res.party_id);
            $('#party_name_display').val(res.party_name);
            $('#warehouse_id').val(res.warehouse_id);
            $('#warehouse_name_display').val(res.warehouse_name);
            
            $('#itemRows').empty();
            res.items.forEach(item => {
                var releaseQty = item.hold_qty || 0;
                addRow(item.product_id, item.item_name, item.sale_qty, item.hold_qty, releaseQty);
            });
        });
    });

    function addRow(pid, name, saleQty, holdQty, releaseQty) {
        var row = `<tr>
            <td class="text-center">${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td class="text-center"><input type="number" name="sale_qty[]" class="form-control input-sm text-center bg-light" value="${saleQty}" readonly></td>
            <td class="text-center"><input type="number" name="hold_qty[]" class="form-control input-sm text-center bg-light" value="${holdQty}" readonly></td>
            <td class="text-center"><input type="number" name="release_qty[]" class="form-control input-sm text-center release-qty-input" value="${releaseQty}" step="any" max="${holdQty}"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        $('#itemRows').append(row);
        updateCount();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateCount(); });
    function updateCount() { $('#total_items_badge').text($('#itemRows tr').length); }

    function save(act) {
        $('#formAction').val(act);
        if($('#itemRows tr').length === 0) { showToast('Select a Hold ID with items first', 'error'); return; }
        var $form = $('#stockReleaseForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            success: function(res) {
                if(res.success) {
                    $('#stockReleaseForm').addClass('form-locked');
                    $('#saveDraftBtn').hide();
                    $('#postBtn, #newBtn').show();
                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        showToast('Stock Released Successfully! Redirecting...', 'success');
                        setTimeout(() => window.location.href = "{{ route('stock-relase-list') }}", 1500);
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Unposted');
                        showToast('Draft Release Saved');
                    }
                } else { showToast(res.message || 'Error saving release', 'error'); }
            },
            error: function(xhr) { 
                var msg = 'Server Error';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error'); 
            },
            complete: function() { $(btn).prop('disabled', false).html(act === 'post' ? '<i class="fa fa-send"></i> Save & Post' : '<i class="fa fa-floppy-o"></i> Save Draft'); }
        });
    }

    $('#saveDraftBtn').on('click', () => save('save'));
    $('#postBtn').on('click', () => save('post'));

    $(document).on('keydown', function(e) {
        if(e.ctrlKey && e.key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'm') { e.preventDefault(); window.location.href = "{{ route('stock-holds.release.add') }}"; }
        if(e.key === 'Escape') { window.location.href = "{{ route('stock-relase-list') }}"; }
    });
});
</script>
@endsection
