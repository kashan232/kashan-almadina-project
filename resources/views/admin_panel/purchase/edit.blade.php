@extends('admin_panel.layout.app')

@section('content')
 <div class="main-content">
                <div class="main-content-inner">
                    <div class="container">
                        <div class="row">
    <h4>Edit Purchase</h4>
    <form action="{{ route('purchase.update', $purchase->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="row mb-3">
            <div class="col">
                <label>Invoice No</label>
                <input type="text" name="invoice_no" class="form-control" value="{{ $purchase->invoice_no }}">
            </div>
            <div class="col">
                <label>Supplier</label>
                <input type="text" name="supplier" class="form-control" value="{{ $purchase->supplier }}">
            </div>
            <div class="col">
                <label>Purchase Date</label>
                <input type="date" name="purchase_date" class="form-control" value="{{ $purchase->purchase_date }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label>Warehouse</label>
                <input type="text" name="warehouse_id" class="form-control" value="{{ $purchase->warehouse_id }}">
            </div>
            <div class="col">
                <label>Item Category</label>
                <input type="text" name="item_category" class="form-control" value="{{ $purchase->item_category }}">
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach (json_decode($purchase->item_name, true) as $index => $item)
                <tr>
                    <td><input type="text" name="item_name[]" class="form-control" value="{{ $item }}"></td>
                    <td><input type="number" name="quantity[]" class="form-control" value="{{ json_decode($purchase->quantity)[$index] }}"></td>
                    <td><input type="number" step="0.01" name="price[]" class="form-control" value="{{ json_decode($purchase->price)[$index] }}"></td>
                    <td><input type="text" name="unit[]" class="form-control" value="{{ json_decode($purchase->unit)[$index] }}"></td>
                    <td><input type="number" name="total[]" class="form-control" value="{{ json_decode($purchase->total)[$index] }}"></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Other Fields -->
        <div class="row mb-3">
            <div class="col">
                <label>Total Price</label>
                <input type="number" name="total_price" class="form-control" value="{{ $purchase->total_price }}">
            </div>
            <div class="col">
                <label>Discount</label>
                <input type="number" name="discount" class="form-control" value="{{ $purchase->discount }}">
            </div>
            <div class="col">
                <label>Payable Amount</label>
                <input type="number" name="Payable_amount" class="form-control" value="{{ $purchase->Payable_amount }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label>Paid Amount</label>
                <input type="number" name="paid_amount" class="form-control" value="{{ $purchase->paid_amount }}">
            </div>
            <div class="col">
                <label>Due Amount</label>
                <input type="number" name="due_amount" class="form-control" value="{{ $purchase->due_amount }}">
            </div>
            <div class="col">
                <label>Status</label>
                <input type="text" name="status" class="form-control" value="{{ $purchase->status }}">
            </div>
            <div class="col">
                <label>Return?</label>
                <input type="text" name="is_return" class="form-control" value="{{ $purchase->is_return }}">
            </div>
        </div>

        <div class="mb-3">
            <label>Note</label>
            <textarea name="note" class="form-control">{{ $purchase->note }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Update Purchase</button>
    </form>
</div>
      </div>
        </div>
        </div>
        </div>
        </div>

@endsection
