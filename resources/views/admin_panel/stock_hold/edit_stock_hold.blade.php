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
        font-size: 100px; color: rgba(255, 0, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(255, 0, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div style="min-width:105px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Edit Stock Hold</h6>
                    <span id="statusBadge" class="badge @if($voucher->status == 'Posted') bg-success @else bg-info @endif text-white px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> {{ $voucher->status }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ $voucher->id }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-hold-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('stock-holds.update', $voucher->id) }}" method="POST" id="stockHoldForm" class="position-relative form-locked">
                @csrf
                <input type="hidden" name="id" value="{{ $voucher->id }}">
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="sale_id" id="sale_id" value="{{ $voucher->sale_id }}">
                <div class="posted-watermark @if($voucher->status == 'Posted') show @endif" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ $voucher->date }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Type</label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="vendor" {{ $voucher->party_type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ $voucher->party_type == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ $voucher->party_type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Party</label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Voucher No</label>
                                <input type="text" name="voucher_no" class="form-control input-sm" value="{{ $voucher->voucher_no }}">
                            </div>
                            <div class="col-md-2 mt-2">
                                <label class="form-label small fw-bold">Location</label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" @if($voucher->warehouse_id == 0) selected @endif>🏠 Shop</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" @if($voucher->warehouse_id == $wh->id) selected @endif>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-10 mt-2">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks }}" placeholder="Any special notes...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MANUAL SEARCH BOX --}}
                <div class="card shadow-sm mb-3 bg-light border-primary border-opacity-25">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-10">
                                <label class="form-label small fw-bold text-primary mb-1">Manual Product Search (to add extra items)</label>
                                <select id="manual_product_search" class="form-select select2">
                                    <option value="">Search for a product...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->id }} - {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="addItemBtn" class="btn btn-primary btn-sm w-100 rounded-pill">
                                    <i class="fa fa-plus me-1"></i> Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:80px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th style="width:120px;">Sale Qty</th>
                                        <th style="width:120px;">Hold Qty</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    @foreach($voucher->items as $item)
                                        <tr>
                                            <td>{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                            <td>{{ $item->product->name ?? 'Product' }}</td>
                                            <td><input type="number" name="sale_qty[]" class="form-control input-sm text-center" value="{{ (float)$item->sale_qty }}" readonly></td>
                                            <td><input type="number" name="hold_qty[]" class="form-control input-sm text-center hold-qty-input" value="{{ (float)$item->hold_qty }}" step="any"></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Hold Items:</th>
                                        <th class="text-center"><span id="total_items_badge" class="badge bg-secondary">{{ count($voucher->items) }}</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            @if($voucher->status != 'Posted')
                                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
                                    <i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                </button>
                                <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                    <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                </button>
                                <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                </button>
                                <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                                </button>
                            @endif
                            <a href="{{ route('create-stock-hold') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <a href="{{ route('stock-hold-list') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
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
    var _savedVoucherId = "{{ $voucher->id }}";
    var _selectedPartyId = "{{ $voucher->party_id }}";

    function loadParties(type, selectId) {
        $.get("{{ route('stock-holds.party.list') }}", { type: type }, function(res) {
            var $p = $('#vendor_id').empty().append('<option value="">Select Party</option>');
            res.forEach(function(item) {
                $p.append('<option value="' + item.id + '">' + item.text + '</option>');
            });
            if (selectId) {
                $p.val(String(selectId));
            }
            $p.trigger('change');
        });
    }

    loadParties($('#vendor_type').val(), _selectedPartyId);

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

    function addRow(pid, name, saleQty = 0, holdQty = 1) {
        var row = `<tr>
            <td>${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td><input type="number" name="sale_qty[]" class="form-control input-sm text-center" value="${saleQty}" readonly></td>
            <td><input type="number" name="hold_qty[]" class="form-control input-sm text-center hold-qty-input" value="${holdQty}" step="any"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        $('#itemRows').append(row);
        updateCount();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateCount(); });
    function updateCount() { $('#total_items_badge').text($('#itemRows tr').length); }

    $('#vendor_type').on('change', function() {
        loadParties($(this).val(), null);
    });

    $('#manual_product_search').select2();

    $('#addItemBtn').on('click', function() {
        var $opt = $('#manual_product_search').find(':selected');
        var id = $opt.val();
        var name = $opt.data('name');
        
        if(!id) { showToast('Select a product first', 'error'); return; }
        addRow(id, name, 0, 1);
        $('#manual_product_search').val('').trigger('change');
    });

    function serializeForm() {
        var data = $('#stockHoldForm').serializeArray();
        ['vendor_id', 'warehouse_id', 'vendor_type'].forEach(function(name) {
            var val = $('[name="' + name + '"]').val() || '';
            var found = false;
            for (var i = 0; i < data.length; i++) {
                if (data[i].name === name) {
                    data[i].value = val;
                    found = true;
                    break;
                }
            }
            if (!found) {
                data.push({ name: name, value: val });
            }
        });
        return $.param(data);
    }

    function update(act) {
        $('#formAction').val(act);
        if($('#itemRows tr').length === 0) { showToast('Add at least one item', 'error'); return; }
        var $form = $('#stockHoldForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: serializeForm(),
            success: function(res) {
                if(res.success) {
                    if(res.status === 'Posted') {
                        showToast('Stock Hold Posted! Redirecting...', 'success');
                        setTimeout(function() { window.location.href = "{{ route('stock-hold-list') }}"; }, 1200);
                    } else {
                        showToast('All changes saved successfully', 'success');
                        setTimeout(function() { window.location.reload(); }, 800);
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: function(xhr) {
                var msg = 'Server Error';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                showToast(msg, 'error');
            },
            complete: function() {
                $(btn).prop('disabled', false).html(act === 'post'
                    ? '<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>'
                    : '<i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            }
        });
    }

    $('#saveDraftBtn').on('click', () => update('save'));
    $('#postBtn').on('click', () => update('post'));
    $('#editBtn').on('click', function() { 
        if("{{ $voucher->status }}" === 'Posted') return;
        $('#stockHoldForm').removeClass('form-locked'); 
        $('#saveDraftBtn, #postBtn').show(); 
        $('#postBtn').html('<i class="fa fa-send"></i> Update & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');
        $(this).hide(); 
    });

    $('#previewPrintBtn').on('click', function() {
        window.open("/stock-holds/print/" + _savedVoucherId, "_blank");
    });

    $(document).on('keydown', function(e) {
        if(e.ctrlKey && e.key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'e') { e.preventDefault(); $('#editBtn:visible').click(); }
        if(e.ctrlKey && e.key === 'm') { e.preventDefault(); window.location.href = "{{ route('create-stock-hold') }}"; }
        if(e.key === 'Escape') { window.location.href = "{{ route('stock-hold-list') }}"; }
    });
});
</script>
@endsection
