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

                {{-- LEFT: empty placeholder --}}
                <div style="min-width:80px;"></div>

                {{-- CENTER: Title + Status Badge --}}
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">New Stock Transfer</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-pencil me-1"></i> Draft
                    </span>
                </div>

                {{-- RIGHT: List button --}}
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock_transfers.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                        <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>

            </div>

            <form action="{{ route('stock_transfers.store') }}" method="POST" id="transferForm">
                @csrf

                {{-- Header Details --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Transfer Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            {{-- From Warehouse --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">From Location <span class="text-danger">*</span></label>
                                <select name="from_warehouse_id" id="from_warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Location</option>
                                    <option value="shop">Shop</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- To Warehouse --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">To Warehouse <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" id="to_warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Warehouse</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- To Shop --}}
                            <div class="col-md-2 d-flex align-items-end pb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="to_shop" value="1" id="toShop">
                                    <label class="form-check-label fw-bold small" for="toShop">Transfer to Shop</label>
                                </div>
                            </div>

                            {{-- Remarks --}}
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Optional note...">
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
                                        <th style="width:300px;">Product Description</th>
                                        <th style="width:130px;">Available Stock</th>
                                        <th style="width:120px;">Qty to Transfer</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="transferItems"></tbody>
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
                        <div class="d-flex justify-content-between align-items-center">

                            {{-- Left: Cancel --}}
                            <div>
                                <a href="{{ route('stock_transfers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                                    <i class="fa fa-times me-1"></i> Cancel
                                </a>
                            </div>

                            {{-- Right: Save Draft + Print Preview + Post --}}
                            <div class="d-flex gap-2">

                                {{-- Save Draft --}}
                                <button type="button" id="saveDraftBtn"
                                    class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
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
                                <button type="button" id="postBtn"
                                    class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-send me-1"></i> Save &amp; Post
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                </button>

                            </div>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

{{-- Print Preview Modal --}}
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="printPreviewLabel">
                    <i class="fa fa-eye me-2"></i> Stock Transfer Preview
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

    // Initialize header Select2
    $('.select2').select2({ width: '100%' });

    // =============================================
    //  TOAST
    // =============================================
    function showToast(msg, type) {
        type = type || 'success';
        var icon  = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff',
            padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)',
            fontSize: '14px', fontWeight: '500',
            display: 'flex', alignItems: 'center', gap: '8px',
            minWidth: '280px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }

    // =============================================
    //  RECALC TOTAL QTY
    // =============================================
    function recalcTotals() {
        var total = 0;
        $('.quantity').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#total_qty').val(total);
    }

    // =============================================
    //  STOCK LOOKUP for a row
    // =============================================
    function fetchStock($row, productId) {
        var warehouseId = $('#from_warehouse_id').val();
        if (!warehouseId || !productId) { $row.find('.stock').val(''); return; }

        $.get("{{ route('warehouse.stock.quantity') }}", {
            warehouse_id: warehouseId,
            product_id: productId
        }).done(function(res) {
            $row.find('.stock').val(res.quantity);
            $row.find('.quantity').attr('max', res.quantity);
        }).fail(function() {
            $row.find('.stock').val(0);
            $row.find('.quantity').attr('max', 0);
        });
    }

    // =============================================
    //  INIT SELECT2 ON A ROW
    // =============================================
    function initProductSelect($row) {
        $row.find('.product-select').select2({
            placeholder: 'Search Product',
            width: '100%',
            ajax: {
                url: "{{ route('search-productsinwar') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return {
                        results: data.map(function(i) {
                            return { id: i.id, text: i.id + ' - ' + i.name };
                        })
                    };
                }
            }
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $row.find('.item-id-input').val(data.id);
            fetchStock($row, data.id);
            recalcTotals();
            setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
        });
    }

    // =============================================
    //  APPEND BLANK ROW
    // =============================================
    function appendBlankRow(focus = true) {
        var html = '<tr>' +
            '<td><input type="text" class="form-control input-sm item-id-input" placeholder="ID"></td>' +
            '<td><select name="product_id[]" class="form-control product-select" style="width:100%;"><option value="">Select Product</option></select></td>' +
            '<td><input type="number" name="available_stock[]" class="form-control input-sm stock" readonly></td>' +
            '<td><input type="number" name="quantity[]" class="form-control input-sm quantity" value="1" min="1"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>' +
            '</tr>';
        var $row = $(html);
        $('#transferItems').append($row);
        initProductSelect($row);
        
        if (focus) {
            setTimeout(function() { $row.find('.item-id-input').focus(); }, 60);
        }
        return $row;
    }

    // Initial row
    appendBlankRow();

    // =============================================
    //  ITEM ID LOOKUP (Enter / Tab)
    // =============================================
    $(document).on('keydown', '.item-id-input', function(e) {
        if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey) {
            var $row = $(this).closest('tr');
            var val  = $(this).val().trim();

            // Always append new row silently if last
            if ($row.is('#transferItems tr:last-child')) {
                appendBlankRow(false);
            }

            if (!val) {
                e.preventDefault();
                $row.find('.product-select').select2('open');
                return;
            }
            e.preventDefault();

            $.ajax({
                url: "{{ route('search-productsinwar') }}",
                data: { q: val },
                success: function(res) {
                    var item = res.find(function(i) { return i.id.toString() === val; });
                    if (item) {
                        var option = new Option(item.id + ' - ' + item.name, item.id, true, true);
                        $row.find('.product-select').empty().append(option).val(item.id).trigger('change.select2');
                        $row.find('.item-id-input').val(item.id);
                        fetchStock($row, item.id);
                        recalcTotals();
                        setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
                    } else {
                        $row.find('.product-select').select2('open');
                    }
                }
            });
        }
    });

    // =============================================
    //  QTY → Enter moves to next row
    // =============================================
    $(document).on('keydown', '.quantity', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var $row = $(this).closest('tr');
            if ($row.is('#transferItems tr:last-child')) {
                appendBlankRow().find('.item-id-input').focus();
            } else {
                $row.next().find('.item-id-input').focus();
            }
        }
    });

    // =============================================
    //  QTY VALIDATION
    // =============================================
    $(document).on('input', '.quantity', function() {
        var max = parseFloat($(this).attr('max')) || 0;
        var val = parseFloat($(this).val()) || 0;
        if (max > 0 && val > max) {
            showToast('Cannot transfer more than available stock (' + max + ')!', 'error');
            $(this).val(max);
        }
        recalcTotals();
    });

    // =============================================
    //  REMOVE ROW
    // =============================================
    $(document).on('click', '.remove-row', function() {
        if ($('#transferItems tr').length > 1) {
            $(this).closest('tr').remove();
            recalcTotals();
        }
    });

    // =============================================
    //  ADD ROW BUTTON
    // =============================================
    $('#addRowBtn').on('click', function() {
        appendBlankRow().find('.item-id-input').focus();
    });

    // =============================================
    //  WAREHOUSE CHANGE → CLEAR ROWS
    // =============================================
    $('#from_warehouse_id').on('change', function() {
        $('#transferItems').empty();
        appendBlankRow();
        recalcTotals();
    });

    // =============================================
    //  FIELD CHAIN FOCUS
    // =============================================
    $('select[name="from_warehouse_id"]').on('select2:select', function() {
        setTimeout(function() { $('select[name="to_warehouse_id"]').select2('open'); }, 80);
    });
    $('select[name="to_warehouse_id"]').on('select2:select', function() {
        setTimeout(function() { $('input[name="remarks"]').focus(); }, 80);
    });
    $('input[name="remarks"]').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#transferItems tr:first .item-id-input').focus();
        }
    });

    // =============================================
    //  SAVED STATE
    // =============================================
    var _savedTransferId = null;

    // =============================================
    //  AJAX SAVE DRAFT
    // =============================================
    function ajaxSaveDraft(callback) {
        var $form = $('#transferForm');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: "{{ route('stock_transfers.store') }}",
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    _savedTransferId = res.id;
                    showToast('Saved as Draft successfully!');
                    $('.page-title').text('Stock Transfer Saved');
                    $('#statusBadge').removeClass('bg-warning text-dark').addClass('bg-info text-white')
                        .html('<i class="fa fa-pencil me-1"></i> Unposted');

                    // Change Post button label
                    $('#postBtn').html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');

                    // Replace Print Preview button with real print link
                    var printUrl = '/stock_transfers/' + res.id + '/print';
                    $('#previewPrintBtn').replaceWith(
                        $('<a>').attr({ href: printUrl, target: '_blank', id: 'realPrintBtn', class: 'btn btn-sm btn-outline-dark rounded-pill px-4' })
                        .html('<i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>')
                    );

                    if (typeof callback === 'function') callback(res.id);
                } else {
                    showToast(res.message || 'Save failed.', 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Save failed.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(' | ');
                }
                showToast(msg, 'error');
            },
            complete: function() {
                $('#saveDraftBtn').prop('disabled', false)
                    .html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            }
        });
    }

    // =============================================
    //  AJAX POST
    // =============================================
    function doPost() {
        if (!_savedTransferId) {
            ajaxSaveDraft(function(id) {
                setTimeout(function() { postById(id); }, 400);
            });
            return;
        }
        postById(_savedTransferId);
    }

    function postById(id) {
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

        $.ajax({
            url: '/stock_transfers/' + id + '/post',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    showToast('Posted Successfully! Redirecting...');
                    $('#statusBadge').removeClass('bg-info bg-warning text-dark').addClass('bg-success text-white')
                        .html('<i class="fa fa-check me-1"></i> Posted');
                    $('#saveDraftBtn').hide();
                    $('#postBtn').hide();
                    setTimeout(function() {
                        window.location.href = '{{ route("stock_transfers.index") }}';
                    }, 1500);
                } else {
                    showToast(res.message, 'error');
                    $('#postBtn').prop('disabled', false)
                        .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Post failed.';
                showToast(msg, 'error');
                $('#postBtn').prop('disabled', false)
                    .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');
            }
        });
    }

    // =============================================
    //  BUTTON CLICK HANDLERS
    // =============================================
    $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
    $('#postBtn').on('click', function() { doPost(); });

    // Print Preview modal (before save)
    $(document).on('click', '#previewPrintBtn', function() {
        var fromWh  = $('#from_warehouse_id option:selected').text();
        var toWh    = $('#to_warehouse_id option:selected').text();
        var remarks = $('input[name="remarks"]').val();
        var rows = '', totalQty = 0, serial = 1;

        $('#transferItems tr').each(function() {
            var productId   = $(this).find('.item-id-input').val();
            var productText = $(this).find('.product-select option:selected').text();

            // Extract Name if text is "ID - Name"
            var productName = productText;
            if (productText.includes(' - ')) {
                productName = productText.split(' - ').slice(1).join(' - ');
            }

            var qty = parseFloat($(this).find('.quantity').val()) || 0;
            if (!productId || productText === 'Select Product' || !qty) return;

            totalQty += qty;
            rows += '<tr>' +
                '<td style="border:1px solid #ddd;padding:6px;">' + serial++ + '</td>' +
                '<td style="border:1px solid #ddd;padding:6px;">' + productId + '</td>' +
                '<td style="border:1px solid #ddd;padding:6px;">' + productName + '</td>' +
                '<td style="border:1px solid #ddd;padding:6px;text-align:center;">' + qty + '</td>' +
                '</tr>';
        });

        var html = '<div style="border:1px solid #eee;padding:20px;font-family:Poppins,sans-serif;font-size:13px;">' +
            '<div style="display:flex;justify-content:space-between;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:16px;">' +
            '<div><div style="font-size:22px;font-weight:700;">Al-Madina Traders</div>' +
            '<div style="color:#555;font-size:12px;">Stock Transfer Voucher</div></div>' +
            '<div style="text-align:right;font-size:12px;"><div><strong>Status:</strong> DRAFT</div></div></div>' +
            '<div style="margin-bottom:16px;font-size:12px;">' +
            '<div><span style="font-weight:600;width:130px;display:inline-block;">From Warehouse:</span>' + fromWh + '</div>' +
            '<div><span style="font-weight:600;width:130px;display:inline-block;">To Warehouse:</span>' + toWh + '</div>' +
            (remarks ? '<div><span style="font-weight:600;width:130px;display:inline-block;">Remarks:</span>' + remarks + '</div>' : '') +
            '</div>' +
            '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">' +
            '<thead><tr style="background:#f2f2f2;">' +
            '<th style="border:1px solid #ddd;padding:7px;width:40px;">S#</th>' +
            '<th style="border:1px solid #ddd;padding:7px;width:80px;">Item ID</th>' +
            '<th style="border:1px solid #ddd;padding:7px;">Product</th>' +
            '<th style="border:1px solid #ddd;padding:7px;width:80px;text-align:center;">Qty</th>' +
            '</tr></thead><tbody>' + rows +
            '<tr style="font-weight:700;background:#f9f9f9;">' +
            '<td colspan="3" style="border:1px solid #ddd;padding:7px;text-align:right;">Total Qty:</td>' +
            '<td style="border:1px solid #ddd;padding:7px;text-align:center;">' + totalQty + '</td>' +
            '</tr></tbody></table>' +
            '<div style="margin-top:50px;display:flex;justify-content:space-between;">' +
            '<div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:5px;">Prepared By</div>' +
            '<div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:5px;">Checked By</div>' +
            '<div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:5px;">Authorized By</div>' +
            '</div></div>';

        $('#printPreviewBody').html(html);
        var myModal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
        myModal.show();
    });

    // =============================================
    //  KEYBOARD SHORTCUTS
    // =============================================
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') { e.preventDefault(); ajaxSaveDraft(); }
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); doPost(); }
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            if (_savedTransferId) {
                window.open('/stock_transfers/' + _savedTransferId + '/print', '_blank');
            } else {
                $('#previewPrintBtn').trigger('click');
            }
        }
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
    });

});
</script>
@endsection
