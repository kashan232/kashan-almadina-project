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
    
    #vendorTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 13px;
    }
    
    #vendorTable tbody td {
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
                                    <h5 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-users me-2 text-primary"></i>Vendor Management</h5>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex gap-2 justify-content-end align-items-center">
                                        @if($isAdmin)
                                        <form action="{{ url('vendor') }}" method="GET" class="d-flex gap-2 align-items-center me-2">
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
                                                <a href="{{ url('vendor') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Reset</a>
                                            @endif
                                        </form>
                                        @endif
                                        
                                        <div class="btn-group">
                                            <a href="{{ route('vendor.ledger') }}" class="btn btn-outline-info btn-sm rounded-pill px-3 me-1">Ledger</a>
                                            <a href="{{ route('vendor.payments.index') }}" class="btn btn-outline-warning btn-sm rounded-pill px-3 text-dark">Payments</a>
                                        </div>

                                        <a href="{{ route('vendor.create') }}" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                                            <i class="fa fa-plus me-1"></i> Add Vendor
                                        </a>
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
                            <span class="fw-bold text-muted small text-uppercase">Vendor List</span>
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
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Phone</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Opening Balance</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Closing Balance</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Address</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="vendorTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Groups</th>
                                            @if($isAdmin)
                                                <th>Created By</th>
                                            @endif
                                            <th>Phone</th>
                                            <th class="text-end">Opening Balance</th>
                                            <th class="text-end">Closing Balance</th>
                                            <th>Address</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $userGroupsKeyed = $userGroups->keyBy('id'); @endphp
                                        @foreach($vendors as $key => $v)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td class="fw-bold text-primary">{{ $v->name }}</td>
                                            <td>
                                                @if(!empty($v->user_group_ids))
                                                    @foreach($v->user_group_ids as $groupId)
                                                        <span class="badge bg-light text-dark border-0 shadow-xs px-2 py-1" style="font-size: 10px;">
                                                            {{ $userGroupsKeyed[$groupId]->group_name ?? 'N/A' }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">No Group</span>
                                                @endif
                                            </td>
                                            @if($isAdmin)
                                                <td><small>{{ $v->creator->name ?? 'System' }}</small></td>
                                            @endif
                                            <td>{{ $v->phone }}</td>
                                            <td class="text-end fw-bold">{{ number_format($v->latestLedger->opening_balance ?? 0, 0) }}</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($v->latestLedger->closing_balance ?? 0, 0) }}</td>
                                            <td><small>{{ Str::limit($v->address, 30) }}</small></td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="{{ route('vendor.edit', $v->id) }}" class="btn btn-outline-primary btn-xs px-1 py-0" style="height: 20px;">
                                                        <i class="fa fa-edit"></i>
                                                    </a>

                                                    <a href="{{ url('vendor/delete/'.$v->id) }}"
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

    const storageKey = 'vendor_table_cols_v1';
    
    // Initialize DataTable
    var dt = $('#vendorTable').DataTable({
        destroy: true,
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search vendors..."
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
    
    // Handle Select2
    $('.select2').select2({ width: '100%' });
});
</script>
@endsection