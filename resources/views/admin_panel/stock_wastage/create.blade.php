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
                    <h6 class="mb-0 fw-bold" style="font-size:.85rem;">Create Stock Wastage</h6>
                    <span class="badge bg-warning text-dark px-2 py-0" style="font-size:10px;">Draft</span>
                    <span class="badge bg-primary px-2 py-0" style="font-size:10px;">GWN: {{ $gwnId }}</span>
                </div>
                <a href="{{ route('stock-wastage.index') }}" id="listBtn" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0" style="font-size:.7rem;">
                    <i class="fa fa-list"></i> List
                    <kbd style="font-size:8px;opacity:.8;margin-left:3px;">Ctrl+L</kbd>
                </a>
            </div>

            <form action="{{ route('stock-wastage.store') }}" method="POST" id="wastageForm" class="position-relative">
                @csrf
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
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <!-- Entry Time -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control input-sm" value="{{ date('H:i') }}" required>
                            </div>
                            
                            <!-- Warehouse -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" selected>🏠 Shop Stock</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Expense Account Head -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense Head</label>
                                <select name="account_head_id" id="account_head_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Head</option>
                                    @foreach($accountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Expense Account -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense A/C</label>
                                <select name="account_id" id="account_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Account</option>
                                    <!-- Populated via JS -->
                                </select>
                            </div>

                            <!-- Remarks (Ref# removed) -->
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm">
                            </div>
                            <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
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
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">Total:</th>
                                        <th></th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="0">
                                        </th>
                                        <th>
                                            <input type="text" name="grand_total" id="grand_total" class="form-control input-sm text-end fw-bold" readonly value="0.00">
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

                    <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" disabled>
                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                    </button>

                    <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" disabled>
                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                    </button>

                    <a href="javascript:void(0)" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
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
        var _savedWastageId = null;
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

        // =============================================
        //  SHOW SUCCESS TOAST
        // =============================================
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

        // =============================================
        //  AJAX SAVE DRAFT (no page reload)
        // =============================================
        function ajaxSaveDraft() {
            if (_saveInFlight) return;
            var $form  = $('#wastageForm');
            
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
                url:  $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    if (res.success) {
                        _savedWastageId = res.id;
                        showToast('✅ Draft Saved — ' + (res.message || 'Wastage saved as unposted.'), 'success');

                        $form.attr('action', "{{ url('stock-wastage') }}/" + res.id);
                        if ($form.find('input[name="_method"]').length === 0) {
                            $form.prepend('<input type="hidden" name="_method" value="PUT">');
                        }
                        window.history.pushState({path: "{{ url('stock-wastage') }}/" + res.id + "/edit"}, '', "{{ url('stock-wastage') }}/" + res.id + "/edit");

                        $('#realPrintBtn').attr('href', '{{ url("stock-wastage") }}/' + res.id + '/print').attr('target', '_blank');

                        $('#editInvoiceBtn').prop('disabled', false);
                        $('#postBtn').prop('disabled', false);
                        $('#deleteBtn').prop('disabled', false);

                        setFormLocked(true);
                        showToast('🔒 Form Locked — Press Ctrl+E to Edit', 'success');
                    } else {
                        showToast('❌ ' + (res.message || 'Error saving draft.'), 'error');
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

        // =============================================
        //  POST (after save) → AJAX → reload create page
        // =============================================
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
                url:  '{{ url("stock-wastage") }}/' + _savedWastageId + '/post',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
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
                        setTimeout(function() { window.location.href = '{{ route("stock-wastage.create") }}'; }, 1500);
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
            if (!href || href === 'javascript:void(0)' || !href.startsWith('http')) {
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

        // =============================================
        //  TAB FLOW:  Date → Warehouse → Expense Head
        //             → Expense A/C → Remarks → Item ID
        // =============================================

        // Date → Tab (forward only) → open Warehouse
        $('input[name="date"]').on('keydown', function(e) {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                $('select[name="warehouse_id"]').select2('open');
            }
            // Shift+Tab on date → browser default (go to previous field)
        });

        // Warehouse: user SELECTS a value → open Expense Head
        $('select[name="warehouse_id"]').on('select2:select', function() {
            setTimeout(function() {
                $('#account_head_id').select2('open');
            }, 80);
        });

        // Expense Head: user SELECTS → AJAX loads → open Expense A/C
        $('#account_head_id').on('select2:select', function() {
            setTimeout(function() {
                $('#account_id').select2('open');
            }, 500);
        });

        // Expense A/C: user SELECTS → focus Remarks
        $('#account_id').on('select2:select', function() {
            setTimeout(function() {
                $('input[name="remarks"]').focus();
            }, 80);
        });

        // Remarks → Tab/Enter (forward) → first Item ID
        // Remarks → Shift+Tab (backward) → open Expense A/C
        $('input[name="remarks"]').on('keydown', function(e) {
            if ((e.key === 'Tab' || e.key === 'Enter') && !e.shiftKey) {
                e.preventDefault();
                $('#itemsTable tbody tr:first .item-id-input').focus();
            }
            if (e.key === 'Tab' && e.shiftKey) {
                e.preventDefault();
                $('#account_id').select2('open');
            }
        });

        // Account Head Logic (AJAX load accounts)
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
                error: function() {
                    $accSelect.html('<option value="" disabled selected>Error loading</option>');
                }
            });
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

            // Init Select2 for Product
            $row.find('.product-select').select2({
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

                        // Prioritize exact matches (ID or Name) at the top of the list
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

            // Focus Item ID of new row
            if (focus) {
                setTimeout(function() { $row.find('.item-id-input').focus(); }, 60);
            }
        }

        // Add Item via Ctrl+I
        // Initial Row
        addRow();

        // ---- Item ID lookup ----
        function lookupByItemId($row, callback) {
            var itemId = $.trim($row.find('.item-id-input').val());
            if (!itemId) return;

            $.ajax({
                url: "{{ route('search-products') }}",
                dataType: 'json',
                data: { q: itemId },
                success: function(data) {
                    // Match prioritization: exact ID -> case-insensitive exact name -> first result if only 1
                    var match = data.find(function(item) { return item.id.toString() === itemId.toString(); })
                             || data.find(function(item) { return item.name.toLowerCase() === itemId.toLowerCase(); });
                    
                    if (!match && data.length === 1) match = data[0];

                    if (match) {
                        // Fill select2
                        var option = new Option(match.name, match.id, true, true);
                        $row.find('.product-select').empty().append(option).trigger('change');

                        // Set qty = 1 (default)
                        $row.find('.qty').val(1);

                        // Set price (if available from DB, otherwise 0)
                        var price = parseFloat(match.price_net || 0);
                        $row.find('.price').val(price.toFixed(2));

                        // Calculate immediately
                        calcRow($row);

                        if (typeof callback === 'function') callback($row, match);
                    } else {
                        // Not found → clear the input so user can retype
                        $row.find('.item-id-input').val('').focus();
                        // If row has no product selected and is not the only row, remove it
                        if ($('#itemsTable tbody tr').length > 1 && !$row.find('.product-select').val()) {
                            $row.remove();
                            calcTotal();
                            // Focus last row's item-id
                            $('#itemsTable tbody tr:last .item-id-input').focus();
                        }
                    }
                }
            });
        }

        // Enter / Tab (NOT Shift+Tab) on Item ID → lookup → new row silent + open product dropdown if empty
        $(document).on('keydown', '.item-id-input', function(e) {
            if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey) {
                var $row = $(this).closest('tr');
                
                // Always append new row silently if last
                if ($row.is(':last-child')) {
                    addRow(false);
                }

                if (!$(this).val()) {
                    e.preventDefault();
                    $row.find('.product-select').select2('open');
                } else {
                    // If has ID, lookup
                    lookupByItemId($row);
                }
            }
            // Shift+Tab on item-id → go back to Remarks
            if (e.key === 'Tab' && e.shiftKey) {
                e.preventDefault();
                $('input[name="remarks"]').focus();
            }
        });

        // Product select2 CLOSE (Tab out or select) → focus Price of same row
        // Use a flag to avoid double-firing (select2:select also fires select2:close)
        var _productSelectDone = false;
        $(document).on('select2:select', '.product-select', function(e) {
            _productSelectDone = true;
            var $row  = $(this).closest('tr');
            var data  = e.params.data;
            $row.find('.item-id-input').val(data.id);
            $row.find('.qty').val(1);
            var price = parseFloat(data.price_net || 0);
            $row.find('.price').val(price.toFixed(2));
            calcRow($row);
            setTimeout(function() {
                $row.find('.price').focus().select();
                _productSelectDone = false;
            }, 80);
        });

        // Tab out of Product without selecting → also move to Price
        $(document).on('select2:close', '.product-select', function() {
            if (_productSelectDone) return; // already handled by select2:select
            var $row = $(this).closest('tr');
            setTimeout(function() {
                $row.find('.price').focus().select();
            }, 80);
        });

        // blur on item-id → lookup silently (no new row, just fill)
        $(document).on('blur', '.item-id-input', function() {
            var $row = $(this).closest('tr');
            var itemId = $.trim($(this).val());
            if (itemId && !$row.find('.product-select').val()) {
                lookupByItemId($row, function($r) { /* silent fill only */ });
            }
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            calcTotal();
        });

        // Calculation: fires on every price / qty change
        $(document).on('input', '.qty, .price', function() {
            calcRow($(this).closest('tr'));
        });

        // Price → Enter → focus qty | Shift+Tab → back to item-id
        $(document).on('keydown', '.price', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $(this).closest('tr').find('.qty').focus().select();
            }
            if (e.key === 'Tab' && e.shiftKey) {
                e.preventDefault();
                $(this).closest('tr').find('.item-id-input').focus().select();
            }
        });

        // Qty → Enter → add new row + focus its item-id
        $(document).on('keydown', '.qty', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                calcRow($(this).closest('tr')); // ensure calculated
                addRow(); // new row is focused inside addRow()
            }
        });

        function calcRow($row) {
            var qty   = parseFloat($row.find('.qty').val())   || 0;
            var price = parseFloat($row.find('.price').val())  || 0;
            $row.find('.amount').val((qty * price).toFixed(2));
            calcTotal();
        }

        function calcTotal() {
            var totalAmt = 0, totalQty = 0;
            $('#itemsTable tbody tr').each(function() {
                var productId = $(this).find('.product-select').val();
                if (productId) {
                    totalAmt += parseFloat($(this).find('.amount').val()) || 0;
                    totalQty += parseFloat($(this).find('.qty').val()) || 0;
                }
            });
            $('#grand_total').val(totalAmt.toFixed(2));
            $('#total_qty').val(totalQty);
        }

        function showPreviewModal() {
            var gwnId     = $('input[name="gwn_id"]').val();
            var date      = $('input[name="date"]').val();
            var remarks   = $('input[name="remarks"]').val();
            var warehouse = $('select[name="warehouse_id"] option:selected').text();
            var expHead   = $('select#account_head_id option:selected').text();
            var expAcc    = $('select#account_id option:selected').text();

            var rows = '';
            var totalQty = 0, totalAmt = 0, serial = 1;

            $('#itemsTable tbody tr').each(function() {
                var productId   = $(this).find('.item-id-input').val();
                var productText = $(this).find('.product-select option:selected').text();
                
                // Use product text directly as we changed the name format
                var productName = productText;

                var price   = parseFloat($(this).find('.price').val()) || 0;
                var qty     = parseFloat($(this).find('.qty').val()) || 0;
                var amount  = qty * price;

                if (!productId || productText === 'Select Product') return;

                totalQty += qty;
                totalAmt += amount;
                rows += `<tr>
                    <td>${serial++}</td>
                    <td>${productId}</td>
                    <td>${productName}</td>
                    <td style="text-align:center">${qty}</td>
                    <td style="text-align:right">${price.toFixed(2)}</td>
                    <td style="text-align:right">${amount.toFixed(2)}</td>
                </tr>`;
            });

            var html = `
                <div style="border:1px solid #eee; padding:20px; max-width:780px; margin:auto;">

                    {{-- Header --}}
                    <div style="display:flex; justify-content:space-between; align-items:center;
                                border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:16px;">
                        <div>
                            <div style="font-size:22px; font-weight:700;">Al-Madina Traders</div>
                            <div style="color:#555; font-size:12px;">Stock Wastage Voucher</div>
                        </div>
                        <div style="text-align:right; font-size:12px;">
                            <div><strong>GWN ID:</strong> ${gwnId}</div>
                            <div><strong>Status:</strong> DRAFT</div>
                        </div>
                    </div>

                    {{-- Info Grid --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; font-size:12px;">
                        <div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Date:</span>${date}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Warehouse:</span>${warehouse}</div>
                        </div>
                        <div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Expense Head:</span>${expHead}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Account:</span>${expAcc}</div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                        <thead>
                            <tr style="background:#f2f2f2;">
                                <th style="border:1px solid #ddd;padding:7px;width:40px;">S#</th>
                                <th style="border:1px solid #ddd;padding:7px;width:80px;">Item ID</th>
                                <th style="border:1px solid #ddd;padding:7px;">Product</th>
                                <th style="border:1px solid #ddd;padding:7px;width:70px;text-align:center;">Qty</th>
                                <th style="border:1px solid #ddd;padding:7px;width:90px;text-align:right;">Price</th>
                                <th style="border:1px solid #ddd;padding:7px;width:100px;text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                        <tfoot>
                            <tr style="background:#f9f9f9;font-weight:700;">
                                <td colspan="3" style="border:1px solid #ddd;padding:7px;text-align:right;">Total:</td>
                                <td style="border:1px solid #ddd;padding:7px;text-align:center;">${totalQty.toFixed(2)}</td>
                                <td style="border:1px solid #ddd;padding:7px;"></td>
                                <td style="border:1px solid #ddd;padding:7px;text-align:right;">${totalAmt.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>

                    ${remarks ? '<p style="font-size:12px;"><strong>Remarks:</strong> ' + remarks + '</p>' : ''}

                    {{-- Signatures --}}
                    <div style="display:flex;justify-content:space-between;margin-top:40px;">
                        <div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:4px;font-size:12px;">Prepared By</div>
                        <div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:4px;font-size:12px;">Approved By</div>
                    </div>

                </div>
            `;

            $('#printPreviewBody').html(html);
            var modal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
            modal.show();
        }

    });
</script>
@endsection
