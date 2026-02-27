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
                    {{-- Post btn could go here if status != Posted --}}
                </div>

                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Edit Inward Gatepass</h6>
                    <span class="badge {{ $gatepass->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $gatepass->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i>
                        {{ ucfirst($gatepass->status ?? 'Draft') }}
                    </span>
                </div>

                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('InwardGatepass.home') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List
                        <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ route('InwardGatepass.update', $gatepass->id) }}" method="POST" id="gatepassForm">
                @csrf
                @method('PUT')

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fw-bold text-muted"><i class="fa fa-info-circle me-1"></i> Gatepass Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                             <!-- Date -->
                             <div class="col-md-2">
                                <label class="form-label small fw-bold">Date</label>
                                <input type="date" name="gatepass_date" class="form-control input-sm" value="{{ $gatepass->gatepass_date }}" required>
                            </div>

                            <!-- Branch -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Branch</label>
                                <select name="branch_id" class="form-select select2" required>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->id }}" {{ $gatepass->branch_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Warehouse</label>
                                <select name="warehouse_id" class="form-select select2" required>
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->id }}" {{ $gatepass->warehouse_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->warehouse_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Vendor -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Vendor</label>
                                <select name="vendor_id" class="form-select select2" required>
                                    @foreach ($vendors as $item)
                                        <option value="{{ $item->id }}" {{ $gatepass->vendor_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Transport -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Transport</label>
                                <input type="text" name="transport_name" class="form-control input-sm" value="{{ $gatepass->transport_name }}">
                            </div>

                            <!-- Bilty -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Bilty/GP No</label>
                                <input type="text" name="bilty_no" class="form-control input-sm" value="{{ $gatepass->bilty_no ?? $gatepass->gatepass_no }}">
                            </div>

                            <!-- Remarks/Note -->
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Note / Remarks</label>
                                <input type="text" name="note" class="form-control input-sm" value="{{ $gatepass->note ?? $gatepass->remarks }}">
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
                                    @foreach($gatepass->items as $item)
                                    <tr>
                                        <td><input type="text" class="form-control input-sm item-id-input" value="{{ $item->product_id }}" placeholder="ID"></td>
                                        <td>
                                            <select name="product_id[]" class="form-control product-select" style="width:100%;">
                                                <option value="{{ $item->product_id }}" selected>{{ $item->product_id }} - {{ $item->product->name ?? '' }}</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="brand[]" class="form-control input-sm brand-name" value="{{ $item->brand ?? '' }}" readonly></td>
                                        <td><input type="number" name="qty[]" class="form-control input-sm quantity" value="{{ $item->qty }}" min="1"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                    </tr>
                                    @endforeach
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('InwardGatepass.home') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-4">
                                    <i class="fa fa-times me-1"></i> Cancel
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-5 shadow-sm">
                                    <i class="fa fa-save me-1"></i> Update Inward Gatepass
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>
                                </button>
                            </div>
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
        // Initialize static Select2
        $('.select2').select2({ width: '100%' });

        // =============================================
        //  ROW MANAGEMENT
        // =============================================

        window.initProductSelect = function($row) {
            $row.find('.product-select').select2({
                placeholder: "Select Product",
                width: '100%',
                ajax: {
                    url: "{{ route('search-productsinwar') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(i => ({
                                id: i.id,
                                text: i.id + ' - ' + i.name,
                                name: i.name,
                                brand: i.brand
                            }))
                        };
                    }
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                $row.find('.item-id-input').val(data.id);
                $row.find('.brand-name').val(data.brand);
                $row.find('.quantity').focus().select();
                recalcTotals();
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
                    <td><input type="number" name="qty[]" class="form-control input-sm quantity" value="1" min="1"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                </tr>`;
            const $newRow = $(html);
            $('#gatepassItems').append($newRow);
            window.initProductSelect($newRow);
            return $newRow;
        };

        // Initialize existing rows
        $('#gatepassItems tr').each(function() {
            window.initProductSelect($(this));
        });

        // If no rows, add one
        if ($('#gatepassItems tr').length === 0) {
            window.appendBlankRow();
        }

        recalcTotals();

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
                        const item = res.find(i => i.id.toString() === val);
                        if (item) {
                            const option = new Option(item.id + ' - ' + item.name, item.id, true, true);
                            $row.find('.product-select').empty().append(option).trigger('change');
                            $row.find('.brand-name').val(item.brand);
                            $row.find('.quantity').focus().select();
                        } else {
                            $(this).focus().select();
                        }
                    }
                });
            }
        });

        $(document).on('keydown', '.quantity', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $row = $(this).closest('tr');
                if ($row.is(':last-child')) {
                    const $newRow = window.appendBlankRow();
                    $newRow.find('.item-id-input').focus();
                } else {
                    $row.next().find('.item-id-input').focus();
                }
            }
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#gatepassItems tr').length > 1) {
                $(this).closest('tr').remove();
                recalcTotals();
            }
        });

        $(document).on('input', '.quantity', function() {
            recalcTotals();
        });

        function recalcTotals() {
            let totalQty = 0;
            $('.quantity').each(function() {
                totalQty += parseFloat($(this).val()) || 0;
            });
            $('#total_qty').val(totalQty);
        }

        $('#addRowBtn').on('click', function() {
            window.appendBlankRow().find('.item-id-input').focus();
        });

        // =============================================
        //  SHORTCUTS
        // =============================================
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                $('#gatepassForm').submit();
            }
            if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
                e.preventDefault();
                window.location.href = $('#listBtn').attr('href');
            }
        });
    });
</script>
@endsection
