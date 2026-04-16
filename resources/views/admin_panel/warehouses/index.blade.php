@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="page-header row">
                <div class="page-title col-lg-9">
                    <h4>Warehouse List</h4>
                    <h6>Manage Warehouses</h6>
                </div>
                <div class="page-btn col-lg-3 text-center">
                    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="clearWarehouse()">Add Warehouse</button>
                </div>
            </div>

            @if($isAdmin)
            <div class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <form action="{{ url('warehouse') }}" method="GET" class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold">Filter by User:</label>
                            <select name="created_by" class="form-control form-control-sm select2">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-sm btn-info mt-4">Filter</button>
                            @if(request('created_by'))
                                <a href="{{ url('warehouse') }}" class="btn btn-sm btn-secondary mt-4">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            @endif

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
                                <th>Groups</th>
                                @if($isAdmin)
                                    <th>Created By</th>
                                @endif
                                <th>Location</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $userGroupsKeyed = $userGroups->keyBy('id'); @endphp
                            @foreach($warehouses as $key => $w)
                            <tr>
                                <td>{{ $key+1 }}</td>
                                <td>{{ $w->warehouse_name }}</td>
                                <td>
                                    @if(!empty($w->user_group_ids))
                                        @foreach($w->user_group_ids as $groupId)
                                            <span class="badge bg-light text-dark border">
                                                {{ $userGroupsKeyed[$groupId]->group_name ?? 'N/A' }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">No Group</span>
                                    @endif
                                </td>
                                @if($isAdmin)
                                    <td>{{ $w->creator->name ?? 'System' }}</td>
                                @endif
                                <td>{{ $w->location }}</td>
                                <td>{{ $w->remarks }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-warehouse-btn"
                                        data-id="{{ $w->id }}"
                                        data-name="{{ $w->warehouse_name }}"
                                        data-location="{{ $w->location }}"
                                        data-remarks="{{ $w->remarks }}"
                                        data-user_group_ids="{{ json_encode($w->user_group_ids ?? []) }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#warehouseModal">
                                        Edit
                                    </button>
                                    <a href="{{ url('warehouse/delete/'.$w->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
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

<div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ url('warehouse/store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="warehouse_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Warehouse Name</label>
                        <input class="form-control" name="warehouse_name" id="warehouse_name" placeholder="Enter warehouse name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input class="form-control" name="location" id="location" placeholder="Enter location">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Any remarks..."></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label><strong>Assigned User Groups:</strong></label>
                        @php 
                            $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::user()->usertype == 'admin';
                        @endphp
                        @if($isAdmin)
                            <select name="user_group_ids[]" id="warehouse_user_groups" class="form-control select2-groups-warehouse" multiple style="width: 100%;" data-placeholder="Select Groups">
                                @foreach($userGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="form-control bg-light" style="height: auto; min-height: 38px;">
                                @php $myGroups = Auth::user()->userGroups; @endphp
                                @if($myGroups->count() > 0)
                                    @foreach($myGroups as $group)
                                        <span class="badge bg-info text-dark">{{ $group->group_name }}</span>
                                        <input type="hidden" name="user_group_ids[]" value="{{ $group->id }}">
                                    @endforeach
                                @else
                                    <span class="text-muted">No Groups Assigned to You</span>
                                @endif
                            </div>
                            <small class="text-muted">Your groups are automatically assigned.</small>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Warehouse</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.datanew').DataTable();

    // Initialize Select2 specifically for the modal using unique class
    $('#warehouseModal').on('shown.bs.modal', function() {
        $('.select2-groups-warehouse').select2({
            dropdownParent: $('#warehouseModal'),
            width: '100%',
            placeholder: "Select Groups"
        });
    });
    
    // For non-modal select2 (filters)
    $('.select2:not(.modal select)').select2({ width: '100%' });
});

    function clearWarehouse() {
        $('#warehouse_id').val('');
        $('#warehouse_name').val('');
        $('#location').val('');
        $('#remarks').val('');
        $('#warehouse_user_groups').val([]).trigger('change');
    }

    $(document).on('click', '.edit-warehouse-btn', function() {
        const btn = $(this);
        $('#warehouse_id').val(btn.data('id'));
        $('#warehouse_name').val(btn.data('name'));
        $('#location').val(btn.data('location'));
        $('#remarks').val(btn.data('remarks'));
        
        const groups = btn.data('user_group_ids') ?? [];
        $('#warehouse_user_groups').val(groups).trigger('change');
    });
</script>
@endsection