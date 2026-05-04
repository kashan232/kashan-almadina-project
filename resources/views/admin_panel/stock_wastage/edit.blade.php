@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 customizations to match theme */
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
    .input-sm {
        height: 31px;
        padding: 2px 8px;
        font-size: 14px;
    }
    .table td, .table th {
        vertical-align: middle;
        padding: 4px;
    }
    .badge-gwn {
        font-size: 16px;
        font-weight: bold;
        background-color: #007bff;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
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
    .locked-bg {
        background-color: #f8f9fa !important;
    }
    .form-locked {
        background-color: #f8f9fa !important;
        position: relative;
    }
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
    .form-locked .remove-row, .form-locked #addItemBtn, .form-locked #saveDraftBtn { 
        display: none !important; 
    }
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

            {{-- TOP BAR: Left | Center | Right --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">

                {{-- LEFT: Post button (only on edit, when unposted) --}}
                <div class="d-flex align-items-center" style="min-width:80px;">
                    @if(isset($stock_wastage) && $stock_wastage->status != 'Posted')
                        <form action="{{ route('stock-wastage.post', $stock_wastage->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Post
                            </button>
                        </form>
                    @else
                        <span></span>
                    @endif
                </div>

                {{-- CENTER: Title + Status badge + GWN ID --}}
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">{{ isset($stock_wastage) ? 'Edit Stock Wastage' : 'Create Stock Wastage' }}</h6>
                    <span class="badge {{ isset($stock_wastage) && $stock_wastage->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ isset($stock_wastage) && $stock_wastage->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i>
                        {{ $stock_wastage->status ?? 'Draft' }}
                    </span>
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> GWN: {{ $gwnId }}
                    </span>
                </div>

                {{-- RIGHT: List button --}}
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-wastage.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                        <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>

            </div>

            <form action="{{ route('stock-wastage.update', $stock_wastage->id) }}" method="POST" id="wastageForm" class="position-relative {{ $stock_wastage->status == 'Posted' ? 'form-locked' : '' }}">
                @csrf
                @method('PUT')
                <div class="posted-watermark {{ $stock_wastage->status == 'Posted' ? 'show' : '' }}">Posted</div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Wastage Details</h6>
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

                <!-- Items Table -->
                <div class="card shadow-sm">
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
                                        <th style="width: 50px;">Act</th>
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
                                        <th class="text-end" colspan="3">Total:</th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="{{ (float)$stock_wastage->items->sum('qty') }}">
                                        </th>
                                        <th>
                                            <input type="text" name="grand_total" id="grand_total" class="form-control input-sm text-end fw-bold" readonly value="{{ number_format($stock_wastage->total_amount, 2, '.', '') }}">
                                        </th>
                                        <th>
                                            <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">+</button>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex gap-2 justify-content-end">
                            {{-- Save Draft --}}
                            <button type="button" id="saveDraftBtn" value="draft"
                                class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm api-save-btn"
                                style="{{ $stock_wastage->status == 'Posted' ? 'display:none;' : '' }}">
                                <i class="fa fa-floppy-o me-1"></i> Save Draft
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>

                            {{-- Print Preview --}}
                            <button type="button" id="previewPrintBtn"
                                class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                <i class="fa fa-print me-1"></i> Print Preview
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>

                            {{-- Post --}}
                            <button type="button" id="postBtn" value="post"
                                class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm api-save-btn"
                                style="{{ $stock_wastage->status == 'Posted' ? 'display:none;' : '' }}">
                                <i class="fa fa-send me-1"></i> Save & Post
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>

                            {{-- Edit --}}
                            <button type="button" id="editInvoiceBtn" 
                                class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" 
                                style="{{ $stock_wastage->status == 'Posted' ? 'display:block;' : 'display:none;' }}">
                                <i class="fa fa-pencil me-1"></i> Edit 
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>

                            {{-- New --}}
                            <a href="{{ route('stock-wastage.create') }}" id="newInvoiceBtn" 
                                class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-plus me-1"></i> New 
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>

                            {{-- Cancel --}}
                            <a href="{{ route('stock-wastage.index') }}" id="cancelBtn" 
                                class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
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
        var _savedWastageId = "{{ $stock_wastage->id }}";

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
            }).html('<i class="fa ' + icon + ' : ' + msg + '"></i>');
            $('body').append($toast);
            setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
        }

        // =============================================
        //  AJAX SAVE DRAFT (no page reload)
        // =============================================
        function ajaxSaveDraft() {
            var $form  = $('#wastageForm');
            
            // Remove empty rows before anything else
            $('#itemsTable tbody tr').each(function() {
                if (!$(this).find('.product-select').val()) {
                    $(this).remove();
                }
            });

            // Re-calculate after removing rows
            calcTotal();

            // At least one row must exist
            if ($('#itemsTable tbody tr').length === 0) {
                addRow();
                showToast('❌ Please add at least one item.', 'error');
                return;
            }

            // Now check validity
            if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

            // Set action = save
            if ($form.find('input[name="action"]').length === 0) {
                $form.append('<input type="hidden" name="action" value="save">');
            }
            $form.find('input[name="action"]').val('save');

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

                        // Show Post button
                        $('#postBtn')
                            .show()
                            .prop('disabled', false)
                            .removeClass('btn-primary')
                            .addClass('btn-success')
                            .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');

                        // Update print button
                        var printUrl = '{{ url("stock-wastage") }}/' + res.id + '/print';
                        if ($('#previewPrintBtn').length) {
                            $('#previewPrintBtn').replaceWith(
                                $('<a>').attr({href: printUrl, target:'_blank', id:'realPrintBtn', class:'btn btn-sm btn-outline-dark rounded-pill px-4'})
                                .html('<i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>')
                            );
                        }
                        
                        // Show New & Edit buttons
                        $('#newInvoiceBtn').show();
                        $('#editInvoiceBtn').show();
                        
                        // Explicitly Lock the form
                        console.log("Locking form...");
                        $('#wastageForm').addClass('form-locked');
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
                    $('#saveDraftBtn').prop('disabled', false)
                        .html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                }
            });
        }

        // =============================================
        //  POST (after save) → AJAX → reload create page
        // =============================================
        function doPost() {
            if (!_savedWastageId) {
                showToast('⚠️ پہلے Save Draft کریں!', 'error');
                return;
            }
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
                        // Reload create page for new entry
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
                    $('#postBtn').prop('disabled', false)
                        .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
                }
            });
        }

        // =============================================
        //  BUTTON CLICK HANDLERS
        // =============================================
        $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
        $('#postBtn').on('click',      function() { doPost(); });

        // Unlock form on Edit button
        $('#editInvoiceBtn').on('click', function() {
            $('#wastageForm').removeClass('form-locked');
            $(this).hide();
            $('#saveDraftBtn, #postBtn').show();
            showToast('🔓 Form Unlocked for Editing', 'success');
        });

        // =============================================
        //  GLOBAL KEYBOARD SHORTCUTS
        // =============================================
        $(document).on('keydown', function(e) {
            // Ctrl+S  →  Save Draft (AJAX)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                ajaxSaveDraft();
            }
            // Ctrl+Enter  →  Post (after save)
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                doPost();
            }
            // Ctrl+P  →  Print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                if ($('#realPrintBtn').length > 0) {
                    window.open($('#realPrintBtn').attr('href'), '_blank');
                } else {
                    $('#previewPrintBtn').trigger('click');
                }
            }
            // Ctrl+L  →  List page
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                window.location.href = $('#listBtn').attr('href');
            }
            // Ctrl+E → Unlock form (Edit)
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                if ($('#editInvoiceBtn').is(':visible')) {
                    $('#editInvoiceBtn').trigger('click');
                }
            }
            // Ctrl+M → New
            if (e.ctrlKey && e.key === 'm') {
                e.preventDefault();
                window.location.href = $('#newInvoiceBtn').attr('href');
            }
            // ESC → Cancel / Modal Close
            if (e.key === 'Escape') {
                if ($('.modal.show').length) {
                    $('.modal.show').modal('hide');
                } else {
                   window.location.href = $('#cancelBtn').attr('href');
                }
            }
        });

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

        $('#addItemBtn').click(function() { addRow(); });

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

        // Summary Preview
        $('#previewPrintBtn').on('click', function() {
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
                if(prod !== 'Select Product') {
                    html += '<tr><td>'+prod+'</td><td class="text-center">'+q+'</td><td class="text-end">'+p+'</td><td class="text-end">'+a+'</td></tr>';
                }
            });
            html += '</tbody><tfoot class="bg-light fw-bold">';
            html += '<tr><td colspan="3" class="text-end">Grand Total:</td><td class="text-end text-danger">'+$('#grand_total').val()+'</td></tr>';
            html += '</tfoot></table>';

            $('#printPreviewBody').html(html);
            $('#printPreviewModal').modal('show');
        });
    });
</script>
@endsection
