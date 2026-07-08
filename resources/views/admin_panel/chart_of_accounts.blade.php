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
    .nature-bar {
        background: #f3f3f3;
        border: 1px solid #ccc;
        padding: 8px 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .nature-bar label {
        font-weight: 700;
        font-size: 13px;
        margin: 0;
        white-space: nowrap;
    }
    .nature-bar .head-code-box {
        width: 80px;
        text-align: center;
        font-weight: 700;
        background: #fff;
    }
    .nature-bar .head-name-box {
        min-width: 220px;
        font-weight: 600;
        background: #fff;
    }
    #accountsTable tbody tr.selected-row td {
        background: #222 !important;
        color: #fff !important;
    }
    #accountsTable tbody tr.selected-row td .text-primary,
    #accountsTable tbody tr.selected-row td .text-danger,
    #accountsTable tbody tr.selected-row td .text-success {
        color: #fff !important;
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
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-times-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                            <span class="fw-bold text-muted small text-uppercase">All Accounts</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Head Code</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Head Name</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Account Code</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Account Title</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Opening Dr.</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Opening Cr.</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Assigned User Groups</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Inactive</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="accountsTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Head Code</th>
                                            <th>Head Name</th>
                                            <th>Account Code</th>
                                            <th>Account Title</th>
                                            <th class="text-end">Opening Dr.</th>
                                            <th class="text-end">Opening Cr.</th>
                                            <th>Assigned User Groups</th>
                                            <th class="text-center">Inactive</th>
                                            <th class="text-center" style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts as $account)
                                        @php
                                            $opening = (float) ($account->opening_balance ?? 0);
                                            $openingDr = $opening > 0 ? $opening : 0;
                                            $openingCr = $opening < 0 ? abs($opening) : 0;
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $account->id }}</td>
                                            <td class="text-center fw-bold text-muted">{{ $account->head_id }}</td>
                                            <td class="fw-bold">{{ $account->head->name ?? '—' }}</td>
                                            <td class="fw-bold text-primary">{{ $account->account_code }}</td>
                                            <td class="fw-bold text-dark">{{ $account->title }}</td>
                                            <td class="text-end">{{ $openingDr > 0 ? number_format($openingDr, 2) : '0.00' }}</td>
                                            <td class="text-end">{{ $openingCr > 0 ? number_format($openingCr, 2) : '0.00' }}</td>
                                            <td>
                                                @if(!empty($account->user_group_ids))
                                                    @foreach($account->user_group_ids as $groupId)
                                                        <span class="badge bg-light text-dark border px-1 me-1" style="font-size: 9px;">
                                                            {{ $userGroups->get((int) $groupId)?->group_name ?? 'N/A' }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($account->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Disabled</span>
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
                                        @empty
                                        <tr class="no-data-row">
                                            <td colspan="10" class="text-center text-muted py-4">
                                                No accounts found.
                                            </td>
                                        </tr>
                                        @endforelse
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
            <input type="hidden" name="id" id="accountId" value="">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa fa-plus-circle me-2"></i>Account Setup</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Select Head</label>
                    <select name="head_id" class="form-select form-select-sm" id="accountHeadSelect" required>
                        <option value="">Select Head</option>
                        @foreach($activeHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->id }} — {{ $head->name }}</option>
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
        <form action="{{ route('coa.head.store') }}" method="POST" id="headForm" class="modal-content border-0 shadow">
            @csrf
            <input type="hidden" name="head_id" id="edit_head_id">
            <div class="modal-header bg-secondary text-white py-2">
                <h6 class="modal-title fw-bold" id="headModalTitle"><i class="fa fa-folder-plus me-2"></i>Add Head</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Head Code</label>
                    <input type="text" id="head_code_display" class="form-control form-control-sm bg-light" value="{{ $nextHeadId ?? '' }}" readonly>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Head Name</label>
                    <input type="text" name="name" id="head_name" class="form-control form-control-sm" placeholder="e.g. Assets" required>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" name="status" type="checkbox" value="on" id="headStatus" checked>
                    <label class="form-check-label small fw-bold" for="headStatus">Active Status</label>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm px-4 shadow-sm" id="headSubmitBtn">Create Head</button>
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
                                <th style="width: 10%;" class="text-center">Head Code</th>
                                <th>Head Name</th>
                                <th style="width: 20%;" class="text-center">Status</th>
                                <th style="width: 20%;" class="text-center">Created At</th>
                                <th style="width: 10%;" class="text-center">Action</th>
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
                                <td class="text-center">
                                    <button class="btn btn-warning btn-xs edit-head-btn"
                                        data-id="{{ $head->id }}"
                                        data-name="{{ $head->name }}"
                                        data-status="{{ $head->status }}"
                                        style="padding: 1px 5px; font-size: 10px;">
                                        <i class="fa fa-edit"></i>
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

        const storageKey = 'coa_table_columns_v5';
        const hasRows = $('#accountsTable tbody tr').not('.no-data-row').length > 0;

        var dt = hasRows ? $('#accountsTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[3, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search accounts...",
                emptyTable: "No accounts found."
            }
        }) : null;

        $('#accountsTable tbody').on('click', 'tr', function () {
            $('#accountsTable tbody tr').removeClass('selected-row');
            $(this).addClass('selected-row');
        });

        $('#addAccountModal').on('shown.bs.modal', function() {
            loadNextAccountCode();

            $('.select2-groups').select2({
                dropdownParent: $('#addAccountModal'),
                width: '100%'
            });
        });

        function loadNextAccountCode() {
            const headId = $('#accountHeadSelect').val();
            if (!headId) {
                $('input[name="account_code"]').val('');
                return;
            }
            $.get("{{ route('coa.account.next_code', ['headId' => '__HEAD__']) }}".replace('__HEAD__', headId), function(data) {
                $('input[name="account_code"]').val(data.code);
            }).fail(function() {
                $('input[name="account_code"]').val('');
            });
        }

        // Apply saved column visibility
        const savedState = localStorage.getItem(storageKey);
        if (savedState && dt) {
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
            if (!dt) return;
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
        $('#accountHeadSelect').on('change', loadNextAccountCode);

        // Edit Account functionality
        $(document).on('click', '.edit-account-btn', function() {
            const btn = $(this);
            const modal = $('#addAccountModal');
            modal.find('.modal-title').html('<i class="fa fa-edit me-2"></i>Edit Account');
            modal.find('#accountId').val(btn.data('id'));
            modal.find('select[name="head_id"]').val(btn.data('head_id'));
            modal.find('input[name="account_code"]').val(btn.data('code'));
            modal.find('input[name="title"]').val(btn.data('title'));
            modal.find('input[name="opening_balance"]').val(btn.data('balance'));
            modal.find('#accStatus').prop('checked', btn.data('status') == 1);
            const groups = btn.data('groups') ?? [];
            modal.find('#accGroups').val(groups).trigger('change');
        });

        // Edit Head functionality
        $(document).on('click', '.edit-head-btn', function() {
            const btn = $(this);
            const headModal = $('#addHeadModal');
            $('#listHeadsModal').modal('hide');
            
            headModal.find('#headModalTitle').html('<i class="fa fa-edit me-2"></i>Edit Head');
            headModal.find('#edit_head_id').val(btn.data('id'));
            headModal.find('#head_code_display').val(btn.data('id'));
            headModal.find('#head_name').val(btn.data('name'));
            headModal.find('#headStatus').prop('checked', btn.data('status') == 1);
            headModal.find('#headSubmitBtn').text('Update Head');
            
            headModal.modal('show');
        });

        // Clear modal on hide
        $('#addAccountModal').on('hidden.bs.modal', function() {
            const modal = $(this);
            modal.find('.modal-title').html('<i class="fa fa-plus-circle me-2"></i>Account Setup');
            modal.find('#accountId').val('');
            modal.find('select[name="head_id"]').val('');
            modal.find('input[name="account_code"]').val('');
            modal.find('input[name="title"]').val('');
            modal.find('input[name="opening_balance"]').val('0.00');
            modal.find('#accStatus').prop('checked', true);
            modal.find('#accGroups').val([]).trigger('change');
        });

        // Clear Head modal on hide
        $('#addHeadModal').on('hidden.bs.modal', function() {
            const modal = $(this);
            modal.find('#headModalTitle').html('<i class="fa fa-folder-plus me-2"></i>Add Head');
            modal.find('#edit_head_id').val('');
            modal.find('#head_code_display').val('{{ $nextHeadId ?? "" }}');
            modal.find('#head_name').val('');
            modal.find('#headStatus').prop('checked', true);
            modal.find('#headSubmitBtn').text('Create Head');
        });
    });
</script>
@endsection