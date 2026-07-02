@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Receipt Voucher Report Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.receipt-voucher.preview') }}" method="POST" id="reportForm">
                    @csrf

                    <div class="filter-grid-container">
                        <div class="row g-1 mb-3">
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="group-list"> Group
                                    </div>
                                    <div class="filter-list" id="group-list">
                                        @foreach($userGroups as $group)
                                            <div class="filter-item">
                                                <input type="checkbox" name="user_group[]" value="{{ $group->id }}">
                                                <span>{{ $group->group_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 110px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="officer-list"> User
                                    </div>
                                    <div class="filter-list" id="officer-list">
                                        @foreach($users as $u)
                                            <div class="filter-item" data-groups="{{ $u->userGroups->pluck('id')->implode(',') }}">
                                                <input type="checkbox" name="sales_officer[]" value="{{ $u->id }}">
                                                <span>{{ $u->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 110px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="main-head-list"> Main Head
                                    </div>
                                    <div class="filter-list" id="main-head-list">
                                        @foreach($accountHeads as $head)
                                            <div class="filter-item">
                                                <input type="checkbox" name="main_head[]" value="{{ $head->id }}">
                                                <span>{{ $head->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 110px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="sub-head-list"> Sub Head
                                    </div>
                                    <div class="filter-list" id="sub-head-list">
                                        @foreach($accountHeads as $head)
                                            <div class="filter-item" data-head-id="{{ $head->id }}">
                                                <input type="checkbox" name="sub_head[]" value="{{ $head->id }}">
                                                <span>{{ $head->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2" style="min-width: 130px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="account-list"> Account
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="accountSearch" placeholder="Search account..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="account-list">
                                        @foreach($accounts as $acc)
                                            <div class="filter-item" data-head-id="{{ $acc->head_id }}" data-search="{{ strtolower($acc->title) }}">
                                                <input type="checkbox" name="account[]" value="{{ $acc->id }}">
                                                <span>{{ $acc->title }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2" style="min-width: 130px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="party-list"> Party
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="partySearch" placeholder="Search party..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="party-list">
                                        @foreach($customers as $cust)
                                            <div class="filter-item" data-search="{{ strtolower($cust->customer_name) }}">
                                                <input type="checkbox" name="party[]" value="{{ $cust->customer_type === 'Walking Customer' ? 'walkin' : 'customer' }}:{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item" data-search="{{ strtolower($vendor->name) }}">
                                                <input type="checkbox" name="party[]" value="vendor:{{ $vendor->id }}">
                                                <span>{{ $vendor->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border border-primary mb-3 shadow-sm">
                        <div class="card-header py-1 bg-primary text-white fw-bold small">
                            <i class="fas fa-cog me-1"></i> REPORT SETTINGS
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                    <select name="report_type" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="source_party" selected>Source Party Name</option>
                                        <option value="sub_head">Sub Head</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Vouc. ID</label>
                                    <input type="text" name="voucher_id" class="form-control form-control-sm" placeholder="12318..." style="height: 30px; font-size: 12px;">
                                </div>
                            </div>
                            <div class="row g-2 align-items-end mt-2">
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Receipt From</label>
                                    <input type="date" name="receipt_from" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Receipt To</label>
                                    <input type="date" name="receipt_to" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Entry From</label>
                                    <input type="date" name="entry_from" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Entry To</label>
                                    <input type="date" name="entry_to" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 30px; font-size: 12px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center pb-4">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                                <i class="fas fa-eye me-1"></i> Preview Report
                            </button>
                            <button type="button" class="btn btn-outline-danger px-4" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    body { background-color: #f4f7f6; }
    .filter-column {
        border: 1px solid #ced4da;
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 280px !important;
    }
    .filter-header {
        background: #f1f3f5;
        padding: 6px 10px;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        font-size: 12px;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        flex-shrink: 0;
    }
    .filter-list { flex-grow: 1; overflow-y: auto; padding: 0; }
    .filter-item {
        padding: 3px 10px;
        cursor: pointer;
        font-size: 12px;
        border-bottom: 1px solid #f1f3f5;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        user-select: none;
    }
    .filter-item:hover { background-color: #f8f9fa; }
    .filter-item.selected { background-color: #3498db !important; color: #fff !important; }
    .filter-item input[type="checkbox"] { display: none; }
    #partySearch, #accountSearch { height: 24px; font-size: 11px; }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function uncheckItem($item) {
            $item.find('input[type="checkbox"]').prop('checked', false);
            $item.removeClass('selected');
        }

        function getCheckedValues(listId, inputName) {
            const values = [];
            $('#' + listId + ' .filter-item:visible input[name="' + inputName + '"]:checked').each(function() {
                values.push(String($(this).val()));
            });
            return values;
        }

        $('.filter-item').on('click', function(e) {
            if ($(e.target).is('input')) return;
            const $cb = $(this).find('input[type="checkbox"]');
            $cb.prop('checked', !$cb.prop('checked'));
            $(this).toggleClass('selected', $cb.prop('checked'));
        });

        $('.select-all').on('change', function() {
            const target = $(this).data('target');
            const checked = $(this).is(':checked');
            $('#' + target + ' .filter-item:visible').each(function() {
                $(this).find('input[type="checkbox"]').prop('checked', checked);
                $(this).toggleClass('selected', checked);
            });
        });

        $('#globalSelectAll').on('change', function() {
            const checked = $(this).is(':checked');
            $('.select-all').prop('checked', checked).trigger('change');
        });

        $('#partySearch').on('keyup', function() {
            const term = $(this).val().toLowerCase();
            $('#party-list .filter-item').each(function() {
                const match = ($(this).data('search') || '').includes(term);
                $(this).toggle(match);
                if (!match) uncheckItem($(this));
            });
        });

        $('#accountSearch').on('keyup', function() {
            filterByMainHead();
        });

        function filterByMainHead() {
            const selectedMainHeads = getCheckedValues('main-head-list', 'main_head[]');
            const selectedSubHeads = getCheckedValues('sub-head-list', 'sub_head[]');

            $('#sub-head-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const visible = selectedMainHeads.length === 0 || selectedMainHeads.includes(headId);
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });

            const activeHeads = selectedSubHeads.length > 0 ? selectedSubHeads : selectedMainHeads;

            $('#account-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const searchTerm = ($('#accountSearch').val() || '').toLowerCase();
                const matchesHead = activeHeads.length === 0 || activeHeads.includes(headId);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesHead && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#main-head-list .filter-item').on('click', function() {
            setTimeout(filterByMainHead, 50);
        });

        $('#sub-head-list .filter-item').on('click', function() {
            setTimeout(filterByMainHead, 50);
        });

        function filterByGroup() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            if (selectedGroups.length === 0) {
                $('#officer-list .filter-item').show();
                return;
            }
            $('#officer-list .filter-item').each(function() {
                const groups = String($(this).data('groups') || '').split(',').filter(Boolean);
                const visible = groups.length === 0 || groups.some(g => selectedGroups.includes(g));
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#group-list .filter-item').on('click', function() {
            setTimeout(filterByGroup, 50);
        });
    });
</script>
@endsection
