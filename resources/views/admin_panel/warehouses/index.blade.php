@extends('admin_panel.layout.app')
@section('content')

<style>
    /* Ultra-High Density Design System */
    .main-content-inner { background: #f4f7fa; min-height: 100vh; }
    
    /* Table Density */
    #warehouseTable { font-size: 11px !important; border-collapse: separate !important; border-spacing: 0; width: 100% !important; }
    #warehouseTable thead th { 
        padding: 2px 10px !important; 
        font-size: 11px !important; 
        height: 20px !important;
        line-height: 1.2 !important;
        background: #fff !important;
        color: #444 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 2px solid #ebedef !important;
        vertical-align: middle !important;
    }
    
    /* DataTables Sorting Arrow Fix */
    table.dataTable thead .sorting:before, table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:before, table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:before, table.dataTable thead .sorting_desc:after {
        bottom: 2px !important;
        font-size: 0.7rem !important;
        opacity: 0.3;
    }

    #warehouseTable tbody td { 
        padding: 4px 10px !important; 
        vertical-align: middle !important; 
        border-bottom: 1px solid #f0f2f5 !important;
        white-space: nowrap;
    }
    #warehouseTable tbody tr:hover { background-color: #f8f9ff !important; }

    /* Compact Buttons */
    .btn-xs { padding: 1px 5px; font-size: 10px; line-height: 1.2; border-radius: 3px; }
    .btn-mini { padding: 0px 4px; font-size: 9px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    
    /* DataTables Export Buttons styling */
    .dt-buttons { margin-bottom: 0px !important; }
    .dt-button { 
        padding: 2px 10px !important; 
        font-size: 10px !important; 
        border-radius: 4px !important; 
        background: #fff !important;
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        transition: all 0.2s;
    }
    .dt-button:hover { background: #f8f9fa !important; border-color: #adb5bd !important; }

    /* Filter Bar Compact */
    .form-control-sm, .form-select-sm { font-size: 11px !important; height: calc(1.5em + 0.5rem + 2px) !important; padding: 0.25rem 0.5rem !important; }
    
    /* Column Picker Styles */
    .column-picker-dropdown { position: relative; display: inline-block; }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 10000;
        display: none;
        min-width: 220px;
        padding: 8px 0;
        margin-top: 5px;
        font-size: 13px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.1);
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show { display: block; }
    .column-picker-item {
        display: flex;
        align-items: center;
        padding: 6px 16px;
        clear: both;
        font-weight: 400;
        line-height: 1.5;
        color: #444;
        white-space: nowrap;
        cursor: pointer;
        transition: background 0.2s;
    }
    .column-picker-item:hover { background-color: #f8f9fa; color: #000; }
    .column-picker-item input { margin-right: 12px; cursor: pointer; width: 16px; height: 16px; }

    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border: none; margin-bottom: 0.5rem; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-1">
            
            <!-- Actions Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-building me-2 text-primary"></i>Warehouses</h6>
                                </div>
                                <div class="col-md-9 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        @if($isAdmin)
                                        <form action="{{ url('warehouse') }}" method="GET" class="d-flex gap-1 align-items-center me-2">
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
                                                <a href="{{ url('warehouse') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
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

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list me-1"></i> Warehouse List</span>
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
                                    <th>Type</th>
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
                                    <td class="text-muted">{{ $key+1 }}</td>
                                    <td class="fw-bold text-primary">{{ $w->warehouse_name }}</td>
                                    <td>
                                        @if($w->claim_type == 'customer')
                                            <span class="badge bg-warning text-dark border px-2 py-0" style="font-size: 9px;">Customer Claim</span>
                                        @elseif($w->claim_type == 'company')
                                            <span class="badge bg-danger border px-2 py-0" style="font-size: 9px;">Company Claim</span>
                                        @else
                                            <span class="badge bg-secondary border px-2 py-0" style="font-size: 9px;">Normal</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($w->user_group_ids))
                                            @foreach($w->user_group_ids as $groupId)
                                                <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 9px;">
                                                    {{ $userGroupsKeyed[$groupId]->group_name ?? 'N/A' }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">No Group</span>
                                        @endif
                                    </td>
                                    @if($isAdmin)
                                        <td class="small text-muted">{{ $w->creator->name ?? 'System' }}</td>
                                    @endif
                                    <td class="small">{{ $w->location }}</td>
                                    <td class="small text-muted">{{ Str::limit($w->remarks, 30) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-outline-primary btn-mini edit-warehouse-btn"
                                                data-id="{{ $w->id }}"
                                                data-name="{{ $w->warehouse_name }}"
                                                data-location="{{ $w->location }}"
                                                data-remarks="{{ $w->remarks }}"
                                                data-claim_type="{{ $w->claim_type }}"
                                                data-user_group_ids="{{ json_encode($w->user_group_ids ?? []) }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#warehouseModal"
                                                title="Edit">
                                                <i class="fa fa-pencil text-dark"></i>
                                            </button>

                                            <a href="{{ url('warehouse/delete/'.$w->id) }}"
                                                class="btn btn-outline-danger btn-mini"
                                                onclick="return confirm('Delete?')"
                                                title="Delete">
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

{{-- Modal remains same but with small styling --}}
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
                        <label class="form-label small fw-bold">Warehouse Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="claim_type" id="type_none" value="none" checked>
                                <label class="form-check-label small" for="type_none">Normal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="claim_type" id="type_customer" value="customer">
                                <label class="form-check-label small" for="type_customer">Customer Claim</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="claim_type" id="type_company" value="company">
                                <label class="form-check-label small" for="type_company">Company Claim</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Assigned User Groups</label>
                        @php 
                            $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1;
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
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Warehouse</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Column Picker Logic
    $('#columnPickerBtn').on('click', function(e) {
        e.stopPropagation();
        $('#columnPickerMenu').toggleClass('show');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.column-picker-dropdown').length) {
            $('#columnPickerMenu').removeClass('show');
        }
    });

    const storageKey = 'warehouse_table_cols_v2';

    var dt = $('#warehouseTable').DataTable({
        "order": [[0, 'asc']], 
        "pageLength": 25,
        "scrollX": true,
        "autoWidth": false,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search warehouses..."
        },
        dom: 'Bfrtip',
        buttons: [
            'copyHtml5', 'excelHtml5', 'csvHtml5'
        ]
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

    $('#columnPickerMenu input').on('change', function() {
        const colIdx = parseInt($(this).data('column'));
        const isChecked = $(this).is(':checked');
        dt.column(colIdx - 1).visible(isChecked);
        
        const state = {};
        $('#columnPickerMenu input').each(function() {
            state[$(this).data('column')] = $(this).is(':checked');
        });
        localStorage.setItem(storageKey, JSON.stringify(state));
        dt.columns.adjust().draw(false);
    });

    // Modal select2 fix
    $('#warehouseModal').on('shown.bs.modal', function() {
        $('.select2-groups-warehouse').select2({
            dropdownParent: $('#warehouseModal'),
            width: '100%',
            placeholder: "Select Groups"
        });
    });
    
    $('.select2:not(.modal select)').select2({ width: '100%' });
});

function clearWarehouse() {
    $('#warehouse_id').val('');
    $('#warehouse_name').val('');
    $('#location').val('');
    $('#remarks').val('');
    $('input[name="claim_type"][value="none"]').prop('checked', true);
    $('#warehouse_user_groups').val([]).trigger('change');
}

$(document).on('click', '.edit-warehouse-btn', function() {
    const btn = $(this);
    $('#warehouse_id').val(btn.data('id'));
    $('#warehouse_name').val(btn.data('name'));
    $('#location').val(btn.data('location'));
    $('#remarks').val(btn.data('remarks'));
    
    const claimType = btn.data('claim_type') || 'none';
    $('input[name="claim_type"][value="' + claimType + '"]').prop('checked', true);
    
    const groups = btn.data('user_group_ids') ?? [];
    $('#warehouse_user_groups').val(groups).trigger('change');
});
</script>
@endsection