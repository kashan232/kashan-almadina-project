@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    
    #salesOfficerTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #salesOfficerTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 10px !important;
        font-size: 12px;
        color: #333;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <!-- Header Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-briefcase me-2 text-primary"></i>Sales Officers</h6>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="fa fa-plus me-1"></i> Add Officer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase">Officer Registry</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="salesOfficerTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">ID</th>
                                    <th>Name</th>
                                    <th>Name (Urdu)</th>
                                    <th>Mobile</th>
                                    <th>Created By</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($officers as $officer)
                                    <tr id="row-{{ $officer->id }}">
                                        <td class="text-center text-muted">{{ $officer->id }}</td>
                                        <td class="fw-bold text-dark">{{ $officer->name }}</td>
                                        <td class="text-end fw-bold" dir="rtl">{{ $officer->name_urdu }}</td>
                                        <td>{{ $officer->mobile }}</td>
                                        <td>
                                            @if($officer->creator)
                                                <span class="text-dark small">{{ $officer->creator->name }}</span>
                                            @else
                                                <span class="text-muted small">System</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button class="btn btn-outline-warning btn-xs px-1 py-0 edit-btn" data-id="{{ $officer->id }}" title="Edit" style="height: 20px;">
                                                    <i class="fa fa-edit text-dark"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-xs px-1 py-0 delete-btn" data-id="{{ $officer->id }}" title="Delete" style="height: 20px;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
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
</div>

<!-- CREATE MODAL -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="create-form" action="{{ route('sales-officer.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold">Add Sales Officer</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Enter name" required />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">نام (Urdu)</label>
                        <input type="text" name="name_urdu" class="form-control form-control-sm text-end" dir="rtl" placeholder="نام درج کریں" required />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Mobile</label>
                        <input type="text" name="mobile" class="form-control form-control-sm" placeholder="Enter mobile number" required />
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Officer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="edit-form" action="{{ route('sales-officer.store') }}" method="POST">
            @csrf
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2 text-dark">
                    <h6 class="modal-title fw-bold">Edit Sales Officer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control form-control-sm" required />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">نام (Urdu)</label>
                        <input type="text" name="name_urdu" id="edit_name_urdu" class="form-control form-control-sm text-end" dir="rtl" required />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Mobile</label>
                        <input type="text" name="mobile" id="edit_mobile" class="form-control form-control-sm" required />
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 shadow-sm">Update Officer</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#salesOfficerTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search officers..."
            }
        });

        // CREATE
        $('.create-form').submit(function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    $('#createModal').modal('hide');
                    Swal.fire('Success', 'Sales Officer Created', 'success').then(() => location.reload());
                }
            });
        });

        // LOAD EDIT DATA
        $('.edit-btn').click(function () {
            let id = $(this).data('id');
            $.get("{{ url('sales-officers/edit') }}/" + id, function (data) {
                $('#edit_id').val(data.id);
                $('#edit_name').val(data.name);
                $('#edit_name_urdu').val(data.name_urdu);
                $('#edit_mobile').val(data.mobile);
                $('#editModal').modal('show');
            });
        });

        // UPDATE
        $('.edit-form').submit(function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    $('#editModal').modal('hide');
                    Swal.fire('Updated', 'Sales Officer Updated', 'success').then(() => location.reload());
                }
            });
        });

        // DELETE
        $('.delete-btn').click(function () {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You can't undo this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/sales-officers/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            $('#row-' + id).remove();
                            Swal.fire('Deleted!', 'Sales Officer has been deleted.', 'success');
                        },
                        error: function (xhr) {
                            Swal.fire('Error', 'Delete failed. Please try again.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection