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
                    <h6 class="page-title mb-0 fw-bold">Edit Customer Claim</h6>
                    <span id="statusBadge" class="badge {{ $claim->status == 'Posted' ? 'bg-success' : 'bg-info' }} text-white px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $claim->status == 'Posted' ? 'fa-check' : 'fa-pencil' }} me-1"></i> {{ $claim->status }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ $claim->claim_no }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('customer-claims.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="#" method="POST" id="claimForm" class="position-relative {{ $claim->status == 'Posted' ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="id" id="claim_id" value="{{ $claim->id }}">
                <input type="hidden" name="action" id="formAction" value="save">
                <div class="posted-watermark {{ $claim->status == 'Posted' ? 'show' : '' }}" id="postedWatermark">Posted</div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <!-- Main Row -->
                            <div class="col-md-1">
                                <label class="form-label">Claim No</label>
                                <input type="text" class="form-control input-sm bg-light fw-bold text-primary" value="{{ $claim->claim_no }}" readonly style="font-size: 0.8rem;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ $claim->entry_date }}" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control input-sm" value="{{ $claim->entry_time ? \Carbon\Carbon::parse($claim->entry_time)->format('H:i') : '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Claim Date</label>
                                <input type="date" name="claim_date" class="form-control input-sm" value="{{ $claim->claim_date }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Claim Type <span class="text-danger">*</span></label>
                                <select name="claim_type" id="claim_type" class="form-select input-sm fw-bold">
                                    <option value="item_return" {{ $claim->claim_type == 'item_return' ? 'selected' : '' }}>Item Return</option>
                                    <option value="credit_note" {{ $claim->claim_type == 'credit_note' ? 'selected' : '' }}>Credit Note</option>
                                    <option value="claim_hold" {{ $claim->claim_type == 'claim_hold' ? 'selected' : '' }}>Claim Hold</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="row g-1">
                                    <div class="col-8">
                                        <label class="form-label">Party Type</label>
                                        <select name="party_type" id="party_type" class="form-select input-sm">
                                            <option value="customer" {{ $claim->party_type == 'customer' ? 'selected' : '' }}>Customer</option>
                                            <option value="vendor" {{ $claim->party_type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                            <option value="walkin" {{ $claim->party_type == 'walkin' ? 'selected' : '' }}>Walk-in Customer</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Code/ID</label>
                                        <input type="text" id="party_code_input" class="form-control input-sm border-danger fw-bold text-danger text-center" placeholder="ID" value="{{ $claim->party_id }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sub Dealer / Party <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select select2" required>
                                    <option value="">Search Party...</option>
                                </select>
                            </div>

                            <!-- Row 2 -->
                            <div class="col-md-3 mt-1">
                                <div class="row g-1">
                                    <div class="col-4">
                                        <label class="form-label">Item ID</label>
                                        <input type="text" id="item_id_input" class="form-control input-sm border-primary fw-bold text-primary text-center" placeholder="ID" value="{{ $claim->product_id }}">
                                    </div>
                                    <div class="col-8">
                                        <label class="form-label">Claim Item <span class="text-danger">*</span></label>
                                        <select name="product_id" id="product_id" class="form-select select2" required>
                                            <option value="">Select Battery...</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" {{ $claim->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1 mt-1">
                                <label class="form-label">MFG Date</label>
                                <input type="text" name="mfg_date" class="form-control input-sm" placeholder="BH JC BD" value="{{ $claim->mfg_date }}">
                            </div>
                            <div class="col-md-2 mt-1">
                                <label class="form-label font-weight-bold">Sales Price</label>
                                <input type="number" step="any" name="sales_price" id="sales_price" class="form-control input-sm text-end fw-bold text-danger" placeholder="0.00" readonly value="{{ $claim->sales_price }}">
                            </div>
                            <div class="col-md-2 mt-1">
                                <label class="form-label">Card No</label>
                                <input type="text" name="card_no" class="form-control input-sm" value="{{ $claim->card_no }}">
                            </div>
                            <div class="col-md-2 mt-1">
                                <label class="form-label">Bill Date</label>
                                <input type="date" name="bill_date" class="form-control input-sm" value="{{ $claim->bill_date }}">
                            </div>
                            <div class="col-md-2 mt-1" id="original_warehouse_div">
                                <label class="form-label border-danger border-bottom"><span class="text-danger"><i class="fa fa-minus-circle"></i></span> Deliver From</label>
                                <select name="original_warehouse_id" class="form-select input-sm">
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ $claim->original_warehouse_id == 0 ? 'selected' : '' }}>Shop</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $claim->original_warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mt-1">
                                <label class="form-label border-primary border-bottom font-weight-bold"><span class="text-success"><i class="fa fa-plus-circle"></i></span> Claim WH (To)</label>
                                @if(isset($isAdmin) && $isAdmin)
                                    <select name="claim_warehouse_id" class="form-select input-sm">
                                        @foreach($allClaimWarehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $claim->claim_warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                        @endforeach
                                    </select>
                                @elseif($assignedClaimWarehouse)
                                    <input type="hidden" name="claim_warehouse_id" value="{{ $assignedClaimWarehouse->id }}">
                                    <input type="text" class="form-control input-sm fw-bold text-primary bg-light" value="{{ $assignedClaimWarehouse->warehouse_name }}" readonly title="Auto-assigned based on your group">
                                @else
                                    <select name="claim_warehouse_id" class="form-select input-sm">
                                        <option value="" disabled selected>No Claim WH Assigned</option>
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-5 mt-1">
                                <label class="form-label">Fault Found / Analysis</label>
                                <textarea name="fault_found" class="form-control input-sm" rows="1" placeholder="Detail fault...">{{ $claim->fault_found }}</textarea>
                            </div>
                            <div class="col-md-5 mt-1">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control input-sm" rows="1" placeholder="General remarks...">{{ $claim->remarks }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Replacement Details -->
                <div id="replacementContainer" class="card shadow-sm mb-3 {{ $claim->claim_type != 'credit_note' ? 'd-none' : '' }} bg-light border-info border-opacity-25">
                    <div class="card-header py-1 bg-info text-white fw-bold small">CREDIT NOTE / REPLACEMENT INFO</div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="row g-1">
                                    <div class="col-3">
                                        <label class="form-label">Item ID</label>
                                        <input type="text" id="replacement_item_id_input" class="form-control input-sm border-primary fw-bold text-primary text-center" placeholder="ID" value="{{ $claim->replacement_product_id }}">
                                    </div>
                                    <div class="col-9">
                                        <label class="form-label">Replace With (Select Item)</label>
                                        <select name="replacement_product_id" id="replacement_product_id" class="form-select select2">
                                            <option value="">Select Replacement...</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" {{ $claim->replacement_product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-primary fw-bold">Sales Price</label>
                                <input type="number" step="any" name="replacement_sales_price" id="replacement_sales_price" class="form-control input-sm text-end fw-bold" placeholder="0.00" readonly value="{{ $claim->replacement_sales_price }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label border-danger border-bottom"><span class="text-danger"><i class="fa fa-minus-circle"></i></span> Deliver From</label>
                                <select name="replacement_from_warehouse_id" class="form-select input-sm">
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ $claim->replacement_from_warehouse_id == 0 ? 'selected' : '' }}>Shop</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $claim->replacement_from_warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="card shadow-sm">
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" {!! $claim->status == 'Posted' ? 'style="display:none;"' : '' !!}>
                                <i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm" {!! $claim->status == 'Posted' ? 'style="display:none;"' : '' !!}>
                                <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>
                            <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
                                <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>
                            <a href="{{ route('customer-claims.create') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" {!! $claim->status != 'Posted' ? 'style="display:none;"' : '' !!}>
                                <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <a href="{{ route('customer-claims.index') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
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
    var _savedId = "{{ $claim->id }}";
    var _isPosted = "{{ $claim->status == 'Posted' ? 'true' : 'false' }}" === 'true';

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

    // Handle Type Changes
    $('#claim_type').change(function() {
        let type = $(this).val();
        if(type === 'item_return') {
            $('#replacementContainer').addClass('d-none');
            $('#original_warehouse_div').removeClass('d-none');
            if(!_isPosted) $('#sales_price, #replacement_sales_price').prop('readonly', true);
        } else if(type === 'credit_note') {
            $('#replacementContainer').removeClass('d-none');
            $('#original_warehouse_div').addClass('d-none');
            if(!_isPosted) $('#sales_price, #replacement_sales_price').prop('readonly', false);
        } else if(type === 'claim_hold') {
            $('#replacementContainer').addClass('d-none');
            $('#original_warehouse_div').addClass('d-none');
            if(!_isPosted) $('#sales_price, #replacement_sales_price').prop('readonly', true);
        }
    });

    // Party Selection Sync
    $('#party_id').on('change', function() {
        $('#party_code_input').val($(this).val());
    });
    $('#party_code_input').on('keyup', function() {
        var val = $(this).val();
        if($('#party_id option[value="'+val+'"]').length) {
            $('#party_id').val(val).trigger('change.select2');
        }
    });

    // Item Selection Sync
    function handleItemIdLookup($input, targetDropdown, targetPriceInput, nextFocus) {
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                const id = $(this).val().trim();
                if (!id) return;
                $.get("{{ route('search-products') }}", { q: id }, function(res) {
                    if(res && res.length) {
                        var prod = res.find(p => String(p.id) === String(id)) || res[0];
                        if(prod) {
                            $(targetDropdown).val(prod.id).trigger('change.select2');
                            var price = prod.sale_price || prod.net_price || 0;
                            $(targetPriceInput).val(parseFloat(price).toFixed(2));
                            $input.val(prod.id);
                            if (nextFocus) setTimeout(() => $(nextFocus).focus(), 100);
                        }
                    }
                });
                if (e.key === 'Enter') e.preventDefault();
            }
        });
    }

    handleItemIdLookup($('#item_id_input'), '#product_id', '#sales_price', 'input[name="mfg_date"]');
    handleItemIdLookup($('#replacement_item_id_input'), '#replacement_product_id', '#replacement_sales_price', null);

    $('#product_id').on('change', function() {
        var val = $(this).val();
        $('#item_id_input').val(val);
        fetchPrice(val, '#sales_price');
    });

    $('#replacement_product_id').on('change', function() {
        var val = $(this).val();
        $('#replacement_item_id_input').val(val);
        fetchPrice(val, '#replacement_sales_price');
    });

    function fetchPrice(productId, targetInputId) {
        if(!productId) { $(targetInputId).val('0.00'); return; }
        $.get("{{ route('search-products') }}", { q: productId }, function(res) {
            if(res && res.length) {
                var prod = res.find(p => p.id == productId) || res[0];
                if(prod) {
                    var price = prod.sale_price || prod.net_price || 0;
                    $(targetInputId).val(parseFloat(price).toFixed(2));
                }
            }
        });
    }

    $('#party_type').on('change', function() {
        var type = $(this).val();
        $.get("{{ route('stock-holds.party.list') }}", { type: type }, function(res) {
            var $p = $('#party_id').empty().append('<option value="">Select Party</option>');
            res.forEach(item => {
                var selected = (item.id == "{{ $claim->party_id }}") ? "selected" : "";
                $p.append(`<option value="${item.id}" ${selected}>${item.text}</option>`);
            });
            $p.trigger('change');
        });
    });

    // Auto load initial parties
    $('#party_type').trigger('change');

    function save(act) {
        $('#formAction').val(act);
        var $form = $('#claimForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        var originalBtnHtml = $(btn).html();
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('customer-claims.ajax-save') }}",
            type: "POST",
            data: $form.serialize(),
            success: function(res) {
                $(btn).prop('disabled', false).html(originalBtnHtml);
                if(res.ok) {
                    showToast(res.msg, 'success');
                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        $('#claimForm').addClass('form-locked');
                        $('#saveDraftBtn, #postBtn, #editBtn').hide();
                        $('#newBtn').show();
                        setTimeout(() => window.location.href = "{{ route('customer-claims.index') }}", 1500);
                    } else {
                        $('#claimForm').addClass('form-locked');
                        $('#saveDraftBtn, #postBtn').hide();
                        $('#editBtn').show();
                        showToast('🔒 Form Locked — Ctrl+E to Edit', 'success');
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
        $('#claimForm').removeClass('form-locked'); 
        $('#saveDraftBtn, #postBtn').show(); 
        $(this).hide(); 
    });

    $(document).on('keydown', function(e) {
        if(e.ctrlKey) {
            var key = e.key.toLowerCase();
            if(key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
            if(key === 'enter') { e.preventDefault(); $('#postBtn:visible').click(); }
            if(key === 'p') { e.preventDefault(); $('#previewPrintBtn:visible').click(); }
            if(key === 'e') { e.preventDefault(); $('#editBtn:visible').click(); }
            if(key === 'm') { e.preventDefault(); window.location.href = "{{ route('customer-claims.create') }}"; }
            if(key === 'l') { e.preventDefault(); window.location.href = "{{ route('customer-claims.index') }}"; }
        }
        if(e.key === 'Escape') { window.location.href = "{{ route('customer-claims.index') }}"; }
    });

    if(_isPosted) {
        $('#claimForm').addClass('form-locked');
        $('#saveDraftBtn, #postBtn, #editBtn').hide();
        $('#newBtn').show();
    }
});
</script>
@endsection
