@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-9">
                    <h4>Vendor List</h4>
                    <h6>Manage Vendors</h6>
                </div>
                <div class="page-btn  col-lg-3 text-center">
                    <!-- ADD button opens add modal -->
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal" onclick="clearAddVendor()">
                        Add Vendor
                    </button>

                    <a href="{{ route('vendor.ledger') }}" class="btn btn-sm btn-outline-danger">ledger</a>
                    <a href="{{ route('vendor.payments.index') }}" class="btn btn-sm btn-outline-danger">payments</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (session()->has('success'))
                    <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
                    @endif

                    <table class="table datanew">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Opening Balance</th>
                                <th>Closing Balance</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendors as $key => $v)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $v->name }}</td>
                                <td>{{ $v->phone }}</td>
                                <td>{{ $v->latestLedger->opening_balance ?? 0 }}</td>
                                <td>{{ $v->latestLedger->closing_balance ?? 0 }}</td>
                                <td>{{ $v->address }}</td>
                                <td>
                                    <!-- EDIT button: opens edit modal and passes data-* -->
                                    <button class="btn btn-sm btn-primary edit-vendor-btn"
                                        data-id="{{ $v->id }}"
                                        data-name="{{ $v->name }}"
                                        data-phone="{{ $v->phone }}"
                                        data-address="{{ $v->address }}"
                                        data-opening_balance="{{ $v->opening_balance ?? 0 }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editVendorModal">
                                        Edit
                                    </button>

                                    <a href="{{ url('vendor/delete/'.$v->id) }}"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete?')">Delete</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- ADD Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="vendorAddForm" action="{{ url('vendor/store') }}" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input class="form-control" name="name" id="add_vname" placeholder="Name" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="phone" id="add_vphone" placeholder="Phone">
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="opening_balance" id="add_vopening" placeholder="Opening Balance" type="number" step="0.01">
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" name="address" id="add_vaddress" placeholder="Address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- EDIT Vendor Modal -->
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="vendorEditForm" action="{{ url('vendor/store') }}" method="POST">@csrf
            <!-- hidden id field for update -->
            <input type="hidden" name="id" id="edit_vendor_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVendorModalTitle">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <input class="form-control" name="name" id="edit_vname" placeholder="Name" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="phone" id="edit_vphone" placeholder="Phone">
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="opening_balance" id="edit_vopening" placeholder="Opening Balance" type="number" step="0.01">
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" name="address" id="edit_vaddress" placeholder="Address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
@section('scripts')
<script>
    // init datatable (call after table exists)
    $('.datanew').DataTable();

    // --- ADD modal helpers ---
    function clearAddVendor() {
        $('#add_vname').val('');
        $('#add_vphone').val('');
        $('#add_vopening').val('');
        $('#add_vaddress').val('');
    }
    // Ensure Add modal cleared when hidden
    document.getElementById('addVendorModal')?.addEventListener('hidden.bs.modal', function() {
        clearAddVendor();
    });

    // --- EDIT: use click handler (more reliable) ---
    $(document).on('click', '.edit-vendor-btn', function(e) {
        e.preventDefault();
        const btn = $(this);

        // read attributes safely
        const id = btn.data('id') ?? '';
        const name = btn.data('name') ?? '';
        const phone = btn.data('phone') ?? '';
        const address = btn.data('address') ?? '';
        const opening = btn.data('opening_balance') ?? '';

        // debug quick: uncomment to test
        // alert("Clicked edit for: " + name + " (id:" + id + ")");

        // set values into edit modal inputs
        $('#edit_vendor_id').val(id);
        $('#edit_vname').val(name);
        $('#edit_vphone').val(phone);
        $('#edit_vaddress').val(address);
        $('#edit_vopening').val(opening);

        // show modal using Bootstrap API (works even if data-bs-toggle omitted)
        const modalEl = document.getElementById('editVendorModal');
        if (modalEl) {
            // If bootstrap Modal class is available:
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            } else {
                // fallback: trigger via jQuery (requires bootstrap JS)
                $('#editVendorModal').modal('show');
            }
        } else {
            console.warn('editVendorModal element not found');
        }
    });

    // Clear edit modal on hide
    document.getElementById('editVendorModal')?.addEventListener('hidden.bs.modal', function() {
        $('#edit_vendor_id').val('');
        $('#edit_vname').val('');
        $('#edit_vphone').val('');
        $('#edit_vaddress').val('');
        $('#edit_vopening').val('');
    });

    // prevent double submit
    $('#vendorAddForm, #vendorEditForm').on('submit', function() {
        $(this).find('button[type="submit"]').attr('disabled', true);
    });
</script>
@endsection