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
                                        <input type="checkbox" class="select-all" data-target="party-type-list"> Party Type
                                    </div>
                                    <div class="filter-list" id="party-type-list">
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="vendor">
                                            <span>Vendor</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="customer">
                                            <span>Customer</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="walkin">
                                            <span>Walking Customer</span>
                                        </div>
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
                                            @php
                                                $partyTypeKey = $cust->customer_type === 'Walking Customer' ? 'walkin' : 'customer';
                                            @endphp
                                            <div class="filter-item" data-party-type="{{ $partyTypeKey }}" data-search="{{ strtolower($cust->customer_name) }}">
                                                <input type="checkbox" name="party[]" value="{{ $partyTypeKey }}:{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item" data-party-type="vendor" data-search="{{ strtolower($vendor->name) }}">
                                                <input type="checkbox" name="party[]" value="vendor:{{ $vendor->id }}">
                                                <span>{{ $vendor->name }}</span>
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
                        </div>
                    </div>

                    <div class="card border border-primary mb-3 shadow-sm report-settings-card">
                        <div class="card-header py-2 bg-primary text-white fw-bold small">
                            <i class="fas fa-cog me-1"></i> REPORT SETTINGS
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <label class="report-field-label">Report Type</label>
                                    <select name="report_type" class="form-select form-select-sm report-field-input" required>
                                        <option value="source_party" selected>Source Party Name</option>
                                        <option value="main_head">Main Head</option>
                                    </select>
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">Vouc. ID</label>
                                    <input type="text" name="voucher_id" class="form-control form-control-sm report-field-input" placeholder="Search voucher no...">
                                </div>
                                <div class="col-12 mt-1">
                                    <span class="report-date-group-label">Receipt Date</span>
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">From</label>
                                    <input type="date" name="receipt_from" class="form-control form-control-sm report-field-input">
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">To</label>
                                    <input type="date" name="receipt_to" class="form-control form-control-sm report-field-input">
                                </div>
                                <div class="col-12 mt-1">
                                    <span class="report-date-group-label">Entry Date</span>
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">From</label>
                                    <input type="date" name="entry_from" class="form-control form-control-sm report-field-input">
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">To</label>
                                    <input type="date" name="entry_to" class="form-control form-control-sm report-field-input">
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

    .report-settings-card .card-body { background: #fafbfc; }
    .report-field-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #37474f;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .report-field-input {
        height: 34px !important;
        min-height: 34px !important;
        font-size: 12px !important;
        border-radius: 4px;
        border: 1px solid #ced4da;
        background: #fff;
    }
    .report-field-input:focus {
        border-color: #0d47a1;
        box-shadow: 0 0 0 0.15rem rgba(13, 71, 161, 0.15);
    }
    .report-date-group-label {
        display: inline-block;
        font-size: 10px;
        font-weight: 800;
        color: #0d47a1;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 2px 8px;
        background: #e3f2fd;
        border-radius: 3px;
        border-left: 3px solid #0d47a1;
    }
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

            if ($(this).closest('#account-list').length && $cb.prop('checked')) {
                autoSelectMainHeadForAccount($(this));
            }
        });

        function autoSelectMainHeadForAccount($accountItem) {
            const headId = String($accountItem.data('head-id') || '');
            if (!headId) return;
            $('#main-head-list .filter-item').each(function() {
                const $headCb = $(this).find('input[name="main_head[]"]');
                if (String($headCb.val()) === headId) {
                    $headCb.prop('checked', true);
                    $(this).addClass('selected');
                }
            });
        }

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
            filterByPartyType();
        });

        function filterByPartyType() {
            const selectedTypes = getCheckedValues('party-type-list', 'party_type[]');
            const searchTerm = ($('#partySearch').val() || '').toLowerCase();

            $('#party-list .filter-item').each(function() {
                const partyType = String($(this).data('party-type') || '');
                const matchesType = selectedTypes.length === 0 || selectedTypes.includes(partyType);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesType && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#party-type-list .filter-item').on('click', function() {
            setTimeout(filterByPartyType, 50);
        });

        $('#accountSearch').on('keyup', function() {
            filterByMainHead();
        });

        function filterByMainHead() {
            const selectedMainHeads = getCheckedValues('main-head-list', 'main_head[]');
            const searchTerm = ($('#accountSearch').val() || '').toLowerCase();

            $('#account-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const matchesHead = selectedMainHeads.length === 0 || selectedMainHeads.includes(headId);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesHead && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#main-head-list .filter-item').on('click', function() {
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
