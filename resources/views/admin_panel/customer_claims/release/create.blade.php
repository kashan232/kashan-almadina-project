@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 31px !important; border: 1px solid #ced4da; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: 31px !important; padding-left: 8px; font-size: 13px; }
    .select2-container .select2-selection--single .select2-selection__arrow { height: 31px !important; }
    .input-sm { height: 31px; padding: 2px 8px; font-size: 13px; }
    .table td, .table th { vertical-align: middle !important; padding: 4px !important; font-size: 13px; }
    
    .form-label { font-size: 12px; font-weight: 700; margin-bottom: 2px; }

    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea { 
        background-color: #e9ecef !important; cursor: not-allowed !important; 
    }
    
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(255, 0, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(255, 0, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
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
                    <h6 class="page-title mb-0 fw-bold">Customer Claim Release</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> New Release
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="display:none;font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: NEW
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('customer-claims.release.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="#" method="POST" id="releaseForm" class="position-relative">
                @csrf
                <input type="hidden" name="id" id="release_id" value="">
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="product_id" id="product_id">
                
                <div class="posted-watermark" id="postedWatermark">Posted</div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Release No</label>
                                <input type="text" class="form-control input-sm bg-light fw-bold text-primary" value="{{ $releaseNo }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Release Date</label>
                                <input type="date" name="release_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label text-primary">Party Type <span class="text-danger">*</span></label>
                                <select name="party_type" id="party_type_select" class="form-select form-select-sm" required>
                                    <option value="">Select Type...</option>
                                    <option value="customer">Customer</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="walkin">Walkin</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-primary">Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id_select" class="form-select select2" required disabled>
                                    <option value="">Choose Type First...</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label text-success fw-bold">Search Claim Hold <span class="text-danger">*</span></label>
                                <select name="claim_id" id="claim_id" class="form-select select2" required disabled>
                                    <option value="">Choose Party First...</option>
                                </select>
                            </div>

                            <div class="col-md-3 mt-1">
                                <label class="form-label border-primary border-bottom font-weight-bold">Deliver From <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select input-sm" required>
                                    <option value="0">Shop</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-9 mt-1">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Release notes...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width:100px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th style="width:150px;">Hold Qty</th>
                                        <th style="width:150px;">Release Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    <tr id="emptyRow">
                                        <td colspan="4" class="text-center text-muted py-3">Select a Hold Claim to see item details</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="card shadow-sm">
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>
                            <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
                                <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>
                            <a href="{{ route('customer-claims.release.create') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="display:none;">
                                <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <a href="{{ route('customer-claims.release.index') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
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

    function showToast(msg, type = 'success') {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', display: 'flex', alignItems: 'center', gap: '8px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(() => $toast.fadeOut(400, () => $(this).remove()), 2000);
    }

    // Party Type Change -> Load Parties
    $('#party_type_select').on('change', function() {
        var type = $(this).val();
        var $party = $('#party_id_select');
        var $claim = $('#claim_id');
        
        $party.val('').trigger('change').prop('disabled', !type);
        $claim.val('').trigger('change').prop('disabled', true);
        $('#itemRows').empty().append('<tr id="emptyRow"><td colspan="4" class="text-center text-muted py-3">Select a Hold Claim to see item details</td></tr>');

        if(type) {
            $party.html('<option value="">Loading...</option>');
            $.get("{{ route('stock-holds.party.list') }}", { type: type }, function(res) {
                var html = '<option value="">Select ' + type + '...</option>';
                res.forEach(p => html += `<option value="${p.id}">${p.text}</option>`);
                $party.html(html).trigger('change');
            });
        } else {
            $party.html('<option value="">Choose Type First...</option>');
        }
    });

    // Party Name Change -> Enable Claim search
    $('#party_id_select').on('change', function() {
        var pid = $(this).val();
        var $claim = $('#claim_id');
        $claim.val('').trigger('change').prop('disabled', !pid);
        if(!pid) {
            $claim.html('<option value="">Choose Party First...</option>');
        }
    });

    // Initialize Hold Claim Select with filters
    $('#claim_id').select2({
        ajax: {
            url: "{{ route('customer-claims.release.hold-list.json') }}",
            dataType: 'json', delay: 250,
            data: function(params) { 
                return { 
                    q: params.term,
                    party_type: $('#party_type_select').val(),
                    party_id: $('#party_id_select').val()
                }; 
            },
            processResults: function(data) { return { results: data }; }
        }
    });

    // Auto-fill on Claim Selection
    $('#claim_id').on('change', function() {
        var id = $(this).val();
        if(!id) return;
        
        $.get("{{ url('customer-claims-release/details') }}/" + id, function(res) {
            $('#product_id').val(res.product_id);
            $('#itemRows').empty();
            addItemRow(res.product_id, res.product_name, res.hold_qty);
            $('#warehouse_id').val(res.warehouse_id);
        });
    });

    function addItemRow(pid, name, qty) {
        var row = `<tr>
            <td class="text-center fw-bold">${pid}</td>
            <td>${name}</td>
            <td class="text-center">
                <input type="number" class="form-control input-sm text-center bg-light fw-bold" value="${qty}" readonly>
            </td>
            <td class="text-center">
                <input type="number" name="release_qty" class="form-control input-sm text-center fw-bold border-success" value="${qty}" step="any" max="${qty}" required>
            </td>
        </tr>`;
        $('#itemRows').append(row);
    }

    function save(act) {
        $('#formAction').val(act);
        var $form = $('#releaseForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        var originalBtnHtml = $(btn).html();
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('customer-claims.release.ajax-save') }}",
            type: "POST",
            data: $form.serialize(),
            success: function(res) {
                $(btn).prop('disabled', false).html(originalBtnHtml);
                if(res.ok) {
                    showToast(res.msg, 'success');
                    $('#release_id').val(res.id);
                    $('#idBadge').html('<i class="fa fa-tag me-1"></i> ID: ' + res.release_no).show();
                    
                    $('#releaseForm').addClass('form-locked');
                    $('#saveDraftBtn').hide();
                    $('#editBtn, #postBtn, #newBtn').show();

                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        $('#editBtn').hide();
                    } else {
                        $('#statusBadge').removeClass('bg-warning bg-success').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Unposted');
                    }
                } else {
                    showToast(res.msg, 'error');
                }
            },
            error: function() {
                $(btn).prop('disabled', false).html(originalBtnHtml);
                showToast('Save error occurred', 'error');
            }
        });
    }

    $('#saveDraftBtn').on('click', () => save('save'));
    $('#postBtn').on('click', () => save('post'));
    $('#editBtn').on('click', function() { 
        if($('#statusBadge').text().trim() === 'Posted') return;
        $('#releaseForm').removeClass('form-locked'); 
        $('#saveDraftBtn, #postBtn').show(); 
        $(this).hide(); 
    });

    $(document).on('keydown', function(e) {
        if(e.ctrlKey && e.key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'e') { e.preventDefault(); $('#editBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'm') { e.preventDefault(); window.location.href = "{{ route('customer-claims.release.create') }}"; }
        if(e.ctrlKey && e.key === 'l') { e.preventDefault(); window.location.href = "{{ route('customer-claims.release.index') }}"; }
        if(e.key === 'Escape') { window.location.href = "{{ route('customer-claims.release.index') }}"; }
    });
});
</script>
@endsection
