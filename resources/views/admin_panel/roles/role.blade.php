 @extends('admin_panel.layout.app')
 @section('content')
     
<div class="main-content">
    <div class="main-content-inner">
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-4 bg-primary text-white p-4 rounded shadow-sm">
                        <h3 class="mb-0">Roles Management</h3>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset-form">
                            Add New Role
                        </button>
                    </div>

                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-0">
                            <div class="table-responsive p-3">
                                <table id="default-datatable" class="table table-hover">
                                    <thead class="bg-light text-muted small text-uppercase">
                                        <tr>
                                            <th>ID</th>
                                            <th>Role Name</th>
                                            <th>Permissions</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($roles as $role)
                                            <tr>
                                                <td class="fw-bold">{{ $role->id }}</td>
                                                <td class="name fw-bold">{{ $role->name }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @forelse ($role->getPermissionNames() as $permission)
                                                            <span class="badge bg-soft-primary text-primary px-2 py-1" style="font-size: 10px;">{{ $permission }}</span>
                                                        @empty
                                                            <span class="text-muted small">No permissions</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <button class="btn btn-dark btn-sm edit-permission-btn" style="background:#2a2d34; border:none;">
                                                            Edit Permissions
                                                        </button>
                                                        <button class="btn btn-primary btn-sm edit-btn" data-url="{{ route('roles.store') }}">
                                                            Edit Name
                                                        </button>
                                                        <input type="hidden" class="edit-id" value="{{ $role->id }}">
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
    </div>
</div>

    {{-- Role Name Modal --}}
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Role Info</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="myform" action="{{ route('roles.store') }}" method="POST">
                    <div class="modal-body p-4">
                        @csrf
                        <input type="hidden" name="edit_id" id="id" />
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold small">Role Name</label>
                            <input type="text" name="name" class="form-control" id="name" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary px-4">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div> 

    {{-- EXACT SCREENSHOT PERMISSIONS MODAL --}}
    <div class="modal fade" id="edit-permission-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 rounded-0 shadow-lg">
                <div class="modal-header rounded-0 py-2 px-3 border-0" style="background: #2a2d34;">
                    <h6 class="modal-title text-white fw-normal">Update Role Permissions</h6>
                    <button type="button" class="btn-close btn-close-white small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('roles.update.permission') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 bg-white">
                        <input type="hidden" name="edit_id" id="edit-role-id" />
                        
                        <div class="mb-4">
                            <label class="fw-bold mb-1" style="font-size: 13px; color: #333;">Role Name</label>
                            <input type="text" id="permission-modal-role-name" class="form-control rounded-1 border-light shadow-none" readonly style="background: #e9ecef; font-size: 14px; padding: 8px 12px;">
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold mb-2 border-bottom w-100 pb-1" style="font-size: 13px; color: #333;">Permissions</label>
                        </div>

                        <div id="permission-checkbox-container" class="row g-2 row-cols-4 mt-1">
                            {{-- Checkboxes will be injected here --}}
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-white justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-1 px-4 py-1" style="background:#e9ecef; color:#333; font-size: 14px;" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-1 px-4 py-1 d-flex align-items-center" style="background:#3d5afe; border:none; font-size: 14px;">
                           <i class="fa fa-save me-2" style="font-size: 12px;"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<style>
    .bg-soft-primary { background-color: rgba(61, 90, 254, 0.1); }
    .permission-checkbox { 
        width: 1.5rem !important; 
        height: 1.5rem !important; 
        flex-shrink: 0 !important;
        margin: 0 !important;
        cursor: pointer; 
    }
    .permission-box { 
        border: 1px solid #e3e6f0; 
        transition: all 0.2s ease-in-out; 
        background: #fff; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        padding: 10px 15px !important;
        border-radius: 8px !important;
        height: 55px; /* Fixed height for symmetry */
    }
    .permission-box:hover { background-color: #f8f9fc; border-color: #3d5afe; }
    .perm-label { 
        font-size: 14px; 
        font-weight: 600; 
        color: #333; 
        cursor: pointer; 
        margin-left: 12px !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .row-cols-4 > * { flex: 0 0 auto; width: 25%; padding: 6px; }
    @media (max-width: 1200px) { .row-cols-4 > * { width: 33.33%; } }
    @media (max-width: 768px) { .row-cols-4 > * { width: 50%; } }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/mycode.js') }}"></script>

<script>
    const allPermissions = @json($allPermissions);

    $(document).ready(function() {
        $('#default-datatable').DataTable();
    });

    $(document).on('click', '.edit-permission-btn', function () {
        var tr = $(this).closest("tr");
        var id = tr.find(".edit-id").val();
        var name = tr.find(".name").text().trim();

        let assignedPerms = [];
        tr.find('td:eq(2) .badge').each(function () {
            assignedPerms.push($(this).text().trim());
        });

        $('#permission-modal-role-name').val(name);
        $('#edit-role-id').val(id);
        $('#permission-checkbox-container').empty();

        // Force relative position to avoid overlap
        allPermissions.forEach(p => {
            if (p.name === 'Reports') return; // Skip Reports permission
            let isChecked = assignedPerms.includes(p.name) ? 'checked' : '';
            let html = `
                <div class="col">
                    <div class="permission-box shadow-sm" onclick="$(this).find('.permission-checkbox').click()">
                        <input class="permission-checkbox" type="checkbox" name="permissions[]" value="${p.name}" ${isChecked} id="p_${p.id}" onclick="event.stopPropagation()" style="position:relative !important; margin:0 !important; cursor:pointer;">
                        <span class="perm-label" style="margin-left:12px; font-weight:700; color:#333; font-size:14px; pointer-events:none;">${p.name}</span>
                    </div>
                </div>
            `;
            $('#permission-checkbox-container').append(html);
        });

        $("#edit-permission-modal").modal("show");
    });

    $(document).on('click', '.edit-btn', function () {
        var tr = $(this).closest("tr");
        $('#id').val(tr.find(".edit-id").val());
        $('#name').val(tr.find(".name").text().trim());
        $("#exampleModal").modal("show");
    });

    $(document).on('click', '#reset-form', function () {
        $('#id').val('');
        $('#name').val('');
    });

    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        myAjax($(this).attr('action'), new FormData(this), $(this).attr('method'));
    });
</script>

@if(session('success'))
    <script>
        Swal.fire({ icon: 'success', title: 'Success', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
    </script>
@endif

@endsection
