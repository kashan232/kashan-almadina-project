@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .25rem .4rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .page-top-bar { margin-bottom: .35rem !important; padding: .35rem .5rem !important; }
    .stock-hold-page .page-top-bar .page-title { font-size: .9rem !important; }
    .stock-hold-page .page-top-bar .badge { font-size: 11px !important; padding: .2rem .55rem !important; }
    .stock-hold-page .card { margin-bottom: .35rem !important; }
    .stock-hold-page .card-body { padding: .45rem .55rem !important; }
    .stock-hold-page .card-footer { padding: .45rem .55rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .4rem; --bs-gutter-y: .25rem; }
    .stock-hold-page .row.mb-3 { margin-bottom: .35rem !important; }
    .stock-hold-page .form-label { margin-bottom: .1rem !important; font-size: .72rem !important; line-height: 1.1; }
    .stock-hold-page .input-sm,
    .stock-hold-page .form-control,
    .stock-hold-page .form-select { height: 26px !important; min-height: 26px !important; padding: .1rem .4rem !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single { height: 26px !important; border: 1px solid #ced4da; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 24px !important; padding-left: 6px !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__arrow { height: 24px !important; }
    .stock-hold-page .table td, .stock-hold-page .table th { vertical-align: middle !important; padding: 2px 4px !important; font-size: .78rem !important; }
    .stock-hold-page .table .form-control { height: 24px !important; min-height: 24px !important; padding: 1px 4px !important; font-size: .75rem !important; }
    .stock-hold-page .manual-add-card .card-body { padding: .35rem .55rem !important; }
    .stock-hold-page .hold-pill-card { padding: .25rem .5rem !important; }
    .stock-hold-page #addItemBtn { height: 26px; padding: 0 .65rem; font-size: .75rem; line-height: 1.2; }
    .stock-hold-page .bottom-bar-btns { gap: .35rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .25rem .65rem !important; font-size: .78rem !important; }
    .stock-hold-page tfoot th { padding: .25rem 4px !important; }

    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea {
        background-color: #e9ecef !important; cursor: not-allowed !important;
    }
    .form-locked .remove-row, .form-locked #addItemBtn, .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn, .form-locked #newInvoiceBtn, .form-locked #realPrintBtn,
    .form-locked #postBtn, .form-locked #exitBtn, .form-locked #deleteBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }

    .form-locked.view-mode #saveDraftBtn,
    .form-locked.view-mode #editInvoiceBtn,
    .form-locked.view-mode #postBtn,
    .form-locked.view-mode #deleteBtn {
        display: inline-block !important;
        pointer-events: none !important;
        opacity: 0.55 !important;
        cursor: not-allowed !important;
    }

    .form-locked.view-mode #realPrintBtn,
    .form-locked.view-mode #exitBtn,
    .form-locked.view-mode #newInvoiceBtn {
        pointer-events: auto !important;
        opacity: 1 !important;
        display: inline-block !important;
    }
    
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
        <div class="container-fluid stock-hold-page">

            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center page-top-bar bg-light rounded shadow-sm">
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
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Release Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Release No</label>
                                <input type="text" id="release_no" class="form-control input-sm fw-bold text-primary bg-light" value="Auto-Generated" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Deliver From <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select input-sm" required>
                                    <option value="0">Shop Stock</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Optional release notes...">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Hold Account Head</label>
                                <select name="hold_account_head_id" id="hold_account_head_id" class="form-select select2 input-sm">
                                    <option value="">Select Head</option>
                                    @foreach(($accountHeads ?? []) as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Hold Account</label>
                                <select name="hold_account_id" id="hold_account_id" class="form-select select2 input-sm"><option value="">Select Account</option></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Warehouse Account Head</label>
                                <select name="warehouse_account_head_id" id="warehouse_account_head_id" class="form-select select2 input-sm">
                                    <option value="">Select Head</option>
                                    @foreach(($accountHeads ?? []) as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Warehouse Account</label>
                                <select name="warehouse_account_id" id="warehouse_account_id" class="form-select select2 input-sm"><option value="">Select Account</option></select>
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-primary">Party Type <span class="text-danger">*</span></label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="">Select Type...</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="customer">Customer</option>
                                    <option value="walkin">Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small fw-bold text-primary">Code/ID</label>
                                <input type="text" id="party_code_input" class="form-control input-sm text-center fw-bold text-danger" placeholder="ID">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-primary">Party Name <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 hold-pill-card rounded-pill h-100">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto"><i class="fa fa-search text-primary"></i></div>
                                        <div class="col">
                                            <label class="form-label x-small fw-bold text-primary mb-0" style="font-size:10px;">PULL FROM EXISTING HOLD / CLAIM</label>
                                            <select id="hold_select" class="form-select select2" disabled>
                                                <option value="">Choose Party First...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="claim_id" id="form_claim_id">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MANUAL ADD ROW (Standard Logic) --}}
                <div class="card shadow-sm manual-add-card border-success border-opacity-25">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1 text-center">
                                <label class="form-label small fw-bold text-success">Item ID</label>
                                <input type="text" id="manual_id_input" class="form-control input-sm text-center fw-bold" placeholder="ID">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small fw-bold text-success">Manual Product Search (Quick Add)</label>
                                <select id="manual_product_search" class="form-select select2">
                                    <option value="">Search for a product manually...</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="addItemBtn" class="btn btn-success btn-sm w-100 rounded-pill shadow-sm">
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
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm">
                                <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" disabled>
                                <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm">
                                <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                            </button>
                            <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" disabled title="Delete not available">
                                <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                            </button>
                            <a href="javascript:void(0)" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route('stock-relase-list') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                                E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </a>
                            <a href="{{ route('stock-holds.release.add') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                                <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@php
    $accountHeadsJson = collect($accountHeads ?? [])->map(function ($head) {
        return [
            'id' => $head->id,
            'accounts' => $head->accounts->map(function ($account) {
                return ['id' => $account->id, 'title' => $account->title];
            })->values(),
        ];
    })->values();
@endphp

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
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

    // 1. Party Selection Logic
    $('#vendor_id').select2({ width: '100%', placeholder: 'Select Party...' });
    $('.select2').select2({ width: '100%' });

    var accountHeadsData = @json($accountHeadsJson);

    function fillAccountSelect(headId, $accSelect, selectedId) {
        $accSelect.empty().append('<option value="">Select Account</option>');
        var head = accountHeadsData.find(function(h) { return String(h.id) === String(headId); });
        if (head && head.accounts) {
            head.accounts.forEach(function(a) {
                var sel = String(selectedId || '') === String(a.id) ? ' selected' : '';
                $accSelect.append('<option value="' + a.id + '"' + sel + '>' + a.title + '</option>');
            });
        }
        $accSelect.trigger('change.select2');
    }
    $('#hold_account_head_id').on('change', function() { fillAccountSelect($(this).val(), $('#hold_account_id'), null); });
    $('#warehouse_account_head_id').on('change', function() { fillAccountSelect($(this).val(), $('#warehouse_account_id'), null); });
    
    $('#vendor_type').on('change', function() {
        let type = $(this).val();
        let $partySelect = $('#vendor_id');
        $partySelect.html('<option value="">Loading...</option>');
        
        if (!type) {
            $partySelect.html('<option value="">Select Party...</option>');
            return;
        }

        $.get('{{ route("party.list") }}?type=' + type, function(data) {
            $partySelect.html('<option value="">Select Party...</option>');
            data.forEach(item => {
                $partySelect.append(`<option value="${item.id}">${item.text}</option>`);
            });
            $partySelect.trigger('change');
        });
        
        // Reset and disable hold select
        resetSelections();
    });

    // Code/ID Sync
    $('#party_code_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            let val = $(this).val();
            if (val) {
                let $option = $('#vendor_id option').filter(function() { return $(this).val() == val; });
                if ($option.length > 0) {
                    $('#vendor_id').val(val).trigger('change');
                    $('#hold_select').select2('open');
                } else {
                    showToast('Party ID not found!', 'error');
                }
            }
        }
    });

    $('#vendor_id').on('change', function() {
        let val = $(this).val();
        $('#party_code_input').val(val || '');
        if (val) {
            $('#hold_select').prop('disabled', false).html('<option value="">Select Record...</option>');
            initHoldSelect();
        } else {
            resetSelections();
        }
        $('#itemRows').empty();
        updateCount();
    });

    function resetSelections() {
        $('#hold_select').prop('disabled', true).html('<option value="">Choose Party First</option>').trigger('change');
        $('#itemRows').empty();
        updateCount();
    }

    // 2. Manual Product Entry Logic
    $('#manual_product_search').select2({
        width: '100%',
        ajax: {
            url: "{{ route('stock-holds.products.search') }}", dataType: 'json', delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data.map(p => ({ id: p.id, text: p.id + ' - ' + p.name, name: p.name })) }; }
        }
    });

    $('#manual_id_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            let id = $(this).val();
            if (id) {
                $.get("{{ url('products/get-by-id') }}/" + id, function(res) {
                    if (res && res.success) {
                        addRow(res.id, res.name, 0, 0, 1);
                        $('#manual_id_input').val('').focus();
                    } else {
                        showToast('Product not found!', 'error');
                    }
                });
            }
        }
    });

    $('#addItemBtn').on('click', function() {
        var data = $('#manual_product_search').select2('data')[0];
        if(!data) { showToast('Select a product first', 'error'); return; }
        addRow(data.id, data.name, 0, 0, 1);
        $('#manual_product_search').val(null).trigger('change');
    });

    // 3. Hold / Claim Filtering (Unified)
    function initHoldSelect() {
        $('#hold_select').select2({
            width: '100%',
            placeholder: 'Search Hold / Claim Record...',
            ajax: {
                url: "{{ route('stock-holds.list.json') }}",
                dataType: 'json', delay: 250,
                data: function(params) { 
                    return { 
                        q: params.term,
                        party_type: $('#vendor_type').val(),
                        party_id: $('#vendor_id').val(),
                        include_claims: 1
                    }; 
                },
                processResults: function(data) { return { results: data }; }
            }
        });
    }

    // 4. Selection Details
    $('#hold_select').on('change', function() {
        var val = $(this).val();
        if(!val) return;
        
        var parts = val.split(':');
        var type = parts[0];
        var id = parts[1];
        
        if(type === 'claim') {
            $('#hold_voucher_id').val('');
            $('#form_claim_id').val(id);
            $.get("{{ url('customer-claims-release/details') }}/" + id, function(res) {
                $('#warehouse_id').val(res.warehouse_id);
                $('#itemRows').empty();
                addRow(res.product_id, res.product_name, res.hold_qty, res.hold_qty, res.hold_qty, res.hold_id || '');
            });
        } else {
            $('#hold_voucher_id').val(id);
            $('#form_claim_id').val('');
            $.get("{{ url('stock-holds/voucher') }}/" + id + "/details", function(res) {
                $('#warehouse_id').val(res.warehouse_id);
                $('#itemRows').empty();
                res.items.forEach(item => {
                    addRow(item.product_id, item.item_name, item.sale_qty, item.hold_qty, item.hold_qty, item.hold_id || '');
                });
            });
        }
    });

    function addRow(pid, name, saleQty, holdQty, releaseQty, holdId) {
        holdId = holdId || '';
        var row = `<tr>
            <td class="text-center font-weight-bold text-primary">${pid} <input type="hidden" name="product_id[]" value="${pid}"><input type="hidden" name="hold_id[]" value="${holdId}"></td>
            <td>${name}</td>
            <td class="text-center"><input type="number" name="sale_qty[]" class="form-control input-sm text-center bg-light" value="${saleQty}" readonly></td>
            <td class="text-center"><input type="number" name="hold_qty[]" class="form-control input-sm text-center bg-light" value="${holdQty}" readonly></td>
            <td class="text-center"><input type="number" name="release_qty[]" class="form-control input-sm text-center release-qty-input border-success" value="${releaseQty}" step="any"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
        </tr>`;
        $('#itemRows').append(row);
        updateCount();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateCount(); });
    function updateCount() { $('#total_items_badge').text($('#itemRows tr').length); }

    var _savedVoucherId = null;
    var _saveInFlight = false;
    var _postInFlight = false;
    var saveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var postBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';

    function setReleaseFormPostedState(voucherId, printUrl) {
        _savedVoucherId = voucherId;
        $('#stockReleaseForm').addClass('form-locked view-mode');
        $('#postedWatermark').addClass('show');
        $('#statusBadge').removeClass('bg-warning bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
        $('#saveDraftBtn, #editInvoiceBtn, #postBtn, #deleteBtn').prop('disabled', true);
        $('#postBtn').html(postBtnHtml);
        $('#realPrintBtn').attr('href', printUrl || ('/stock-release/print/' + voucherId)).attr('target', '_blank');
    }

    function isReleasePostedView() {
        return $('#stockReleaseForm').hasClass('view-mode');
    }

    function serializeForm() {
        var data = $('#stockReleaseForm').serializeArray();
        ['vendor_id', 'warehouse_id', 'vendor_type', 'hold_voucher_id', 'claim_id', 'action'].forEach(function(name) {
            var val = $('[name="' + name + '"]').val() || '';
            var found = false;
            for (var i = 0; i < data.length; i++) {
                if (data[i].name === name) {
                    data[i].value = val;
                    found = true;
                    break;
                }
            }
            if (!found) data.push({ name: name, value: val });
        });
        return $.param(data);
    }

    // 4. Save Logic
    function save(act) {
        if (_saveInFlight || _postInFlight) return;
        $('#formAction').val(act);
        if($('#itemRows tr').length === 0) { showToast('Please select a record with items first', 'error'); return; }
        var $form = $('#stockReleaseForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        if (act === 'post') _postInFlight = true; else _saveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: serializeForm(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedVoucherId = res.id;
                    if (res.voucher_no) $('#release_no').val(res.voucher_no);
                    $('#realPrintBtn').attr('href', '/stock-release/print/' + res.id).attr('target', '_blank');
                    $form.attr('action', '/stock-release/update/' + res.id);

                    if(res.status === 'Posted') {
                        setReleaseFormPostedState(res.id, '/stock-release/print/' + res.id);
                        showToast('Stock Released Successfully!', 'success');
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft Saved');
                        $('#stockReleaseForm').addClass('form-locked');
                        $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                        showToast('Release Saved as Draft — Ctrl+E to edit');
                    }
                } else { showToast(res.message || 'Error saving release', 'error'); }
            },
            error: function(xhr) {
                var msg = 'Server Error';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error');
            },
            complete: function() {
                if (act === 'post') _postInFlight = false; else _saveInFlight = false;
                if (!$('#stockReleaseForm').hasClass('form-locked')) {
                    $(btn).prop('disabled', false).html(act === 'post' ? postBtnHtml : saveBtnHtml);
                }
            }
        });
    }

    function doPost() {
        if (_postInFlight || !_savedVoucherId) return;
        _postInFlight = true;
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: '/stock-release/post/' + _savedVoucherId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                var printUrl = (res && res.print_url) ? res.print_url : ('/stock-release/print/' + _savedVoucherId);
                setReleaseFormPostedState(_savedVoucherId, printUrl);
                showToast('Stock Released Successfully!', 'success');
            },
            error: function(xhr) {
                var msg = 'Post failed';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error');
                _postInFlight = false;
                $('#postBtn').prop('disabled', false).html(postBtnHtml);
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) save('save'); });
    $('#postBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;
        if ($('#stockReleaseForm').hasClass('form-locked') && _savedVoucherId) doPost();
        else save('post');
    });
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#stockReleaseForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false).html(saveBtnHtml);
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save as Draft first', 'error');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (isReleasePostedView()) {
            if (e.key === 'Escape') { e.preventDefault(); window.location.href = $('#exitBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newInvoiceBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                var href = $('#realPrintBtn').attr('href');
                if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
            }
            return;
        }
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_saveInFlight && !$('#saveDraftBtn').prop('disabled')) $('#saveDraftBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_postInFlight && !$('#postBtn').prop('disabled')) $('#postBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            var href = $('#realPrintBtn').attr('href');
            if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
            else showToast('Save as Draft first', 'error');
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            window.location.href = $('#newInvoiceBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            window.location.href = $('#exitBtn').attr('href');
        }
    }, true);
});
</script>
@endsection
