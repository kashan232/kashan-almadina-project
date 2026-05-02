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
    
    #accountsTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #accountsTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 10px !important;
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

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
    
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
    }

    .badge-soft-success {
        background-color: #e1f6e1;
        color: #28a745;
    }
    .badge-soft-danger {
        background-color: #fdeaea;
        color: #dc3545;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @endif

            <!-- Header and Filters -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 fw-bold text-dark ms-2 me-4"><i class="fa fa-sitemap me-2 text-primary"></i>Chart of Accounts</h6>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                                        <i class="fa fa-plus-circle me-1"></i> Add Account
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addHeadModal">
                                        <i class="fa fa-folder-plus me-1"></i> Add Head
                                    </button>
                                    <button class="btn btn-outline-info btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#listHeadsModal">
                                        <i class="fa fa-list-ul me-1"></i> Heads
                                    </button>
                                </div>
                            </div>
                            
                            @if($isAdmin)
                            <div class="d-flex gap-1 align-items-center">
                                <form action="{{ route('view_all') }}" method="GET" class="d-flex gap-1 align-items-center">
                                    <select name="created_by" class="form-select form-select-sm select2" style="min-width: 150px;">
                                        <option value="">All Creators</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
                                    @if(request('created_by'))
                                        <a href="{{ route('view_all') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                    @endif
                                </form>
                                <a href="{{ route('purcahse-account-allocation') }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-2">
                                    <i class="fa fa-history me-1"></i> History
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Accounts Registry</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Account Code</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Head</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Account Title</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Groups</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Balance</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Status</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="accountsTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Code</th>
                                            <th>Head</th>
                                            <th>Account Title</th>
                                            <th>Groups</th>
                                            <th class="text-end">Balance</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accounts as $key => $account)
                                        <tr>
                                            <td class="text-center text-muted small">{{ $key+1 }}</td>
                                            <td class="fw-bold text-primary">{{ $account->account_code }}</td>
                                            <td><span class="small fw-semibold text-muted">{{ $account->head->name }}</span></td>
                                            <td class="fw-bold text-dark">{{ $account->title }}</td>
                                            <td class="small">
                                                @if(!empty($account->user_group_ids))
                                                    @foreach($account->user_group_ids as $groupId)
                                                        @php $group = $userGroups->find($groupId); @endphp
                                                        <span class="badge bg-info text-dark border-0 rounded-pill px-2" style="font-size: 9px;">{{ $group->group_name ?? 'N/A' }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted" style="font-size: 10px;">Global</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-danger">{{ number_format($account->opening_balance, 0) }}</td>
                                            <td class="text-center">
                                                @if($account->status)
                                                    <span class="badge bg-success rounded-pill px-3">Active</span>
                                                @else
                                                    <span class="badge bg-danger rounded-pill px-3">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-warning btn-xs px-1 py-0 edit-account-btn" 
                                                    data-id="{{ $account->id }}"
                                                    data-head_id="{{ $account->head_id }}"
                                                    data-code="{{ $account->account_code }}"
                                                    data-title="{{ $account->title }}"
                                                    data-balance="{{ $account->opening_balance }}"
                                                    data-status="{{ $account->status }}"
                                                    data-groups="{{ json_encode($account->user_group_ids ?? []) }}"
                                                    data-bs-toggle="modal" data-bs-target="#addAccountModal"
                                                    style="height: 20px;">
                                                    <i class="fa fa-edit text-dark"></i>
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
</div>

{{-- MODALS --}}

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.account.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa fa-plus-circle me-2"></i>Account Setup</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Select Head</label>
                    <select name="head_id" class="form-select form-select-sm" required>
                        <option value="">Select Head</option>
                        @foreach($heads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Account Code</label>
                    <input type="text" name="account_code" class="form-control form-control-sm bg-light" placeholder="Auto-generated" readonly required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Account Title</label>
                    <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Cash in Hand" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Opening Balance</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">₨</span>
                        <input type="number" step="0.01" name="opening_balance" class="form-control text-end" value="0.00">
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" name="status" type="checkbox" value="on" id="accStatus" checked>
                    <label class="form-check-label small fw-bold" for="accStatus">Active Status</label>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-bold">Assigned User Groups</label>
                    @if($isAdmin)
                        <select name="user_group_ids[]" id="accGroups" class="form-control select2-groups" multiple style="width: 100%;" data-placeholder="Select Groups">
                            @foreach($userGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="form-control form-control-sm bg-light" style="height: auto; min-height: 38px;">
                            @php $myGroups = Auth::user()->userGroups; @endphp
                            @if($myGroups->count() > 0)
                                @foreach($myGroups as $group)
                                    <span class="badge bg-info text-dark rounded-pill px-2 me-1">{{ $group->group_name }}</span>
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
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Head Modal -->
<div class="modal fade" id="addHeadModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('coa.head.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-secondary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa fa-folder-plus me-2"></i>Add Head</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Head Code</label>
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $nextHeadId ?? '' }}" readonly>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Head Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Assets" required>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm px-4 shadow-sm">Create Head</button>
            </div>
        </form>
    </div>
</div>

<!-- List of Heads Modal -->
<div class="modal fade" id="listHeadsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa fa-list-ul me-2"></i>Account Heads</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive">
                    <table id="headsTable" class="table table-sm table-hover table-bordered w-100">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 10%;" class="text-center">ID</th>
                                <th>Head Name</th>
                                <th style="width: 20%;" class="text-center">Status</th>
                                <th style="width: 20%;" class="text-center">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heads as $head)
                            <tr style="font-size: 12px;">
                                <td class="fw-bold text-center text-muted">{{ $head->id }}</td>
                                <td class="fw-bold text-dark">{{ $head->name }}</td>
                                <td class="text-center">
                                    @if($head->status)
                                        <span class="badge bg-success rounded-pill px-3">Active</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-3">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center small text-muted">{{ $head->created_at ? $head->created_at->format('d-M-Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

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

        const storageKey = 'coa_table_columns_v2';
        
        var dt = $('#accountsTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search accounts..."
            }
        });

        // Initialize Select2 in modal
        $('#addAccountModal').on('shown.bs.modal', function() {
            $('.select2-groups').select2({
                dropdownParent: $('#addAccountModal'),
                width: '100%'
            });
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
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            dt.column(parseInt(colIdx) - 1).visible(isChecked);
            
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
            dt.columns.adjust().draw(false);
        });

        // Initialize DataTable for Heads Table inside Modal
        $('#headsTable').DataTable({
            pageLength: 10,
            searching: false,
            lengthChange: false
        });

        // Auto-generate Account Code on Head selection
        $('select[name="head_id"]').on('change', function() {
            const headId = $(this).val();
            if (headId) {
                $.get("{{ url('/coa/next-account-code') }}/" + headId, function(data) {
                    $('input[name="account_code"]').val(data.code);
                });
            } else {
                $('input[name="account_code"]').val('');
            }
        });

        // Edit Account functionality
        $(document).on('click', '.edit-account-btn', function() {
            const btn = $(this);
            const modal = $('#addAccountModal');
            modal.find('.modal-title').html('<i class="fa fa-edit me-2"></i>Edit Account');
            modal.find('select[name="head_id"]').val(btn.data('head_id'));
            modal.find('input[name="account_code"]').val(btn.data('code'));
            modal.find('input[name="title"]').val(btn.data('title'));
            modal.find('input[name="opening_balance"]').val(btn.data('balance'));
            modal.find('#accStatus').prop('checked', btn.data('status') == 1);
            const groups = btn.data('groups') ?? [];
            modal.find('#accGroups').val(groups).trigger('change');
        });

        // Clear modal on hide
        $('#addAccountModal').on('hidden.bs.modal', function() {
            const modal = $(this);
            modal.find('.modal-title').html('<i class="fa fa-plus-circle me-2"></i>Account Setup');
            modal.find('select[name="head_id"]').val('');
            modal.find('input[name="account_code"]').val('');
            modal.find('input[name="title"]').val('');
            modal.find('input[name="opening_balance"]').val('0.00');
            modal.find('#accStatus').prop('checked', true);
            modal.find('#accGroups').val([]).trigger('change');
        });
    });
</script>
@endsection