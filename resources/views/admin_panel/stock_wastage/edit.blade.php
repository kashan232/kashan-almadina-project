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
                <div class="d-flex align-items-center" style="min-width:80px;">
                    @if($stock_wastage->status != 'Posted')
                        <form action="{{ route('stock-wastage.post', $stock_wastage->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa fa-send me-1"></i> Post
                            </button>
                        </form>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Edit Stock Wastage</h6>
                    <span class="badge {{ $stock_wastage->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $stock_wastage->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i>
                        {{ $stock_wastage->status }}
                    </span>
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa fa-tag me-1"></i> GWN: {{ $stock_wastage->gwn_id }}
                    </span>
                </div>

                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-wastage.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('stock-wastage.update', $stock_wastage->id) }}" method="POST" id="wastageForm">
                @csrf
                @method('PUT')

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Wastage Details</h6>
                    </div>
                    
                    <div class="card-body">
                        <input type="hidden" name="gwn_id" value="{{ $stock_wastage->gwn_id }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="date" class="form-control input-sm" value="{{ $stock_wastage->date }}" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $stock_wastage->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense Head</label>
                                <select name="account_head_id" id="account_head_id" class="form-select select2" required>
                                    @foreach($accountHeads as $head)
                                        <option value="{{ $head->id }}" {{ $stock_wastage->account_head_id == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Expense A/C</label>
                                <select name="account_id" id="account_id" class="form-select select2" required>
                                    <option value="{{ $stock_wastage->account_id }}" selected>{{ $stock_wastage->account->title ?? 'Select Account' }}</option>
                                </select>
                            </div>

                            <div class="col-md-9">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $stock_wastage->remarks }}">
                            </div>
                        </div>
                    </div>
                </div>

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
                                    <tr>
                                        <td><input type="text" class="form-control input-sm item-id-input" value="{{ $item->product_id }}" style="width:75px;"></td>
                                        <td>
                                            <select name="product_id[]" class="form-control product-select" required style="width:100%;">
                                                <option value="{{ $item->product_id }}" selected>{{ $item->product_id }} - {{ $item->product->name ?? '' }}</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="price[]" class="form-control input-sm price" step="0.01" value="{{ $item->price }}"></td>
                                        <td><input type="number" name="qty[]"   class="form-control input-sm qty"   step="any" min="0.01" value="{{ $item->qty }}" required></td>
                                        <td><input type="text"   class="form-control input-sm amount" readonly value="{{ number_format($item->amount, 2, '.', '') }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row">X</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">Total:</th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="{{ $stock_wastage->items->sum('qty') }}">
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('stock-wastage.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                                    <i class="fa fa-times me-1"></i> Cancel
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="saveUpdateBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                </button>
                                <a href="{{ route('stock-wastage.print', $stock_wastage->id) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                    <i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                </a>
                                <button type="button" id="postBtn" data-action="post" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Print Preview Modal (optional for edit, but good for parity) --}}
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').each(function() {
            $(this).select2({ width: '100%' });
        });

        // Re-init Select2 for existing rows
        $('.product-select').each(function() {
            $(this).select2({
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
                                return { id: item.id, text: item.id + ' - ' + item.name, price_net: item.price_net || 0 };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        });

        function showToast(msg, type) {
            type = type || 'success';
            var color = type === 'success' ? '#28a745' : '#dc3545';
            var $toast = $('<div>').css({
                position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
                background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
                fontSize: '14px', fontWeight: '500'
            }).text(msg);
            $('body').append($toast);
            setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
        }

        function ajaxUpdate() {
            var $form = $('#wastageForm');
            $('#saveUpdateBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Updating...');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(res) {
                    if (res.success) {
                        showToast('✅ ' + res.message);
                    } else {
                        showToast('❌ ' + res.message, 'error');
                    }
                },
                error: function(xhr) {
                    showToast('❌ Update failed.', 'error');
                },
                complete: function() {
                    $('#saveUpdateBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Update Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                }
            });
        }

        function doPost() {
            var id = "{{ $stock_wastage->id }}";
            $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');
            $.ajax({
                url: '{{ url("stock-wastage") }}/' + id + '/post',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    showToast('✅ Posted successfully!');
                    setTimeout(function() { window.location.href = '{{ route("stock-wastage.index") }}'; }, 1000);
                },
                error: function() {
                    showToast('❌ Post failed.', 'error');
                    $('#postBtn').prop('disabled', false).html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
                }
            });
        }

        $('#saveUpdateBtn').on('click', ajaxUpdate);
        $('#postBtn').on('click', doPost);

        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') { e.preventDefault(); ajaxUpdate(); }
            if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); doPost(); }
            if (e.ctrlKey && e.key === 'l') { e.preventDefault(); window.location.href = $('#listBtn').attr('href'); }
        });

        // Logic for adding rows and product selection similar to create.blade.php
        function addRow() {
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
                        return { results: data.map(function(item) { return { id: item.id, text: item.id + ' - ' + item.name, price_net: item.price_net || 0 }; }) };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
            setTimeout(function() { $row.find('.item-id-input').focus(); }, 60);
        }

        $('#addItemBtn').click(addRow);

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            calcTotal();
        });

        $(document).on('input', '.qty, .price', function() {
            calcRow($(this).closest('tr'));
        });

        $(document).on('select2:select', '.product-select', function(e) {
            var $row = $(this).closest('tr');
            var data = e.params.data;
            $row.find('.item-id-input').val(data.id);
            $row.find('.qty').val(1);
            $row.find('.price').val(parseFloat(data.price_net || 0).toFixed(2));
            calcRow($row);
            setTimeout(function() { $row.find('.price').focus().select(); }, 80);
        });

        function calcRow($row) {
            var qty = parseFloat($row.find('.qty').val()) || 0;
            var price = parseFloat($row.find('.price').val()) || 0;
            $row.find('.amount').val((qty * price).toFixed(2));
            calcTotal();
        }

        function calcTotal() {
            var totalAmt = 0, totalQty = 0;
            $('.amount').each(function() { totalAmt += parseFloat($(this).val()) || 0; });
            $('.qty').each(function() { totalQty += parseFloat($(this).val()) || 0; });
            $('#grand_total').val(totalAmt.toFixed(2));
            $('#total_qty').val(totalQty);
        }

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
                    $accSelect.html(options).trigger('change');
                }
            });
        });
    });
</script>
@endsection
