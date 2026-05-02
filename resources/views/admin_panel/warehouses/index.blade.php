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
    
    #warehouseTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 13px;
    }
    
    #warehouseTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 6px 10px !important;
        font-size: 12px;
        color: #333;
    }

    /* Column Picker Styles */
    .column-picker-dropdown {
        position: relative;
        display: inline-block;
    }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 1000;
        display: none;
        min-width: 200px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0,0,0,.175);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show {
        display: block;
    }
    .column-picker-item {
        display: block;
        padding: 5px 15px;
        clear: both;
        font-weight: 400;
        line-height: 1.42857143;
        color: #333;
        white-space: nowrap;
        cursor: pointer;
    }
    .column-picker-item:hover {
        background-color: #f5f5f5;
    }
    .column-picker-item input {
        margin-right: 10px;
        cursor: pointer;
    }
    .column-hidden {
        display: none !important;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">

            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
                    <i class="fa fa-times-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Top Actions & Filter -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-building me-2 text-primary"></i>Warehouse Management</h5>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex gap-2 justify-content-end align-items-center">
                                        @if($isAdmin)
                                        <form action="{{ url('warehouse') }}" method="GET" class="d-flex gap-2 align-items-center me-2">
                                            <select name="created_by" class="form-select form-select-sm select2" style="min-width: 150px;">
                                                <option value="">All Users</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                            @if(request('created_by'))
                                                <a href="{{ url('warehouse') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Reset</a>
                                            @endif
                                        </form>
                                        @endif
                                        
                                        <button class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="clearWarehouse()">
                                            <i class="fa fa-plus me-1"></i> Add Warehouse
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Warehouse List</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Name</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Groups</label>
                                    @if($isAdmin)<label class="column-picker-item"><input type="checkbox" data-column="4" checked> Created By</label>@endif
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Location</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Remarks</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="warehouseTable" class="table table-sm table-striped table-bordered w-100 mb-0">
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
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $userGroupsKeyed = $userGroups->keyBy('id'); @endphp
                                        @foreach($warehouses as $key => $w)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td class="fw-bold text-primary">{{ $w->warehouse_name }}</td>
                                            <td>
                                                @if(!empty($w->user_group_ids))
                                                    @foreach($w->user_group_ids as $groupId)
                                                        <span class="badge bg-light text-dark border-0 shadow-xs px-2 py-1" style="font-size: 10px;">
                                                            {{ $userGroupsKeyed[$groupId]->group_name ?? 'N/A' }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">No Group</span>
                                                @endif
                                            </td>
                                            @if($isAdmin)
                                                <td><small>{{ $w->creator->name ?? 'System' }}</small></td>
                                            @endif
                                            <td>{{ $w->location }}</td>
                                            <td><small>{{ Str::limit($w->remarks, 30) }}</small></td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button class="btn btn-outline-primary btn-xs px-1 py-0 edit-warehouse-btn"
                                                        data-id="{{ $w->id }}"
                                                        data-name="{{ $w->warehouse_name }}"
                                                        data-location="{{ $w->location }}"
                                                        data-remarks="{{ $w->remarks }}"
                                                        data-user_group_ids="{{ json_encode($w->user_group_ids ?? []) }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#warehouseModal"
                                                        style="height: 20px;">
                                                        <i class="fa fa-edit"></i>
                                                    </button>

                                                    <a href="{{ url('warehouse/delete/'.$w->id) }}"
                                                        class="btn btn-outline-danger btn-xs px-1 py-0"
                                                        onclick="return confirm('Delete?')"
                                                        style="height: 20px;">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
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

<div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ url('warehouse/store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="warehouse_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fs-6">Add/Edit Warehouse</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Warehouse Name</label>
                        <input class="form-control form-control-sm" name="warehouse_name" id="warehouse_name" placeholder="Enter warehouse name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Location</label>
                        <input class="form-control form-control-sm" name="location" id="location" placeholder="Enter location">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea class="form-control form-control-sm" name="remarks" id="remarks" rows="2" placeholder="Any remarks..."></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Assigned User Groups</label>
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
                            <div class="form-control bg-light form-control-sm" style="height: auto; min-height: 38px;">
                                @php $myGroups = Auth::user()->userGroups; @endphp
                                @if($myGroups->count() > 0)
                                    @foreach($myGroups as $group)
                                        <span class="badge bg-info text-dark px-2">{{ $group->group_name }}</span>
                                        <input type="hidden" name="user_group_ids[]" value="{{ $group->id }}">
                                    @endforeach
                                @else
                                    <span class="text-muted small">No Groups Assigned</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Save Warehouse</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle Column Picker Menu
    $('#columnPickerBtn').on('click', function(e) {
        e.stopPropagation();
        $('#columnPickerMenu').toggleClass('show');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.column-picker-dropdown').length) {
            $('#columnPickerMenu').removeClass('show');
        }
    });

    const storageKey = 'warehouse_table_cols_v1';

    // Initialize DataTable
    var dt = $('#warehouseTable').DataTable({
        destroy: true,
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search warehouses..."
        }
    });

    // Apply saved column visibility
    const savedState = localStorage.getItem(storageKey);
    if (savedState) {
        const columns = JSON.parse(savedState);
        $('#columnPickerMenu input').each(function() {
            const colIdx = parseInt($(this).data('column'));
            const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
            $(this).prop('checked', checked);
            dt.column(colIdx - 1).visible(checked);
        });
        dt.columns.adjust().draw(false);
    }

    // Handle Checkbox Change
    $('#columnPickerMenu input').on('change', function() {
        const colIdx = parseInt($(this).data('column'));
        const isChecked = $(this).is(':checked');
        
        dt.column(colIdx - 1).visible(isChecked);
        dt.columns.adjust().draw(false);
        
        const state = {};
        $('#columnPickerMenu input').each(function() {
            state[$(this).data('column')] = $(this).is(':checked');
        });
        localStorage.setItem(storageKey, JSON.stringify(state));
    });

    // Initialize Select2 specifically for the modal
    $('#warehouseModal').on('shown.bs.modal', function() {
        $('.select2-groups-warehouse').select2({
            dropdownParent: $('#warehouseModal'),
            width: '100%',
            placeholder: "Select Groups"
        });
    });
    
    // For non-modal select2
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