@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .purchase-page.container-fluid { padding: .2rem .35rem !important; }
    .purchase-page .main-content-inner { padding: 0 !important; }
    .purchase-page .form-label { margin-bottom: 0 !important; font-size: .7rem !important; }
    .purchase-page .input-sm,
    .purchase-page .form-control-sm,
    .purchase-page .form-select-sm { height: 24px !important; min-height: 24px !important; font-size: .75rem !important; padding: .1rem .35rem !important; }
    .purchase-page .select2-container--default .select2-selection--single { height: 24px !important; font-size: .75rem !important; border: 1px solid #ced4da; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 22px !important; padding-left: 6px !important; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 22px !important; }
    .purchase-page .table td, .purchase-page .table th { padding: 2px 4px !important; vertical-align: middle; font-size: .75rem; }
    .purchase-page .card-header { padding: .35rem .5rem !important; }
    .purchase-page .card-body { padding: .5rem !important; }
    .purchase-page .row.g-3 { --bs-gutter-x: .5rem; --bs-gutter-y: .35rem; }
    .purchase-page .bottom-bar { margin-top: .4rem !important; padding: .75rem !important; }
    .purchase-page .page-head { padding: .35rem .5rem !important; margin-bottom: .35rem !important; }
    .purchase-page .btn-xs { padding: 1px 4px; font-size: .7rem; line-height: 1.2; }
    .posted-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem;
        color: rgba(220, 53, 69, 0.1);
        font-weight: 900;
        pointer-events: none;
        z-index: 1000;
        text-transform: uppercase;
        display: none;
        border: 10px solid rgba(220, 53, 69, 0.1);
        padding: 20px 50px;
        border-radius: 20px;
    }
    .posted-watermark.show { display: block; }
    .form-locked input,
    .form-locked .select2-container--default .select2-selection--single,
    .form-locked .select2-container,
    .form-locked select,
    .form-locked textarea {
        pointer-events: none !important;
        opacity: 0.85 !important;
        background-color: #f1f3f5 !important;
        cursor: not-allowed !important;
    }
    .form-locked .remove-row,
    .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn,
    .form-locked #newInvoiceBtn,
    .form-locked #realPrintBtn,
    .form-locked #postBtn,
    .form-locked #exitBtn,
    .form-locked #deleteBtn {
        pointer-events: auto !important;
        opacity: 1 !important;
    }
</style>

@section('content')
<div class="main-content purchase-page">
    <div class="main-content-inner">
        <div class="container-fluid purchase-page-inner">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-1 mb-1">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-1 mb-1">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center page-head bg-light rounded border shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold" style="font-size:.85rem;">Edit Stock Wastage</h6>
                    <span class="badge bg-warning text-dark px-2 py-0" style="font-size:10px;">{{ $stock_wastage->status ?? 'Draft' }}</span>
                    <span class="badge bg-primary px-2 py-0" style="font-size:10px;">GWN: {{ $gwnId }}</span>
                </div>
                <a href="{{ route('stock-wastage.index') }}" id="listBtn" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0" style="font-size:.7rem;">
                    <i class="fa fa-list"></i> List
                    <kbd style="font-size:8px;opacity:.8;margin-left:3px;">Ctrl+L</kbd>
                </a>
            </div>

            <form action="{{ route('stock-wastage.update', $stock_wastage->id) }}" method="POST" id="wastageForm" class="position-relative">
                @csrf
                @method('PUT')
                <div class="posted-watermark">Posted</div>

                <div class="card shadow-sm mb-2">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold text-muted" style="font-size:.8rem;"><i class="fa fa-info-circle me-1"></i> Wastage Details</h6>
                    </div>
                    
                    <div class="card-body">
                        <input type="hidden" name="gwn_id" value="{{ $gwnId }}">
                        <div class="row g-3">
                            <!-- Entry Date -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ old('entry_date', $stock_wastage->entry_date ?? date('Y-m-d')) }}" required>
                            </div>
                            <!-- Entry Time -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control input-sm" value="{{ old('entry_time', $stock_wastage->entry_time ?? date('H:i')) }}" required>
                            </div>
                            
                            <!-- Warehouse -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ old('warehouse_id', $stock_wastage->warehouse_id) == 0 ? 'selected' : '' }}>🏠 Shop Stock</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ old('warehouse_id', $stock_wastage->warehouse_id) == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Expense Account Head -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense Head</label>
                                <select name="account_head_id" id="account_head_id" class="form-select select2" required>
                                    <option value="" disabled>Select Head</option>
                                    @foreach($accountHeads as $head)
                                        <option value="{{ $head->id }}" {{ old('account_head_id', $stock_wastage->account_head_id) == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Expense Account -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense A/C</label>
                                <select name="account_id" id="account_id" class="form-select select2" required>
                                    <option value="" disabled>Select Account</option>
                                    @if(isset($stock_wastage->account))
                                        <option value="{{ $stock_wastage->account_id }}" selected>{{ $stock_wastage->account->title }}</option>
                                    @endif
                                </select>
                            </div>

                            <!-- Remarks (Ref# removed) -->
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ old('remarks', $stock_wastage->remarks) }}">
                            </div>
                            <input type="hidden" name="date" value="{{ old('date', $stock_wastage->date ?? date('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 80px;">Item ID</th>
                                        <th style="width: 280px;">Product</th>
                                        <th style="width: 110px;">Price</th>
                                        <th style="width: 90px;">Qty</th>
                                        <th style="width: 110px;">Amount</th>
                                        <th style="width: 50px;" class="text-center"><span style="font-size:9px;font-weight:normal;color:#888;"><kbd style="font-size:8px;padding:0 2px;">Ctrl+I</kbd></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stock_wastage->items as $item)
                                        @php $amount = $item->price * $item->qty; @endphp
                                        <tr>
                                            <td><input type="text" class="form-control input-sm item-id-input" placeholder="ID" style="width:75px;" value="{{ $item->product_id }}"></td>
                                            <td>
                                                <select name="product_id[]" class="form-control product-select" required style="width:100%;">
                                                    <option value="{{ $item->product_id }}" selected>{{ $item->product->name }}</option>
                                                </select>
                                            </td>
                                            <td><input type="number" name="price[]" class="form-control input-sm price" step="0.01" value="{{ $item->price }}"></td>
                                            <td><input type="number" name="qty[]"   class="form-control input-sm qty"   step="any" min="0.01" value="{{ (float)$item->qty }}" required></td>
                                            <td><input type="text"   class="form-control input-sm amount" readonly value="{{ number_format($amount, 2, '.', '') }}"></td>
                                            <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row">X</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">Total:</th>
                                        <th></th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="{{ (float)$stock_wastage->items->sum('qty') }}">
                                        </th>
                                        <th>
                                            <input type="text" name="grand_total" id="grand_total" class="form-control input-sm text-end fw-bold" readonly value="{{ number_format($stock_wastage->total_amount, 2, '.', '') }}">
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center bg-light bottom-bar rounded-2 border shadow-sm w-100">
                    <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm">
                        <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                    </button>

                    <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" disabled>
                        <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                    </button>

                    <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm">
                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                    </button>

                    <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm">
                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                    </button>

                    <a href="{{ route('stock-wastage.print', $stock_wastage->id) }}" target="_blank" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                        <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                    </a>

                    <a href="{{ route('stock-wastage.index') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                        E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                    </a>

                    <a href="{{ route('stock-wastage.create') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                        <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ===== Print Preview Modal ===== --}}
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="printPreviewLabel">
                    <i class="fa fa-eye me-2"></i> Stock Wastage Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="printPreviewBody" style="font-family:'Poppins',sans-serif; font-size:13px;">
                {{-- Populated by JS --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-dark rounded-pill px-4" onclick="window.print()">
                    <i class="fa fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // =============================================
        //  SAVED WASTAGE STATE (after AJAX save)
        // =============================================
        var _savedWastageId = {{ $stock_wastage->id }};
        var _saveInFlight = false;
        var _postInFlight = false;

        function setFormLocked(isLocked) {
            if (isLocked) {
                $('#wastageForm').addClass('form-locked');
                $('#wastageForm .select2').prop('disabled', true);
            } else {
                $('#wastageForm').removeClass('form-locked');
                $('#wastageForm .select2').prop('disabled', false).trigger('change.select2');
            }
        }

        function showToast(msg, type) {
            type = type || 'success';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
            var color = type === 'success' ? '#28a745' : '#dc3545';
            var $toast = $('<div>').css({
                position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
                background: color, color: '#fff',
                padding: '12px 20px', borderRadius: '8px',
                boxShadow: '0 4px 15px rgba(0,0,0,.2)',
                fontSize: '14px', fontWeight: '500',
                display: 'flex', alignItems: 'center', gap: '8px',
                minWidth: '280px', animation: 'fadein .3s'
            }).html('<i class="fa ' + icon + '"></i> ' + msg);
            $('body').append($toast);
            setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
        }

        function ajaxSaveDraft() {
            if (_saveInFlight) return;
            var $form = $('#wastageForm');

            $('#itemsTable tbody tr').each(function() {
                if (!$(this).find('.product-select').val()) {
                    $(this).remove();
                }
            });

            calcTotal();

            if ($('#itemsTable tbody tr').length === 0) {
                addRow();
                showToast('❌ Please add at least one item.', 'error');
                return;
            }

            if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

            if ($form.find('input[name="action"]').length === 0) {
                $form.append('<input type="hidden" name="action" value="save">');
            }
            $form.find('input[name="action"]').val('save');

            _saveInFlight = true;
            $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    if (res.success) {
                        _savedWastageId = res.id;
                        showToast('✅ Updated — ' + (res.message || 'Wastage saved.'), 'success');

                        $('#realPrintBtn').attr('href', '{{ url("stock-wastage") }}/' + res.id + '/print').attr('target', '_blank');

                        $('#editInvoiceBtn').prop('disabled', false);
                        $('#postBtn').prop('disabled', false);
                        $('#deleteBtn').prop('disabled', false);

                        setFormLocked(true);
                        showToast('🔒 Form Locked — Press Ctrl+E to Edit', 'success');
                    } else {
                        showToast('❌ ' + (res.message || 'Error saving.'), 'error');
                    }
                },
                error: function(xhr) {
                    var msg = 'Save failed.';
                    try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e){}
                    showToast('❌ ' + msg, 'error');
                },
                complete: function() {
                    _saveInFlight = false;
                    if (!$('#wastageForm').hasClass('form-locked')) {
                        $('#saveDraftBtn').prop('disabled', false)
                            .html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                    }
                }
            });
        }

        function doPost() {
            if (_postInFlight) return;
            if (!_savedWastageId) {
                showToast('⚠️ پہلے Save کریں!', 'error');
                return;
            }
            _postInFlight = true;
            $('#postBtn').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

            $.ajax({
                url: '{{ url("stock-wastage") }}/' + _savedWastageId + '/post',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function() {
                    showToast('✅ Posted successfully! نئی انٹری شروع کریں...');
                    setTimeout(function() {
                        window.location.href = '{{ route("stock-wastage.create") }}';
                    }, 1500);
                },
                error: function(xhr) {
                    var msg = 'Post failed.';
                    try {
                        var r = JSON.parse(xhr.responseText);
                        msg = r.message || r.error || msg;
                    } catch(e) {}
                    showToast('❌ ' + msg, 'error');
                    _postInFlight = false;
                    $('#postBtn').prop('disabled', false)
                        .html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
                }
            });
        }

        $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
        $('#postBtn').on('click', function() { doPost(); });

        $('#editInvoiceBtn').on('click', function() {
            if ($(this).prop('disabled')) return;
            setFormLocked(false);
            $(this).prop('disabled', true);
            $('#saveDraftBtn').prop('disabled', false)
                .html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            showToast('🔓 Form Unlocked for Editing', 'success');
        });

        $('#deleteBtn').on('click', function() {
            if (!_savedWastageId || $(this).prop('disabled')) return;
            if (confirm('Are you sure you want to delete this draft?')) {
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>...');
                $.ajax({
                    url: '{{ url("stock-wastage") }}/' + _savedWastageId,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function() {
                        showToast('✅ Wastage deleted successfully!', 'success');
                        setTimeout(function() { window.location.href = '{{ route("stock-wastage.index") }}'; }, 1500);
                    },
                    error: function() {
                        showToast('❌ Failed to delete.', 'error');
                        $('#deleteBtn').prop('disabled', false)
                            .html('<u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>');
                    }
                });
            }
        });

        $('#realPrintBtn').on('click', function(e) {
            var href = $(this).attr('href');
            if (!href || href === 'javascript:void(0)' || href.indexOf('stock-wastage') === -1) {
                e.preventDefault();
                showPreviewModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (!_saveInFlight && !$('#saveDraftBtn').prop('disabled') && $('#saveDraftBtn').is(':visible')) {
                    $('#saveDraftBtn').click();
                }
            }
            if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
                e.preventDefault();
                if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
            }
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (!_postInFlight && !$('#postBtn').prop('disabled')) $('#postBtn').click();
            }
            if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
                e.preventDefault();
                if (!$('#deleteBtn').prop('disabled')) $('#deleteBtn').click();
            }
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                var href = $('#realPrintBtn').attr('href');
                if (href && href !== 'javascript:void(0)' && href.indexOf('stock-wastage') !== -1) {
                    window.open(href, '_blank');
                } else {
                    showPreviewModal();
                }
            }
            if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
                e.preventDefault();
                window.location.href = $('#listBtn').attr('href');
            }
            if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
                e.preventDefault();
                window.location.href = $('#newInvoiceBtn').attr('href');
            }
            if (e.ctrlKey && (e.key === 'i' || e.key === 'I')) {
                e.preventDefault();
                if (!$('#wastageForm').hasClass('form-locked')) addRow(true);
            }
            if (e.key === 'Escape') {
                if ($('.modal.show').length) {
                    $('.modal.show').modal('hide');
                } else {
                    e.preventDefault();
                    window.location.href = $('#exitBtn').attr('href');
                }
            }
        }, true);

        // Initialize rows with select2
        function initProductSelect($select) {
            $select.select2({
                placeholder: "Search Product",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 100,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data, params) {
                        const term = (params.term || '').toLowerCase();
                        const results = data.map(function(item) {
                            return { id: item.id, text: item.name, price_net: item.price_net || 0 };
                        });
                        results.sort((a, b) => {
                            if (String(a.id) === term || a.text.toLowerCase() === term) return -1;
                            if (String(b.id) === term || b.text.toLowerCase() === term) return 1;
                            return 0;
                        });
                        return { results };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        }

        $('#itemsTable tbody .product-select').each(function() {
            initProductSelect($(this));
        });

        // Add Row Function
        function addRow(focus = true) {
            var rowHtml = `
                <tr>
                    <td><input type="text" class="form-control input-sm item-id-input" placeholder="ID" style="width:75px;"></td>
                    <td>
                        <select name="product_id[]" class="form-control product-select" required style="width:100%;">
                            <option value="">Select Product</option>
                        </select>
                    </td>
                    <td><input type="number" name="price[]" class="form-control input-sm price" step="0.01" value="0"></td>
                    <td><input type="number" name="qty[]"   class="form-control input-sm qty"   step="any" min="0.01" value="1" required></td>
                    <td><input type="text"   class="form-control input-sm amount" readonly value="0.00"></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row">X</button></td>
                </tr>
            `;
            var $row = $(rowHtml);
            $('#itemsTable tbody').append($row);
            initProductSelect($row.find('.product-select'));
            if (focus) {
                setTimeout(function() { $row.find('.item-id-input').focus(); }, 60);
            }
        }

        // Add row via Ctrl+I

        // Account Head Logic
        $('#account_head_id').on('change', function() {
            var headId = $(this).val();
            var $accSelect = $('#account_id');
            $accSelect.html('<option value="" disabled selected>Loading...</option>');
            $.ajax({
                url: '/get-accounts-by-head/' + headId,
                type: 'GET',
                success: function(res) {
                    var options = '<option value="" disabled selected>Select Account</option>';
                    if(Array.isArray(res)) {
                        res.forEach(function(acc) {
                            options += `<option value="${acc.id}">${acc.code || ''} - ${acc.title}</option>`;
                        });
                    }
                    $accSelect.html(options);
                    if ($accSelect.hasClass('select2-hidden-accessible')) $accSelect.trigger('change');
                },
                error: function() { $accSelect.html('<option value="" disabled selected>Error loading</option>'); }
            });
        });

        // Calculation Helpers
        function calcRow($row) {
            var p = parseFloat($row.find('.price').val()) || 0;
            var q = parseFloat($row.find('.qty').val()) || 0;
            $row.find('.amount').val((p * q).toFixed(2));
            calcTotal();
        }

        function calcTotal() {
            var tQty = 0; var tAmt = 0;
            $('#itemsTable tbody tr').each(function() {
                tQty += parseFloat($(this).find('.qty').val()) || 0;
                tAmt += parseFloat($(this).find('.amount').val()) || 0;
            });
            $('#total_qty').val(tQty);
            $('#grand_total').val(tAmt.toFixed(2));
        }

        $(document).on('input', '.qty, .price', function() { calcRow($(this).closest('tr')); });
        $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); calcTotal(); });

        // Item ID lookup
        function lookupByItemId($row) {
            var itemId = $.trim($row.find('.item-id-input').val());
            if (!itemId) return;
            $.ajax({
                url: "{{ route('search-products') }}",
                dataType: 'json',
                data: { q: itemId },
                success: function(data) {
                    var match = data.find(function(item) { return item.id.toString() === itemId.toString(); })
                             || data.find(function(item) { return item.name.toLowerCase() === itemId.toLowerCase(); });
                    if (!match && data.length === 1) match = data[0];
                    if (match) {
                        var option = new Option(match.name, match.id, true, true);
                        $row.find('.product-select').empty().append(option).trigger('change');
                        $row.find('.price').val(parseFloat(match.price_net || 0).toFixed(2));
                        calcRow($row);
                    } else {
                        $row.find('.item-id-input').val('').focus();
                    }
                }
            });
        }

        $(document).on('keydown', '.item-id-input', function(e) {
            if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey) {
                var $row = $(this).closest('tr');
                if ($row.is(':last-child')) addRow(false);
                if (!$(this).val()) { e.preventDefault(); $row.find('.product-select').select2('open'); }
                else lookupByItemId($row);
            }
        });

        $(document).on('select2:select', '.product-select', function(e) {
            var $row = $(this).closest('tr');
            var data = e.params.data;
            $row.find('.item-id-input').val(data.id);
            $row.find('.price').val(parseFloat(data.price_net || 0).toFixed(2));
            calcRow($row);
            setTimeout(function() { $row.find('.price').focus().select(); }, 80);
        });

        function showPreviewModal() {
            var html = '<div class="p-3 border rounded mb-3 bg-light">';
            html += '<h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Wastage Summary</h5>';
            html += '<div class="row g-2">';
            html += '<div class="col-6"><strong>GWN ID:</strong> '+ $('input[name="gwn_id"]').val() +'</div>';
            html += '<div class="col-6"><strong>Date:</strong> '+ $('input[name="entry_date"]').val() +'</div>';
            html += '<div class="col-6"><strong>Warehouse:</strong> '+ $('select[name="warehouse_id"] option:selected').text() +'</div>';
            html += '<div class="col-6"><strong>Expense Head:</strong> '+ $('#account_head_id option:selected').text() +'</div>';
            html += '<div class="col-6"><strong>Expense A/C:</strong> '+ $('#account_id option:selected').text() +'</div>';
            html += '<div class="col-12 mt-2"><strong>Remarks:</strong> '+ ($('input[name="remarks"]').val() || '-') +'</div>';
            html += '</div></div>';

            html += '<table class="table table-sm table-bordered">';
            html += '<thead class="bg-dark text-white"><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead><tbody>';
            $('#itemsTable tbody tr').each(function() {
                var prod = $(this).find('.product-select option:selected').text();
                var q = $(this).find('.qty').val();
                var p = $(this).find('.price').val();
                var a = $(this).find('.amount').val();
                if(prod && prod !== 'Select Product') {
                    html += '<tr><td>'+prod+'</td><td class="text-center">'+q+'</td><td class="text-end">'+p+'</td><td class="text-end">'+a+'</td></tr>';
                }
            });
            html += '</tbody><tfoot class="bg-light fw-bold">';
            html += '<tr><td colspan="3" class="text-end">Grand Total:</td><td class="text-end text-danger">'+$('#grand_total').val()+'</td></tr>';
            html += '</tfoot></table>';

            $('#printPreviewBody').html(html);
            $('#printPreviewModal').modal('show');
        }

    });
</script>
@endsection
