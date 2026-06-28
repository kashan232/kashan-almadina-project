 @extends('admin_panel.layout.app')
 @section('content')
     
<div class="main-content">
    <div class="main-content-inner">
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card permissions-card">
                        <div class="card-header permissions-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users-cog fa-2x mr-3"></i>
                                <h3 class="mb-0 text-white" style="font-weight: 600;">Roles Management</h3>
                            </div>
                            <button type="button" class="btn btn-light rounded-pill px-4" style="font-weight: 600; color: #4e73df; background: white;" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset-form">
                                <i class="fas fa-plus me-1"></i> Add New Role
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="default-datatable" class="table table-hover table-permissions w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="5%">ID</th>
                                            <th width="15%">Role Name</th>
                                            <th width="65%">Permissions</th>
                                            <th class="text-center" width="15%">Action</th>
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
                                                            <span class="permission-badge mb-1"><i class="fas fa-check-circle mr-1"></i>{{ $permission }}</span>
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

                        <div class="mb-2 d-flex justify-content-between align-items-center border-bottom pb-2">
                            <label class="fw-bold mb-0" style="font-size: 13px; color: #333;">Permissions</label>
                            <div class="d-flex align-items-center bg-light px-3 py-1 rounded border" style="cursor: pointer;" onclick="$('#select-all-permissions').click()">
                                <input type="checkbox" id="select-all-permissions" onclick="event.stopPropagation()" class="m-0" style="width: 16px; height: 16px; cursor: pointer;">
                                <label for="select-all-permissions" class="mb-0 fw-bold ms-2" style="font-size: 13px; cursor: pointer; pointer-events: none;">Select All</label>
                            </div>
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
    /* Premium Table Styles */
    .permissions-card { border: none; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); background-color: #ffffff; }
    .permissions-header { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; border-radius: 12px 12px 0 0 !important; padding: 20px 25px; border-bottom: none; }
    .table-permissions th { background-color: #f8f9fc; color: #4e73df; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; border-bottom: 2px solid #e3e6f0 !important; padding: 15px; }
    .table-permissions td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #e3e6f0; padding: 15px; }
    .table-permissions tbody tr:hover { background-color: #f1f3f9; transition: background-color 0.2s ease-in-out; }
    
    /* Pill Badges */
    .permission-badge { background-color: #e8eaef; color: #3a3b45; font-weight: 600; padding: 6px 12px; border-radius: 20px; font-size: 12px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #d1d3e2; margin-right: 4px; }
    .permission-badge i { color: #1cc88a; }

    /* Modal Checkbox Styles */
    .permission-checkbox { width: 1.5rem !important; height: 1.5rem !important; flex-shrink: 0 !important; margin: 0 !important; cursor: pointer; float: none !important; position: static !important; }
    .permission-box { border: 1px solid #e3e6f0; transition: all 0.2s ease-in-out; background: #fff; cursor: pointer; display: flex; align-items: center; padding: 10px 15px !important; border-radius: 8px !important; height: 55px; }
    .permission-box:hover { background-color: #f8f9fc; border-color: #3d5afe; }
    .perm-label { font-size: 14px; font-weight: 600; color: #333; cursor: pointer; margin-left: 10px !important; overflow: hidden; text-overflow: ellipsis; white-space: normal; line-height: 1.2; padding-left: 0 !important; }
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
        tr.find('td:eq(2) .permission-badge').each(function () {
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
                    <div class="permission-box shadow-sm d-flex align-items-center p-2 rounded border" onclick="$(this).find('.permission-checkbox').click()">
                        <input class="permission-checkbox m-0" type="checkbox" name="permissions[]" value="${p.name}" ${isChecked} id="p_${p.id}" onclick="event.stopPropagation()" style="cursor:pointer; width:20px; height:20px; min-width:20px;">
                        <span class="perm-label ms-2" style="font-weight:600; color:#444; font-size:14px; pointer-events:none;">${p.name}</span>
                    </div>
                </div>
            `;
            $('#permission-checkbox-container').append(html);
        });

        // Check select all status
        var allChecked = $('.permission-checkbox:not(:checked)').length === 0;
        $('#select-all-permissions').prop('checked', allChecked);

        $("#edit-permission-modal").modal("show");
    });

    $(document).on('change', '#select-all-permissions', function() {
        $('.permission-checkbox').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.permission-checkbox', function() {
        var allChecked = $('.permission-checkbox:not(:checked)').length === 0;
        $('#select-all-permissions').prop('checked', allChecked);
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
