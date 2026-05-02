@extends('admin_panel.layout.app')

@section('content')
<!-- Select2 CSS (Customized for this theme) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        min-height: 40px;
        padding: 2px 5px;
    }
    .select2-container { z-index: 9999 !important; }
    .badge-user { font-size: 11px; margin: 2px; }
    .card-custom { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
    .page-title { font-weight: 700; color: #333; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="page-title">User Groups</h3>
                        <button type="button" class="btn btn-primary shadow-sm" id="reset-form">
                            <i class="fa fa-plus-circle me-1"></i> Create Group
                        </button>
                    </div>

                    <div class="card-custom">
                        <div class="table-responsive">
                            <table id="user-group-table" class="table table-hover align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th width="80">Id</th>
                                         <th>Group Name</th>
                                         <th>Shop Access</th>
                                         <th>Assigned Users</th>
                                         <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($user_groups as $group)
                                        <tr>
                                            <td class="text-center">{{ $group->id }}</td>
                                            <td class="fw-bold text-primary group-name-text">{{ $group->group_name }}</td>
                                             <td>
                                                @if($group->allow_shop)
                                                    <span class="badge bg-success rounded-pill px-3">Allowed</span>
                                                @else
                                                    <span class="badge bg-danger rounded-pill px-3">Restricted</span>
                                                @endif
                                                <span class="d-none group-allow-shop">{{ $group->allow_shop }}</span>
                                             </td>
                                             <td>
                                                 @foreach($group->users as $u)
                                                     <span class="badge bg-info text-dark badge-user shadow-sm">{{ $u->email }}</span>
                                                 @endforeach
                                                 <span class="d-none group-user-ids">{{ $group->users->pluck('id') }}</span>
                                                 <span class="d-none group-id-val">{{ $group->id }}</span>
                                             </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning edit-btn px-3 rounded-pill" 
                                                        data-id="{{ $group->id }}">
                                                    <i class="fa fa-edit me-1"></i> Edit
                                                </button>
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

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exampleModalLabel shadow-sm">User Group Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="groupForm" class="myform" action="{{ route('user-group.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_id" id="edit_id_input" />
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Group Name</label>
                        <input type="text" name="group_name" id="group_name_input" class="form-control" placeholder="Enter group name..." required />
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Assign Users</label>
                        <select name="user_ids[]" id="user_ids_select" class="form-control select2" multiple required style="width: 100%;">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->email }}</option>
                            @endforeach
                        </select>
                        <div class="form-text mt-2 small">Select one or more users to assign to this group.</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_shop" value="1" id="allow_shop_input">
                            <label class="form-check-label fw-bold text-dark" for="allow_shop_input">
                                <i class="fa fa-shopping-cart text-primary me-1"></i> Allow Access to Shop Stock
                            </label>
                        </div>
                        <div class="form-text small">If enabled, users in this group can see "Shop Stock" in warehouse dropdowns.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 save-btn">Submit Changes</button>
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
        // DataTable
        var table = $('#user-group-table').DataTable({
            "pageLength": 10,
            "order": [[0, 'desc']],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search group..."
            }
        });

        // Initialize Select2 properly for modal
        function initSelect2() {
            $('.select2').select2({
                dropdownParent: $('#exampleModal'),
                placeholder: "Type to find users...",
                allowClear: true
            });
        }

        $('#exampleModal').on('shown.bs.modal', function () {
            initSelect2();
        });

        // Create
        $('#reset-form').click(function() {
            $('#groupForm')[0].reset();
            $('#edit_id_input').val('');
            $('#user_ids_select').val([]).trigger('change');
            $('#allow_shop_input').prop('checked', false);
            $('#exampleModalLabel').text('Create New User Group');
            $('#exampleModal').modal('show');
        });

        // Edit
        $(document).on('click', '.edit-btn', function() {
            var $row = $(this).closest('tr');
            var id = $row.find('.group-id-val').text();
            var name = $row.find('.group-name-text').text();
            var users = JSON.parse($row.find('.group-user-ids').text());
            var allowShop = $row.find('.group-allow-shop').text().trim() == '1';

            $('#edit_id_input').val(id);
            $('#group_name_input').val(name);
            $('#allow_shop_input').prop('checked', allowShop);
            
            $('#exampleModalLabel').text('Edit Group: ' + name);
            $('#exampleModal').modal('show');

            // Wait for modal show to sync Select2
            $('#exampleModal').one('shown.bs.modal', function() {
                $('#user_ids_select').val(users).trigger('change');
            });
        });

        // Ajax Form Submit (Uses global myAjax from footer.blade.php)
        $(document).on('submit', '.myform', function(e) {
            e.preventDefault();
            var formdata = new FormData(this);
            var url = $(this).attr('action');
            var method = $(this).attr('method');
            $(this).find(':submit').prop('disabled', true);
            
            myAjax(url, formdata, method, function(res) {
                // Success callback if needed
            });
        });
    });
</script>
@endsection
