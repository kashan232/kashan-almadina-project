@extends('admin_panel.layout.app')
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header">
        <h5>➕ New Stock Transfer</h5>
    </div>
    <div class="card-body">
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ route('stock_transfers.store') }}" method="POST" id="transferForm">
            @csrf

            <div class="mb-3">
                <label>From Warehouse</label>
                <select name="from_warehouse_id" id="from_warehouse_id" class="form-control" required>
                    <option value="">Select Warehouse</option>
                    @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <label>To Warehouse</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-control" required>
                            <option value="">Select Warehouse</option>
                            @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6 d-flex align-items-center">
                        <div class="form-check ms-3">
                            <input class="form-check-input" type="checkbox" name="to_shop" value="1" id="toShop">
                            <label class="form-check-label" for="toShop">Transfer to Shop</label>
                        </div>
                    </div>
                </div>
            </div>

            <table class="w-100 table table-bordered text-center" id="product_table">
                <thead>
                    <tr class="bg-light">
                        <th>Product</th>
                        <th>Available Stock</th>
                        <th>Qty to Transfer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="product_body">
                    <tr class="product_row">
                        <td>
                            <select name="product_id[]" class="form-control product-select" required>
                                <option value="">Select Product</option>
                                @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name ?? $product->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="available_stock[]" class="form-control stock" readonly>
                        </td>
                        <td>
                            <input type="number" name="quantity[]" class="form-control quantity" min="1" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger remove-row">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mb-3">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Transfer Stock</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(function() {
        // CSRF for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Add new empty row
        function addNewRow() {
            var row = `
            <tr class="product_row">
                <td>
                    <select name="product_id[]" class="form-control product-select" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name ?? $product->item_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="available_stock[]" class="form-control stock" readonly>
                </td>
                <td>
                    <input type="number" name="quantity[]" class="form-control quantity" min="1" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger remove-row">Remove</button>
                </td>
            </tr>
        `;
            $('#product_body').append(row);
        }

        // When a product is selected, fetch stock for the selected 'from' warehouse
        $(document).on('change', '.product-select', function() {
            var $row = $(this).closest('tr');
            var productId = $(this).val();
            var warehouseId = $('#from_warehouse_id').val();

            if (!warehouseId) {
                alert('Please select "From Warehouse" first.');
                $(this).val('');
                return;
            }

            if (productId) {
                $.get("{{ route('warehouse.stock.quantity') }}", {
                        warehouse_id: warehouseId,
                        product_id: productId
                    })
                    .done(function(res) {
                        $row.find('.stock').val(res.quantity);
                        $row.find('.quantity').attr('max', res.quantity).val('');
                    })
                    .fail(function() {
                        $row.find('.stock').val(0);
                        $row.find('.quantity').attr('max', 0);
                    });
            } else {
                $row.find('.stock').val('');
                $row.find('.quantity').attr('max', 0).val('');
            }

            // If last row selected, add a new blank row
            if ($('#product_body tr:last')[0] === $row[0]) {
                addNewRow();
            }
        });

        // When warehouse changes, clear all product rows
        $('#from_warehouse_id').on('change', function() {
            $('#product_body').empty();
            addNewRow();
        });

        // Validate quantity input
        $(document).on('input', '.quantity', function() {
            var max = parseInt($(this).attr('max')) || 0;
            var val = parseInt($(this).val()) || 0;
            if (val > max) {
                alert('Cannot transfer more than available stock!');
                $(this).val(max);
            }
        });

        // Remove row
        $(document).on('click', '.remove-row', function() {
            var rows = $('#product_body tr').length;
            if (rows <= 1) {
                // reset first row instead of removing
                $(this).closest('tr').find('select, input').val('');
                return;
            }
            $(this).closest('tr').remove();
        });

        // On form submit, final check that no qty exceeds available_stock
        $('#transferForm').on('submit', function(e) {
            var valid = true;
            $('#product_body tr').each(function() {
                var available = parseInt($(this).find('.stock').val()) || 0;
                var qty = parseInt($(this).find('.quantity').val()) || 0;
                var prod = $(this).find('.product-select').val();
                if (prod && qty <= 0) {
                    valid = false;
                    alert('Enter quantity for selected product.');
                    return false;
                }
                if (qty > available) {
                    valid = false;
                    alert('Cannot transfer more than available stock.');
                    return false;
                }
            });
            if (!valid) e.preventDefault();
        });

        // Ensure first empty row present
        if ($('#product_body tr').length === 0) addNewRow();
    });
</script>
@endsection