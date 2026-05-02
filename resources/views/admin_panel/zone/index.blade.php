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
    
    #zoneTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #zoneTable tbody td {
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
                            <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-map-marker me-2 text-primary"></i>Zones Management</h6>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="fa fa-plus me-1"></i> Add Zone
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase">Zone Registry</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="zoneTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">ID</th>
                                    <th>Zone Name</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($zones as $zone)
                                <tr id="row-{{ $zone->id }}">
                                    <td class="text-center text-muted">{{ $zone->id }}</td>
                                    <td class="fw-bold text-dark">{{ $zone->zone }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-outline-warning btn-xs px-1 py-0 edit-btn" data-id="{{ $zone->id }}" title="Edit" style="height: 20px;">
                                                <i class="fa fa-edit text-dark"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-xs px-1 py-0 delete-btn" data-id="{{ $zone->id }}" data-url="{{ route('zone.delete', $zone->id) }}" title="Delete" style="height: 20px;">
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
        <form class="myform" action="{{ route('zone.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold">Add Zone</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Zone Name</label>
                        <input type="text" name="zone" class="form-control form-control-sm" placeholder="Enter zone name" required />
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Zone</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="editform" action="{{ route('zone.store') }}" method="POST">
            @csrf
            <input type="hidden" name="edit_id" id="edit_id" />
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2 text-dark">
                    <h6 class="modal-title fw-bold">Edit Zone</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Zone Name</label>
                        <input type="text" name="zone" class="form-control form-control-sm" id="edit_zone" required />
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4 shadow-sm">Update Zone</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#zoneTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search zones..."
            }
        });

        // CREATE FORM
        $('.myform').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $('#createModal').modal('hide');
                    Swal.fire('Success!', 'Zone created successfully.', 'success').then(() => location.reload());
                }
            });
        });

        // EDIT MODAL DATA
        $('.edit-btn').click(function() {
            var id = $(this).data('id');
            $.get("{{ url('zones/edit') }}/" + id, function(res) {
                $('#edit_id').val(res.id);
                $('#edit_zone').val(res.zone);
                $('#editModal').modal('show');
            });
        });

        // EDIT FORM
        $('.editform').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $('#editModal').modal('hide');
                    Swal.fire('Updated!', 'Zone updated successfully.', 'success').then(() => location.reload());
                }
            });
        });

        // DELETE FUNCTION
        $('.delete-btn').click(function() {
            var id = $(this).data('id');
            var url = $(this).data('url');
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'GET',
                        url: url,
                        success: function(res) {
                            $('#row-' + id).remove();
                            Swal.fire('Deleted!', 'Zone has been deleted.', 'success');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
