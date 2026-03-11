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
    .input-sm {
        height: 31px;
        padding: 2px 8px;
        font-size: 14px;
    }
    .table td, .table th {
        vertical-align: middle !important;
        padding: 4px !important;
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
    .form-locked .remove-row, .form-locked #addRowBtn, .form-locked #saveDraftBtn { 
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

            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
                <div class="d-flex align-items-center" style="min-width:80px;">
                    @if(isset($gatepass) && $gatepass->status != 'Posted')
                        <form action="{{ url('InwardGatepass/'.$gatepass->id.'/post') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Post
                            </button>
                        </form>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">{{ isset($gatepass) ? 'Edit Inward Gatepass' : 'Add Inward Gatepass' }}</h6>
                    <span class="badge {{ isset($gatepass) && $gatepass->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ isset($gatepass) && $gatepass->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i>
                        {{ $gatepass->status ?? 'Draft' }}
                    </span>
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> Inv: {{ isset($gatepass) ? $gatepass->invoice_no : 'NEW' }}
                    </span>
                </div>

                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('InwardGatepass.home') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                        <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ isset($gatepass) ? url('InwardGatepass/'.$gatepass->id) : route('store.InwardGatepass') }}" method="POST" id="gatepassForm" class="position-relative {{ (isset($gatepass) && $gatepass->status == 'Posted') ? 'form-locked' : '' }}">
                @csrf
                @if(isset($gatepass))
                    @method('PUT')
                @endif
                
                <div class="posted-watermark {{ (isset($gatepass) && $gatepass->status == 'Posted') ? 'show' : '' }}">Posted</div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Gatepass Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                             <!-- Date -->
                             <div class="col-md-2">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="gatepass_date" class="form-control input-sm" value="{{ isset($gatepass) ? $gatepass->gatepass_date : date('Y-m-d') }}" required>
                            </div>

                            <!-- Branch -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Branch</label>
                                <select name="branch_id" class="form-select select2">
                                    <option value="" disabled selected>Select Branch</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->id }}" {{ (isset($gatepass) && $gatepass->branch_id == $item->id) ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Warehouse</option>
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->id }}" {{ (isset($gatepass) && $gatepass->warehouse_id == $item->id) ? 'selected' : '' }}>
                                            {{ $item->warehouse_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Vendor -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Vendor</label>
                                <select name="vendor_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Vendor</option>
                                    @foreach ($vendors as $item)
                                        <option value="{{ $item->id }}" {{ (isset($gatepass) && $gatepass->vendor_id == $item->id) ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Transport -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Transport</label>
                                <input type="text" name="transport_name" class="form-control input-sm" value="{{ isset($gatepass) ? $gatepass->transport_name : '' }}">
                            </div>

                            <!-- Bilty -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Bilty/GP No</label>
                                <input type="text" name="bilty_no" class="form-control input-sm" value="{{ isset($gatepass) ? $gatepass->gatepass_no : '' }}">
                            </div>

                            <!-- Remarks/Note -->
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Note / Remarks</label>
                                <input type="text" name="note" class="form-control input-sm" value="{{ isset($gatepass) ? $gatepass->remarks : '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="gatepassTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 80px;">Item ID</th>
                                        <th style="width: 280px;">Product Description</th>
                                        <th style="width: 150px;">Brand</th>
                                        <th style="width: 100px;">Qty</th>
                                        <th style="width: 50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="gatepassItems">
                                    @if(isset($gatepass))
                                        @foreach($gatepass->items as $idx => $item)
                                            <tr>
                                                <td><input type="text" class="form-control input-sm item-id-input" value="{{ $item->product_id }}" placeholder="ID"></td>
                                                <td>
                                                    <select name="product_id[]" class="form-control product-select" style="width:100%;">
                                                        <option value="{{ $item->product_id }}" selected>{{ $item->product_id }} - {{ $item->product->name ?? 'N/A' }}</option>
                                                    </select>
                                                </td>
                                                <td><input type="text" name="brand[]" class="form-control input-sm brand-name" value="{{ $item->brand }}" readonly></td>
                                                <td><input type="number" name="qty[]" class="form-control input-sm quantity" value="{{ $item->qty }}" step="any" min="0.01"></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Qty:</th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="0">
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
                        <div class="d-flex justify-content-end gap-2">
                            {{-- Save Draft --}}
                            <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                <i class="fa fa-floppy-o me-1"></i> Save Draft
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>

                            {{-- Print Preview --}}
                            <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                <i class="fa fa-print me-1"></i> Print Preview
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>

                            {{-- Post --}}
                            <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-5 shadow-sm">
                                <i class="fa fa-send me-1"></i> Save & Post Inward
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>
                            </button>

                            {{-- Edit --}}
                            <button type="button" id="editInvoiceBtn" 
                                class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" 
                                style="display: none;">
                                <i class="fa fa-pencil me-1"></i> Edit 
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>

                            {{-- New --}}
                            <a href="{{ route('add_inwardgatepass') }}" id="newInvoiceBtn" 
                                class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" 
                                style="display: none;">
                                <i class="fa fa-plus me-1"></i> New 
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>

                            {{-- Cancel --}}
                            <a href="{{ route('InwardGatepass.home') }}" id="cancelBtn" 
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
                    <i class="fa fa-eye me-2"></i> Inward Gatepass Preview
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
        // Initialize static Select2
        $('.select2').select2({ width: '100%' });

        var _savedGatepassId = "{{ isset($gatepass) ? $gatepass->id : '' }}";
        if(_savedGatepassId) {
            $('#editInvoiceBtn').show();
            $('#newInvoiceBtn').show();
            $('#gatepassForm').addClass('form-locked');
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
            var $form = $('#gatepassForm');

            // Remove empty rows
            $('#gatepassItems tr').each(function() {
                if (!$(this).find('.product-select').val()) {
                    $(this).remove();
                }
            });

            if ($('#gatepassItems tr').length === 0) {
                window.appendBlankRow();
                showToast('❌ Please add at least one product.', 'error');
                return;
            }

            if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

            $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            var url = _savedGatepassId 
                ? "{{ url('InwardGatepass') }}/" + _savedGatepassId 
                : "{{ route('store.InwardGatepass') }}";
            
            var data = $form.serializeArray();
            if(_savedGatepassId) {
                // Method is already handled in HTML with @method('PUT') 
                // but for new to edit transition, we might need to handle it.
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: $.param(data),
                success: function(res) {
                    if (res.success) {
                        _savedGatepassId = res.id;
                        showToast('✅ ' + res.message);
                        $('.page-title').text('Edit Inward Gatepass');
                        
                        if(res.status) {
                            $('.badge').removeClass('bg-warning text-dark').addClass('bg-info text-white')
                                .html('<i class="fa fa-pencil me-1"></i> ' + res.status);
                        }

                        $('#postBtn').show().prop('disabled', false).removeClass('btn-primary').addClass('btn-success')
                            .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
                        
                        $('#editInvoiceBtn').show();
                        $('#newInvoiceBtn').show();
                        $('#gatepassForm').addClass('form-locked');
                        showToast('🔒 Form Locked — Press Ctrl+E to Edit', 'success');

                        var printUrl = "{{ url('inward-gatepass') }}/" + res.id + "/pdf";
                        $('#previewPrintBtn').replaceWith(
                            $('<a>').attr({href: printUrl, target:'_blank', id:'realPrintBtn', class:'btn btn-sm btn-outline-dark rounded-pill px-4'})
                            .html('<i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>')
                        );

                        // Update URL to Edit without reload
                        var editUrl = "{{ url('InwardGatepass') }}/" + res.id + "/edit";
                        window.history.replaceState(null, null, editUrl);
                    } else {
                        showToast('❌ Error saving.', 'error');
                    }
                },
                error: function(xhr) {
                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Save failed.';
                    showToast('❌ ' + errorMsg, 'error');
                },
                complete: function() {
                    $('#saveDraftBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                }
            });
        }

        function doPost() {
            if (!_savedGatepassId) { ajaxSaveDraft(); return; }
            $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

            $.ajax({
                url: "{{ url('InwardGatepass') }}/" + _savedGatepassId + "/post",
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    showToast('✅ Posted successfully!');
                    setTimeout(function() { window.location.href = "{{ route('add_inwardgatepass') }}"; }, 1500);
                },
                error: function() {
                    showToast('❌ Post failed.', 'error');
                    $('#postBtn').prop('disabled', false).html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
                }
            });
        }

        $('#saveDraftBtn').on('click', ajaxSaveDraft);
        $('#postBtn').on('click', doPost);

        $('#editInvoiceBtn').on('click', function() {
            $('#gatepassForm').removeClass('form-locked');
            $(this).hide();
            showToast('🔓 Form Unlocked for Editing', 'success');
        });

        // Chain focus
        $('input[name="gatepass_date"]').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('select[name="branch_id"]').select2('open');
            }
        });

        $('select[name="branch_id"]').on('select2:select', function() {
            setTimeout(() => $('select[name="warehouse_id"]').select2('open'), 80);
        });

        $('select[name="warehouse_id"]').on('select2:select', function() {
            setTimeout(() => $('select[name="vendor_id"]').select2('open'), 80);
        });

        $('select[name="vendor_id"]').on('select2:select', function() {
            setTimeout(() => $('input[name="transport_name"]').focus(), 80);
        });

        // Row Management
        window.initProductSelect = function($row) {
            $row.find('.product-select').select2({
                placeholder: "Select Product",
                width: '100%',
                ajax: {
                    url: "{{ route('search-productsinwar') }}",
                    dataType: 'json',
                    delay: 100,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data, params) {
                        const term = (params.term || '').toLowerCase();
                        const results = data.map(i => ({
                            id: i.id,
                            text: i.id + ' - ' + i.name,
                            name: i.name,
                            brand: i.brand
                        }));

                        // Prioritize exact matches (ID or Name) at the top of the list
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
                const data = e.params.data;
                $row.find('.item-id-input').val(data.id);
                $row.find('.brand-name').val(data.brand);
                recalcTotals();
                if ($row.is('#gatepassItems tr:last-child')) {
                    window.appendBlankRow();
                }
                setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
            });
        };

        window.appendBlankRow = function() {
            const html = `
                <tr>
                    <td><input type="text" class="form-control input-sm item-id-input" placeholder="ID"></td>
                    <td>
                        <select name="product_id[]" class="form-control product-select" style="width:100%;">
                            <option value="">Select Product</option>
                        </select>
                    </td>
                    <td><input type="text" name="brand[]" class="form-control input-sm brand-name" readonly></td>
                    <td><input type="number" name="qty[]" class="form-control input-sm quantity" value="1" step="any" min="0.01"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                </tr>`;
            const $newRow = $(html);
            $('#gatepassItems').append($newRow);
            window.initProductSelect($newRow);
            return $newRow;
        };

        // If no rows, add one
        if($('#gatepassItems tr').length === 0) {
            window.appendBlankRow();
        } else {
            $('#gatepassItems tr').each(function() {
                window.initProductSelect($(this));
            });
            recalcTotals();
        }

        // Item ID Lookup
        $(document).on('keydown', '.item-id-input', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                const $row = $(this).closest('tr');
                const val = $(this).val().trim();
                if (!val) return;

                e.preventDefault();
                $.ajax({
                    url: "{{ route('search-productsinwar') }}",
                    data: { q: val },
                    success: function(res) {
                        // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result if only 1
                        let item = res.find(i => String(i.id) === String(val))
                                || res.find(i => i.name.toLowerCase() === val.toLowerCase());

                        if (!item && res.length === 1) {
                            item = res[0];
                        }

                        if (item) {
                            const option = new Option(item.id + ' - ' + item.name, item.id, true, true);
                            $row.find('.product-select').empty().append(option);
                            $row.find('.product-select').val(item.id).trigger('change.select2');
                            $row.find('.brand-name').val(item.brand || '');
                            recalcTotals();
                            if ($row.is('#gatepassItems tr:last-child')) {
                                window.appendBlankRow();
                            }
                            setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
                        } else {
                            $row.find('.product-select').select2('open');
                        }
                    }
                });
            }
        });

        // Shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+S → Save Draft
            if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                ajaxSaveDraft();
            }
            // Ctrl+Enter → Post
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                doPost();
            }
            // Ctrl+P → Print Preview
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                if ($('#realPrintBtn').length > 0) {
                    window.open($('#realPrintBtn').attr('href'), '_blank');
                } else {
                    $('#previewPrintBtn').trigger('click');
                }
            }
            // Ctrl+L → List page
            if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
                e.preventDefault();
                window.location.href = $('#listBtn').attr('href');
            }
            // Ctrl+E → Unlock form (Edit)
            if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
                e.preventDefault();
                if ($('#editInvoiceBtn').is(':visible')) {
                    $('#editInvoiceBtn').trigger('click');
                }
            }
            // Ctrl+M → New
            if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
                e.preventDefault();
                window.location.href = $('#newInvoiceBtn').attr('href');
            }
            // ESC → Cancel / Modal Close
            if (e.key === 'Escape') {
                var $openModal = $('.modal.show');
                if ($openModal.length) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    $openModal.modal('hide');
                    return false;
                } else {
                    window.location.href = $('#cancelBtn').attr('href');
                }
            }
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#gatepassItems tr').length > 1) {
                $(this).closest('tr').remove();
                recalcTotals();
            }
        });

        $(document).on('input', '.quantity', recalcTotals);

        function recalcTotals() {
            let totalQty = 0;
            $('.quantity').each(function() {
                const $row = $(this).closest('tr');
                const productId = $row.find('.product-select').val();
                if (productId) {
                    totalQty += parseFloat($(this).val()) || 0;
                }
            });
            $('#total_qty').val(totalQty);
        }

        $('#addRowBtn').on('click', function() {
            window.appendBlankRow().find('.item-id-input').focus();
        });

        // =============================================
        //  PRINT PREVIEW LOGIC
        // =============================================
        $('#previewPrintBtn').on('click', function() {
            var date      = $('input[name="gatepass_date"]').val();
            var branch    = $('select[name="branch_id"] option:selected').text();
            var warehouse = $('select[name="warehouse_id"] option:selected').text();
            var vendor    = $('select[name="vendor_id"] option:selected').text();
            var transport = $('input[name="transport_name"]').val();
            var bilty     = $('input[name="bilty_no"]').val();
            var remarks   = $('input[name="note"]').val();
            var invNo     = $('.badge .fa-tag').parent().text().replace('Inv:', '').trim();

            var rows = '';
            var totalQty = 0, serialNum = 1;

            $('#gatepassItems tr').each(function() {
                var productId = $(this).find('.item-id-input').val();
                var product   = $(this).find('.product-select option:selected').text();
                var brand     = $(this).find('.brand-name').val();
                var qty       = parseFloat($(this).find('.quantity').val()) || 0;

                if (!productId || product.indexOf('Select Product') !== -1) return;

                totalQty += qty;
                rows += `<tr>
                    <td>${serialNum++}</td>
                    <td>${productId}</td>
                    <td>${product}</td>
                    <td>${brand || ''}</td>
                    <td style="text-align:center">${qty.toFixed(2)}</td>
                </tr>`;
            });

            var html = `
                <div style="border:1px solid #eee; padding:20px; max-width:780px; margin:auto;">
                    {{-- Header --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:16px;">
                        <div>
                            <div style="font-size:22px; font-weight:700;">Al-Madina Traders</div>
                            <div style="color:#555; font-size:12px;">Inward Gatepass Voucher</div>
                        </div>
                        <div style="text-align:right; font-size:12px;">
                            <div><strong>Inv No:</strong> ${invNo}</div>
                            <div><strong>Status:</strong> ${_savedGatepassId ? 'SAVED' : 'DRAFT'}</div>
                        </div>
                    </div>

                    {{-- Info Grid --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; font-size:12px;">
                        <div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Date:</span>${date}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Warehouse:</span>${warehouse}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Vendor:</span>${vendor}</div>
                        </div>
                        <div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Branch:</span>${branch}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Transport:</span>${transport}</div>
                            <div><span style="font-weight:600;width:120px;display:inline-block;">Bilty/GP No:</span>${bilty}</div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                        <thead>
                            <tr style="background:#f2f2f2;">
                                <th style="border:1px solid #ddd;padding:7px;width:40px;">S#</th>
                                <th style="border:1px solid #ddd;padding:7px;width:80px;">Item ID</th>
                                <th style="border:1px solid #ddd;padding:7px;">Product</th>
                                <th style="border:1px solid #ddd;padding:7px;">Brand</th>
                                <th style="border:1px solid #ddd;padding:7px;width:70px;text-align:center;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                        <tfoot>
                            <tr style="background:#f9f9f9;font-weight:700;">
                                <td colspan="4" style="border:1px solid #ddd;padding:7px;text-align:right;">Total Qty:</td>
                                <td style="border:1px solid #ddd;padding:7px;text-align:center;">${totalQty.toFixed(2)}</td>
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
