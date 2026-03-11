@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 31px !important;
        border: 1px solid #ced4da;
    }
    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 31px !important;
        padding-left: 8px;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 31px !important;
    }
    .input-sm { height: 31px; padding: 2px 8px; font-size: 14px; }
    .table td, .table th { vertical-align: middle !important; padding: 4px !important; }
    
    .form-locked {
        position: relative;
        pointer-events: none !important;
        opacity: 0.8;
    }
    .form-locked input, 
    .form-locked .select2-container--default .select2-selection--single, 
    .form-locked select, 
    .form-locked textarea { 
        background-color: #e9ecef !important;
        cursor: not-allowed !important;
    }
    .form-locked .remove-row, .form-locked #addRowBtn, .form-locked .btn-primary { 
        display: none !important; 
    }
    .posted-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px;
        color: rgba(255, 0, 0, 0.1);
        font-weight: bold;
        pointer-events: none;
        z-index: 1000;
        text-transform: uppercase;
        border: 10px solid rgba(255, 0, 0, 0.1);
        padding: 20px;
        border-radius: 20px;
        display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                
                {{-- LEFT: Empty for balance --}}
                <div style="min-width:80px;"></div>

                {{-- CENTER: Title + Status + ID --}}
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Manual Warehouse Stock Update</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> Draft
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="display:none;font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: NEW
                    </span>
                </div>

                {{-- RIGHT: List button --}}
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('warehouse_stocks.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                        <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('warehouse_stocks.store') }}" method="POST" id="stockUpdateForm" class="position-relative">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <div class="posted-watermark" id="postedWatermark">Posted</div>
                
                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2 border-0">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-building me-1"></i> General Details</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            {{-- Warehouse --}}
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Target Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Warehouse</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Remarks --}}
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Remarks / Batch Note</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Optional notes for this update...">
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
                                        <th style="width:130px;">Current Stock</th>
                                        <th style="width:150px;">Qty to Add/Adjust</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Items Added:</th>
                                        <th class="text-center">
                                            <span id="total_items_badge" class="badge bg-secondary">0</span>
                                        </th>
                                        <th>
                                            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">+</button>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-end gap-2 text-end">

                            {{-- Save Button --}}
                            <button type="button" id="saveBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                <i class="fa fa-floppy-o me-1"></i> Save Draft
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>

                            {{-- Print Preview --}}
                            <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                <i class="fa fa-print me-1"></i> Print Preview
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>

                            {{-- Post Button --}}
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Save & Post
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>

                            {{-- Edit Button (Hidden initially) --}}
                            <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
                                <i class="fa fa-pencil me-1"></i> Edit
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>

                            {{-- New Button (Hidden initially) --}}
                            <a href="{{ route('warehouse_stocks.create') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="display:none;">
                                <i class="fa fa-plus me-1"></i> New
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>

                            {{-- Cancel --}}
                            <a href="{{ route('warehouse_stocks.index') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-times me-1"></i> Cancel
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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
    var _savedAdjustmentId = null;

    function showToast(msg, type) {
        type = type || 'success';
        var icon  = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', fontSize: '14px', fontWeight: '500',
            display: 'flex', alignItems: 'center', gap: '8px', minWidth: '280px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }

    function updateItemCount() {
        var count = $('#itemRows tr').length;
        $('#total_items_badge').text(count);
    }

    function fetchCurrentStock($row, productId) {
        var warehouseId = $('#warehouse_id').val();
        if (!warehouseId || !productId) { $row.find('.current-stock').val(''); return; }
        $.get("{{ route('warehouse.stock.quantity') }}", { warehouse_id: warehouseId, product_id: productId })
            .done(function(res) { 
                $row.find('.current-stock').val(res.quantity); 
            })
            .fail(function() { $row.find('.current-stock').val(0); });
    }

    function initProductSelect($row) {
        $row.find('.product-select').select2({
            placeholder: 'Search Product', width: '100%',
            ajax: {
                url: "{{ route('search-productsinwar') }}", dataType: 'json', delay: 100,
                data: function(params) { return { q: params.term }; },
                processResults: function(data, params) {
                    const term = (params.term || '').toLowerCase();
                    const results = data.map(function(i) { 
                        return { id: i.id, text: i.id + ' - ' + i.name, name: i.name }; 
                    });

                    // Prioritize exact matches
                    results.sort((a, b) => {
                        if (String(a.id) === term || a.name.toLowerCase() === term) return -1;
                        if (String(b.id) === term || b.name.toLowerCase() === term) return 1;
                        return 0;
                    });

                    return { results };
                }
            },
            minimumInputLength: 1
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $row.find('.item-id-input').val(data.id);
            fetchCurrentStock($row, data.id);
            if ($row.is('#itemRows tr:last-child')) { appendBlankRow(false); }
            setTimeout(function() { $row.find('.quantity-input').focus().select(); }, 60);
        });
    }

    function appendBlankRow(focus = true) {
        var html = `<tr>
            <td><input type="text" class="form-control input-sm item-id-input" placeholder="ID"></td>
            <td><select name="product_id[]" class="form-control product-select" style="width:100%;"><option value="">Select Product</option></select></td>
            <td><input type="number" class="form-control input-sm current-stock" readonly></td>
            <td><input type="number" name="quantity[]" class="form-control input-sm quantity-input" value="1" step="any"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        var $row = $(html);
        $('#itemRows').append($row);
        initProductSelect($row);
        updateItemCount();
        if (focus) { setTimeout(function() { $row.find('.item-id-input').focus(); }, 60); }
        return $row;
    }

    appendBlankRow();

    $(document).on('keydown', '.item-id-input', function(e) {
        if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey) {
            var $row = $(this).closest('tr');
            var val  = $(this).val().trim();
            if (!val) { e.preventDefault(); $row.find('.product-select').select2('open'); return; }
            e.preventDefault();
            $.ajax({
                url: "{{ route('search-productsinwar') }}", data: { q: val },
                success: function(res) {
                    // Match prioritization: exact ID -> case-insensitive exact name -> first result if only 1
                    var item = res.find(function(i) { return i.id.toString() === val; })
                             || res.find(function(i) { return i.name.toLowerCase() === val.toLowerCase(); });
                    
                    if (!item && res.length === 1) {
                        item = res[0];
                    }

                    if (item) {
                        var option = new Option(item.id + ' - ' + item.name, item.id, true, true);
                        $row.find('.product-select').empty().append(option).val(item.id).trigger('change.select2');
                        fetchCurrentStock($row, item.id);
                        if ($row.is('#itemRows tr:last-child')) { appendBlankRow(false); }
                        setTimeout(function() { $row.find('.quantity-input').focus().select(); }, 60);
                    } else { $row.find('.product-select').select2('open'); }
                }
            });
        }
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#itemRows tr').length > 1) { $(this).closest('tr').remove(); updateItemCount(); }
    });

    $('#addRowBtn').on('click', function() { appendBlankRow().find('.item-id-input').focus(); });

    $('#warehouse_id').on('change', function() {
        $('#itemRows tr').each(function() {
            var pid = $(this).find('.item-id-input').val();
            if(pid) fetchCurrentStock($(this), pid);
        });
    });

    function doSave(act) {
        $('#formAction').val(act);
        $('#itemRows tr').each(function() { if (!$(this).find('.product-select').val()) { $(this).remove(); } });
        if ($('#itemRows tr').length === 0) { appendBlankRow(); showToast('❌ Add at least one item.', 'error'); return; }

        var $form = $('#stockUpdateForm');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveBtn';
        var oldHtml = $(btn).html();
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Processing...');

        $.ajax({
            url: "{{ route('warehouse_stocks.store') }}", type: 'POST', data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    showToast('✅ ' + res.message);
                    _savedAdjustmentId = res.id;
                    
                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning text-dark').addClass('bg-success text-white').html('<i class="fa fa-check me-1"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        $('#saveBtn, #postBtn, #editBtn').hide();
                        $('#stockUpdateForm').addClass('form-locked');
                        showToast('✅ Posted Successfully! Redirecting...', 'success');
                        setTimeout(function() {
                            window.location.href = "{{ route('warehouse_stocks.create') }}";
                        }, 1500);
                    } else {
                        $('#statusBadge').removeClass('bg-warning text-dark').addClass('bg-info text-white').html('<i class="fa fa-pencil me-1"></i> Unposted');
                        $('#editBtn, #newBtn').show();
                        $('#saveBtn, #postBtn').hide();
                        $('#stockUpdateForm').addClass('form-locked');
                        showToast('🔒 Draft Saved — Ctrl+E to Edit', 'success');
                        setTimeout(function(){ $('#editBtn').focus(); }, 100);
                    }
                    $('#idBadge').html('<i class="fa fa-tag me-1"></i> ID: ' + res.id).show();
                } else { showToast(res.message, 'error'); }
            },
            complete: function() {
                $(btn).prop('disabled', false).html(oldHtml);
            }
        });
    }

    $('#saveBtn').on('click', function() { doSave('save'); });
    $('#postBtn').on('click', function() { doSave('post'); });
    
    $('#editBtn').on('click', function() {
        $('#stockUpdateForm').removeClass('form-locked');
        $(this).hide();
        $('#saveBtn, #postBtn').show();
        showToast('🔓 Edit Mode Active', 'success');
    });

    $('#previewPrintBtn').on('click', function() {
        if(!_savedAdjustmentId) { showToast('❌ Save first to print.', 'error'); return; }
        window.open("/warehouse_stocks/" + _savedAdjustmentId + "/print", "_blank");
    });

    $(document).on('keydown', function(e) {
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); $('#saveBtn').trigger('click'); }
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn').trigger('click'); }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) { e.preventDefault(); $('#previewPrintBtn').trigger('click'); }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) { e.preventDefault(); window.location.href = $('#listBtn').attr('href'); }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) { e.preventDefault(); $('#editBtn').trigger('click'); }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newBtn').attr('href'); }
        if (e.key === 'Escape') { window.location.href = $('#cancelBtn').attr('href'); }
    });
});
</script>
@endsection
