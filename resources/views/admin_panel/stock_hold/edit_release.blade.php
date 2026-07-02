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
@php
    $partyName = $voucher->party_type === 'vendor'
        ? ($voucher->partyVendor->name ?? 'N/A')
        : ($voucher->partyCustomer->customer_name ?? 'N/A');
    $holdSelectValue = $voucher->claim_id
        ? 'claim:' . $voucher->claim_id
        : ($voucher->hold_voucher_id ? 'hold:' . $voucher->hold_voucher_id : '');
    $holdSelectText = $voucher->claim_id
        ? 'Claim: #' . $voucher->claim_id
        : ($voucher->holdVoucher ? 'Hold: ' . $voucher->holdVoucher->voucher_no : '');
@endphp
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">

            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Edit Stock Release</h6>
                    <span id="statusBadge" class="badge {{ $voucher->status == 'Posted' ? 'bg-success text-white' : 'bg-info text-white' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $voucher->status == 'Posted' ? 'fa-check' : 'fa-pencil' }} me-1"></i> {{ $voucher->status }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ $voucher->id }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-relase-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('stock-holds.release.update', $voucher->id) }}" method="POST" id="stockReleaseForm" class="position-relative form-locked">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="hold_voucher_id" id="hold_voucher_id" value="{{ $voucher->hold_voucher_id }}">
                <input type="hidden" name="claim_id" id="form_claim_id" value="{{ $voucher->claim_id }}">
                <div class="posted-watermark" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Release Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ $voucher->date }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Release No</label>
                                <input type="text" id="release_no" class="form-control input-sm fw-bold text-primary bg-light" value="{{ (int) preg_replace('/[^0-9]/', '', $voucher->voucher_no) ?: $voucher->voucher_no }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted mb-1">Deliver From <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select input-sm" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" @selected($voucher->warehouse_id == 0)>Shop Stock</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" @selected($voucher->warehouse_id == $wh->id)>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks }}" placeholder="Optional release notes...">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-primary mb-1">Party Type <span class="text-danger">*</span></label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="">Select Type...</option>
                                    <option value="vendor" @selected($voucher->party_type == 'vendor')>Vendor</option>
                                    <option value="customer" @selected($voucher->party_type == 'customer')>Customer</option>
                                    <option value="walkin" @selected($voucher->party_type == 'walkin')>Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small fw-bold text-primary mb-1">Code/ID</label>
                                <input type="text" id="party_code_input" class="form-control input-sm text-center fw-bold text-danger" value="{{ $voucher->party_id }}" placeholder="ID">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-primary mb-1">Party Name <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-3 rounded-pill h-100">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto"><i class="fa fa-search text-primary"></i></div>
                                        <div class="col">
                                            <label class="form-label x-small fw-bold text-primary mb-0" style="font-size:10px;">PULL FROM EXISTING HOLD / CLAIM</label>
                                            <select id="hold_select" class="form-select select2">
                                                <option value="">Select Record...</option>
                                                @if($holdSelectValue)
                                                    <option value="{{ $holdSelectValue }}" selected>{{ $holdSelectText }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MANUAL ADD ROW --}}
                <div class="card shadow-sm mb-3 border-success border-opacity-25">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1 text-center">
                                <label class="form-label small fw-bold text-success mb-1">Item ID</label>
                                <input type="text" id="manual_id_input" class="form-control input-sm text-center fw-bold" placeholder="ID">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small fw-bold text-success mb-1">Manual Product Search (Quick Add)</label>
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
                                <tbody id="itemRows">
                                    @foreach($voucher->items as $item)
                                        @php
                                            $holdQty = (float) $item->sale_qty;
                                            if ($item->hold) {
                                                $holdQty = (float) $item->hold->hold_qty + (float) $item->release_qty;
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center font-weight-bold text-primary">{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"><input type="hidden" name="hold_id[]" value="{{ $item->hold_id }}"></td>
                                            <td>{{ $item->product->name ?? 'Product' }}</td>
                                            <td class="text-center"><input type="number" name="sale_qty[]" class="form-control input-sm text-center bg-light" value="{{ (float) $item->sale_qty }}" readonly></td>
                                            <td class="text-center"><input type="number" name="hold_qty[]" class="form-control input-sm text-center bg-light" value="{{ $holdQty }}" readonly></td>
                                            <td class="text-center"><input type="number" name="release_qty[]" class="form-control input-sm text-center release-qty-input border-success" value="{{ (float) $item->release_qty }}" step="any" min="0"></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end py-2">Total Release Items:</th>
                                        <th class="text-center py-2"><span id="total_items_badge" class="badge bg-secondary">{{ count($voucher->items) }}</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm fw-bold" style="display:none;">
                                <i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm fw-bold">
                                <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm fw-bold">
                                <i class="fa fa-send me-1"></i> Update & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>
                            <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm fw-bold">
                                <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>
                            <a href="{{ route('stock-holds.release.add') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm fw-bold text-white">
                                <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <a href="{{ route('stock-relase-list') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm fw-bold text-white">
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
    var _savedVoucherId = "{{ $voucher->id }}";
    var _selectedPartyId = "{{ $voucher->party_id }}";
    var _selectedPartyName = @json($partyName);

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

    function loadParties(type, selectId, selectText) {
        $.get('{{ route("party.list") }}?type=' + type, function(data) {
            var $partySelect = $('#vendor_id');
            $partySelect.html('<option value="">Select Party...</option>');
            data.forEach(function(item) {
                $partySelect.append('<option value="' + item.id + '">' + item.text + '</option>');
            });
            if (selectId) {
                if ($partySelect.find('option[value="' + selectId + '"]').length === 0 && selectText) {
                    $partySelect.append('<option value="' + selectId + '">' + selectText + '</option>');
                }
                $partySelect.val(String(selectId));
            }
            $partySelect.trigger('change');
        });
    }

    $('#vendor_id').select2({ width: '100%', placeholder: 'Select Party...' });
    $('#hold_select').select2({ width: '100%', placeholder: 'Search Hold / Claim Record...' });

    if ($('#vendor_type').val()) {
        loadParties($('#vendor_type').val(), _selectedPartyId, _selectedPartyName);
    }

    $('#vendor_type').on('change', function() {
        loadParties($(this).val(), null, null);
        resetHoldSelect();
    });

    $('#party_code_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            var val = $(this).val();
            if (val) {
                var $option = $('#vendor_id option').filter(function() { return $(this).val() == val; });
                if ($option.length > 0) {
                    $('#vendor_id').val(val).trigger('change');
                } else {
                    showToast('Party ID not found!', 'error');
                }
            }
        }
    });

    $('#vendor_id').on('change', function() {
        var val = $(this).val();
        $('#party_code_input').val(val || '');
    });

    function resetHoldSelect() {
        $('#hold_voucher_id').val('');
        $('#form_claim_id').val('');
        $('#hold_select').html('<option value="">Select Record...</option>').trigger('change');
    }

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

    $('#hold_select').on('change', function() {
        var val = $(this).val();
        if (!val) return;

        var parts = val.split(':');
        var type = parts[0];
        var id = parts[1];

        if (type === 'claim') {
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
                res.items.forEach(function(item) {
                    addRow(item.product_id, item.item_name, item.sale_qty, item.hold_qty, item.hold_qty, item.hold_id || '');
                });
            });
        }
    });

    $('#manual_product_search').select2({
        width: '100%',
        ajax: {
            url: "{{ route('stock-holds.products.search') }}", dataType: 'json', delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data.map(function(p) { return { id: p.id, text: p.id + ' - ' + p.name, name: p.name }; }) }; }
        }
    });

    $('#manual_id_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            var id = $(this).val();
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
        if (!data) { showToast('Select a product first', 'error'); return; }
        addRow(data.id, data.name || data.text, 0, 0, 1);
        $('#manual_product_search').val(null).trigger('change');
    });

    function addRow(pid, name, saleQty, holdQty, releaseQty, holdId) {
        holdId = holdId || '';
        var row = '<tr>' +
            '<td class="text-center font-weight-bold text-primary">' + pid + ' <input type="hidden" name="product_id[]" value="' + pid + '"><input type="hidden" name="hold_id[]" value="' + holdId + '"></td>' +
            '<td>' + name + '</td>' +
            '<td class="text-center"><input type="number" name="sale_qty[]" class="form-control input-sm text-center bg-light" value="' + saleQty + '" readonly></td>' +
            '<td class="text-center"><input type="number" name="hold_qty[]" class="form-control input-sm text-center bg-light" value="' + holdQty + '" readonly></td>' +
            '<td class="text-center"><input type="number" name="release_qty[]" class="form-control input-sm text-center release-qty-input border-success" value="' + releaseQty + '" step="any" min="0"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>' +
            '</tr>';
        $('#itemRows').append(row);
        updateCount();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateCount(); });
    function updateCount() { $('#total_items_badge').text($('#itemRows tr').length); }

    function serializeForm() {
        var data = $('#stockReleaseForm').serializeArray();
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
            if (!found) data.push({ name: name, value: val });
        });
        return $.param(data);
    }

    function save(act) {
        $('#formAction').val(act);
        if ($('#itemRows tr').length === 0) { showToast('Please add at least one item', 'error'); return; }
        var $form = $('#stockReleaseForm');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: serializeForm(),
            success: function(res) {
                if (res.success) {
                    if (res.status === 'Posted') {
                        showToast('Stock Released Successfully!', 'success');
                        setTimeout(function() { window.location.href = "{{ route('stock-relase-list') }}"; }, 1200);
                    } else {
                        showToast('All changes saved successfully', 'success');
                        setTimeout(function() { window.location.reload(); }, 800);
                    }
                } else {
                    showToast(res.message || 'Error saving release', 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Server Error';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                showToast(msg, 'error');
            },
            complete: function() {
                $(btn).prop('disabled', false).html(act === 'post'
                    ? '<i class="fa fa-send me-1"></i> Update & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>'
                    : '<i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            }
        });
    }

    $('#saveDraftBtn').on('click', function() { save('save'); });
    $('#postBtn').on('click', function() { save('post'); });

    $('#editBtn').on('click', function() {
        $('#stockReleaseForm').removeClass('form-locked');
        $('#saveDraftBtn, #postBtn').show();
        $(this).hide();
    });

    $('#previewPrintBtn').on('click', function() {
        window.open("/stock-release/print/" + _savedVoucherId, "_blank");
    });

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); $('#saveDraftBtn:visible').click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); $('#postBtn:visible').click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') { e.preventDefault(); $('#previewPrintBtn:visible').click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') { e.preventDefault(); $('#editBtn:visible').click(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'm') { e.preventDefault(); window.location.href = "{{ route('stock-holds.release.add') }}"; }
        if (e.key === 'Escape') { window.location.href = "{{ route('stock-relase-list') }}"; }
    });
});
</script>
@endsection
