@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Purchase</h4>
                            <a class="btn btn-primary" href="{{ route('add_purchase') }}">Add Purchase</a>
                        </div>

                        <div class="card-body">
                            <div class="col-lg-12">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="width:100%">
                                        <thead class="text-center">
                                            <tr>
                                                <th class="text-center">ID</th>
                                                <th class="text-center">Invoice No</th>
                                                <th class="text-center">Purchase Type</th> <!-- New Column -->
                                                <th class="text-center">Supplier</th>
                                                <th class="text-center">Purchase Date</th>
                                                <th class="text-center">Warehouse</th>
                                                <th class="text-center">DC No</th>
                                                <th class="text-center">Note</th>
                                                <th class="text-center">Subtotal</th>
                                                <th class="text-center">Discount</th>
                                                <th class="text-center">WHT</th>
                                                <th class="text-center">Net Amount</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-center">
                                            @foreach ($Purchase as $purchase)
                                            <tr>
                                                <td>{{ $purchase->id }}</td>
                                                <td>{{ $purchase->invoice_no ?? 'N/A' }}</td>
                                                <td>
                                                    @if($purchase->inward_id)
                                                    <span class="badge bg-info">Inward (ID: {{ $purchase->inward_id }})</span>
                                                    @else
                                                    <span class="badge bg-success">Direct</span>
                                                    @endif
                                                </td>

                                                <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($purchase->current_date)->format('d-M-Y') }}</td>
                                                <td>{{ $purchase->warehouse->warehouse_name ?? 'N/A' }}</td>
                                                <td>{{ $purchase->dc ?? 'N/A' }}</td>
                                                <td>{{ $purchase->note ?? '-' }}</td>
                                                <td>{{ number_format($purchase->subtotal, 2) }}</td>
                                                <td>{{ number_format($purchase->discount, 2) }}</td>
                                                <td>{{ number_format($purchase->wht, 2) }}</td>
                                                <td><strong>{{ number_format($purchase->net_amount, 2) }}</strong></td>
                                                <td>
                                                    <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                                    <a href="{{ route('purchase.invoice', $purchase->id) }}" class="btn btn-sm btn-dark text-white">Invoice</a>
                                                    {{--
                                            <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this purchase?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                    --}}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>


                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="purchaseModalLabel">Add Purchase</h5>
                                </div>
                                <div class="modal-body">
                                    <form class="myform" action="{{ route('store.Purchase') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="edit_id" id="id" />
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Invoice No</label>
                                                <input type="text" name="invoice_no" class="form-control" id="invoice_no" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Supplier</label>
                                                <input type="text" name="supplier" class="form-control" id="supplier" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Purchase Date</label>
                                                <input type="date" name="purchase_date" class="form-control" id="purchase_date" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Warehouse</label>
                                                <input type="text" name="warehouse_id" class="form-control" id="warehouse_id" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Item Category</label>
                                                <input type="text" name="item_category" class="form-control" id="item_category">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Item Name</label>
                                                <input type="text" name="item_name" class="form-control" id="item_name">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Quantity</label>
                                                <input type="number" name="quantity" class="form-control" id="quantity">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" class="btn btn-primary save-btn" value="Save">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        url = $(this).attr('action');
        method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });

    $(document).on('click', '.edit-btn', function() {
        var tr = $(this).closest("tr");
        $('#id').val(tr.find(".id").text());
        $('#invoice_no').val(tr.find(".invoice_no").text());
        $('#supplier').val(tr.find(".supplier").text());
        $('#purchase_date').val(tr.find(".purchase_date").text());
        $('#warehouse_id').val(tr.find(".warehouse_id").text());
        $("#purchaseModal").modal("show");
    });
</script>
@endsection