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
                    @if(isset($wastage) && $wastage->status != 'Posted')
                        <form action="{{ route('stock-wastage.post', $wastage->id) }}" method="POST" class="d-inline">
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
                    <h6 class="page-title mb-0 fw-bold">{{ isset($wastage) ? 'Edit Stock Wastage' : 'Create Stock Wastage' }}</h6>
                    <span class="badge {{ isset($wastage) && $wastage->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ isset($wastage) && $wastage->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i>
                        {{ $wastage->status ?? 'Draft' }}
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

            <form action="{{ route('stock-wastage.store') }}" method="POST" id="wastageForm">
                @csrf

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Wastage Details</h6>
                    </div>
                    
                    <div class="card-body">
                        <input type="hidden" name="gwn_id" value="{{ $gwnId }}">
                        <div class="row g-3">
                            <!-- Date -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <!-- Warehouse -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Warehouse</option>
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
                            <div class="col-md-9">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm">
                            </div>
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
                                    <!-- Dynamic Rows -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">Total:</th>
                                        <th>
                                             <!-- Price Total? Not needed usually -->
                                        </th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="0">
                                        </th>
                                        <th>
                                            <input type="text" name="grand_total" id="grand_total" class="form-control input-sm text-end fw-bold" readonly value="0.00">
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
                        <div class="d-flex justify-content-between align-items-center">

                            {{-- Left: Cancel --}}
                            <div>
                                <a href="{{ route('stock-wastage.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                                    <i class="fa fa-times me-1"></i> Cancel
                                </a>
                            </div>

                            {{-- Right: Save + Print + Post --}}
                            <div class="d-flex gap-2">

                                {{-- Save Draft --}}
                                <button type="button" id="saveDraftBtn"
                                    class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-floppy-o me-1"></i> Save Draft
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                </button>

                                {{-- Print Preview --}}
                                @if(isset($wastage))
                                    <a href="{{ route('stock-wastage.print', $wastage->id) }}" target="_blank"
                                        class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                        <i class="fa fa-print me-1"></i> Print
                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                    </a>
                                @else
                                    <button type="button" id="previewPrintBtn"
                                        class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                        <i class="fa fa-print me-1"></i> Print Preview
                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                    </button>
                                @endif

                                {{-- Post --}}
                                @if(isset($wastage) && $wastage->status != 'Posted')
                                    <button type="button" id="postBtn" data-action="post"
                                        class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                        <i class="fa fa-send me-1"></i> Post
                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>
                                    </button>
                                @elseif(!isset($wastage))
                                    <button type="button" id="postBtn" data-action="post"
                                        class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                        <i class="fa fa-send me-1"></i> Save & Post
                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>
                                    </button>
                                @endif

                            </div>
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
        var _savedWastageId = null;

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
            var $form  = $('#wastageForm');
            if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

            // Set action = save
            if ($form.find('input[name="action"]').length === 0) {
                $form.append('<input type="hidden" name="action" value="save">');
            }
            $form.find('input[name="action"]').val('save');

            $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            // Remove empty rows before submission
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
                $('#saveDraftBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                showToast('❌ Please add at least one item.', 'error');
                return;
            }

            $.ajax({
                url:  $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    if (res.success) {
                        _savedWastageId = res.id;
                        showToast('✅ ' + res.message);

                        // Update GWN badge in header (already correct, but refresh name)
                        $('.page-title').text('Edit Stock Wastage');

                        // Show Post button (it was "Save & Post" before — now it becomes real Post)
                        $('#postBtn')
                            .show()
                            .prop('disabled', false)
                            .removeClass('btn-primary')
                            .addClass('btn-success')
                            .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');

                        // Show Print button (real print link)
                        var printUrl = '{{ url("stock-wastage") }}/' + res.id + '/print';
                        $('#previewPrintBtn')
                            .attr('href', printUrl)
                            .attr('target', '_blank')
                            .attr('id', 'realPrintBtn')
                            .prop('tagName') === 'BUTTON'
                            ? $('#previewPrintBtn').replaceWith(
                                $('<a>').attr({href: printUrl, target:'_blank', id:'realPrintBtn', class:'btn btn-sm btn-outline-dark rounded-pill px-4'})
                                .html('<i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>')
                            ) : null;

                        // Disable form fields to prevent further edits without a new form
                        // (optional UX) — leave editable for now
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
            // Ctrl+P  →  Print Preview modal
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                // If the real print link exists (it means we've saved draft), open the link in a new tab
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
        });

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
                    delay: 250,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return { id: item.id, text: item.name, price_net: item.price_net || 0 };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Focus Item ID of new row
            if (focus) {
                setTimeout(function() { $row.find('.item-id-input').focus(); }, 60);
            }
        }

        // Add Item Button
        $('#addItemBtn').click(function() { addRow(); });

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
                    var match = null;
                    $.each(data, function(i, item) {
                        if (item.id.toString() === itemId.toString()) { match = item; return false; }
                    });
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
            $('.amount').each(function() { totalAmt += parseFloat($(this).val()) || 0; });
            $('.qty').each(function()    { totalQty += parseFloat($(this).val()) || 0; });
            $('#grand_total').val(totalAmt.toFixed(2));
            $('#total_qty').val(totalQty);
        }

        // Submit handler
        $('.api-save-btn').on('click', function(e) {
            e.preventDefault();
            var action = $(this).val();
            var $form  = $('#wastageForm');

            // Remove empty rows before submission
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

            if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
            if ($form.find('input[name="action"]').length === 0) {
                $form.append('<input type="hidden" name="action">');
            }
            $form.find('input[name="action"]').val(action);
            $('.api-save-btn').prop('disabled', true);
            $(this).text('Processing...');
            $form.submit();
        });

        // Print Preview → show Modal with same design as print.blade.php
        $('#previewPrintBtn').on('click', function() {
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
        });

    });
</script>
@endsection
