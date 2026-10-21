@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Claim Report Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.claim.preview') }}" method="POST" id="reportForm">
                    @csrf

                    <div class="filter-grid-container">
                        <div class="row g-1 mb-3">
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="group-list"> Group
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="group-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="group-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="filter-list" id="group-list">
                                        @foreach($userGroups as $group)
                                            <div class="filter-item" title="Click to toggle, double-click to select only this">
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
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="officer-list"> User
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="officer-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="officer-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="filter-list" id="officer-list">
                                        @foreach($users as $u)
                                            <div class="filter-item" 
                                                 data-groups="{{ $u->userGroups->pluck('id')->implode(',') }}" 
                                                 data-is-admin="{{ $u->isAdmin() ? '1' : '0' }}"
                                                 title="Click to toggle, double-click to select only this">
                                                <input type="checkbox" name="sales_officer[]" value="{{ $u->id }}">
                                                <span>{{ $u->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="claimwh-list"> Claim WH
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="claimwh-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="claimwh-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="filter-list" id="claimwh-list">
                                        @foreach($claimWarehouses as $w)
                                            <div class="filter-item" data-groups="{{ is_array($w->user_group_ids) ? implode(',', $w->user_group_ids) : '' }}" title="Click to toggle, double-click to select only this">
                                                <input type="checkbox" name="claim_warehouse[]" value="{{ $w->id }}">
                                                <span>{{ $w->warehouse_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 110px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="claimtype-list"> Claim Type
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="claimtype-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="claimtype-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="filter-list" id="claimtype-list">
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="claim_type[]" value="item_return"><span>Item Return</span></div>
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="claim_type[]" value="credit_note"><span>Credit Note</span></div>
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="claim_type[]" value="claim_hold"><span>Claim Hold</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="item-list"> Item
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="item-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="item-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="itemSearch" placeholder="Search item..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="item-list">
                                        @foreach($products as $prod)
                                            <div class="filter-item" data-search="{{ strtolower($prod->name) }}" title="Click to toggle, double-click to select only this">
                                                <input type="checkbox" name="item[]" value="{{ $prod->id }}">
                                                <span>{{ $prod->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1" style="min-width: 90px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="partytype-list"> Type
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="partytype-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="partytype-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="party_type[]" value="customer"><span>Customer</span></div>
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="party_type[]" value="walkin"><span>Walk-in</span></div>
                                        <div class="filter-item" title="Click to toggle, double-click to select only this"><input type="checkbox" name="party_type[]" value="vendor"><span>Vendor</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2" style="min-width: 130px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="checkbox" class="select-all" data-target="party-list"> Party
                                        </div>
                                        <div class="filter-actions">
                                            <span class="badge bg-secondary counter-badge" data-target="party-list">0</span>
                                            <a href="javascript:void(0)" class="quick-none text-danger fw-bold ms-1" data-target="party-list" title="Uncheck all">None</a>
                                        </div>
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="partySearch" placeholder="Search party..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="party-list">
                                        @foreach($customers as $cust)
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($cust->customer_name) }}"
                                                 data-party-type="customer"
                                                 data-groups="{{ is_array($cust->user_group_ids) ? implode(',', $cust->user_group_ids) : '' }}"
                                                 title="Click to toggle, double-click to select only this">
                                                <input type="checkbox" name="party[]" value="customer:{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($vendor->name) }}"
                                                 data-party-type="vendor"
                                                 data-groups="{{ is_array($vendor->user_group_ids) ? implode(',', $vendor->user_group_ids) : '' }}"
                                                 title="Click to toggle, double-click to select only this">
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
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Claim Type</label>
                                    <select name="claim_entry_type" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="all" selected>All Types</option>
                                        <option value="item_return">Item Return</option>
                                        <option value="credit_note">Credit Note</option>
                                        <option value="claim_hold">Claim Hold</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Claim No.</label>
                                    <input type="text" name="claim_no" class="form-control form-control-sm" placeholder="CLM-..." style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                    <select name="report_type" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="Party Wise" selected>Party Wise</option>
                                        <option value="Claim Type Wise">Claim Type Wise</option>
                                        <option value="Brand Wise">Brand Wise</option>
                                        <option value="Item Wise">Item Wise</option>
                                        <option value="Claim Wise">Claim Wise</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">From Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">To Date</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 30px; font-size: 12px;">
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
        padding: 5px 8px;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        font-size: 11px;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        flex-shrink: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .filter-actions {
        font-size: 10px;
        font-weight: normal;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .counter-badge {
        font-size: 9px;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    .quick-none {
        text-decoration: none;
        font-size: 10px;
        cursor: pointer;
    }
    .quick-none:hover {
        text-decoration: underline;
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
    #itemSearch, #partySearch { height: 24px; font-size: 11px; }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function uncheckItem($item) {
            $item.find('input[type="checkbox"]').prop('checked', false);
            $item.removeClass('selected');
        }

        function checkItem($item) {
            $item.find('input[type="checkbox"]').prop('checked', true);
            $item.addClass('selected');
        }

        function updateCounters() {
            $('.filter-list').each(function() {
                const listId = $(this).attr('id');
                const total = $(this).find('.filter-item').length;
                const checked = $(this).find('.filter-item input[type="checkbox"]:checked').length;
                const $badge = $('.counter-badge[data-target="' + listId + '"]');
                const $selectAll = $('.select-all[data-target="' + listId + '"]');

                $badge.text(checked + '/' + total);
                if (checked === total && total > 0) {
                    $badge.removeClass('bg-secondary bg-primary').addClass('bg-success');
                    $selectAll.prop('checked', true).prop('indeterminate', false);
                } else if (checked === 0) {
                    $badge.removeClass('bg-success bg-primary').addClass('bg-secondary');
                    $selectAll.prop('checked', false).prop('indeterminate', false);
                } else {
                    $badge.removeClass('bg-secondary bg-success').addClass('bg-primary');
                    $selectAll.prop('checked', false).prop('indeterminate', true);
                }
            });
        }

        function getCheckedValues(listId, inputName) {
            const values = [];
            $('#' + listId + ' .filter-item:visible input[name="' + inputName + '"]:checked').each(function() {
                values.push(String($(this).val()));
            });
            return values;
        }

        function parseDataList(value) {
            return String(value || '').split(',').map(v => v.trim()).filter(Boolean);
        }

        function matchesGroups($item, selectedGroups) {
            // Super Admin / Admin users always have access across all groups and should never be hidden
            if ($item.data('is-admin') == '1' || $item.data('is-admin') === 1) return true;

            const totalGroups = $('#group-list .filter-item').length;
            // If all groups are selected or none selected, keep everything visible
            if (!selectedGroups.length || selectedGroups.length === totalGroups) return true;

            const itemGroups = parseDataList($item.data('groups'));
            // If an entity has no specific group restrictions, keep it visible
            if (!itemGroups.length) return true;

            return selectedGroups.some(g => itemGroups.includes(g));
        }

        function applyCascadeFilters() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            const selectedPartyTypes = getCheckedValues('partytype-list', 'party_type[]');
            const itemSearch = ($('#itemSearch').val() || '').toLowerCase().trim();
            const partySearch = ($('#partySearch').val() || '').toLowerCase().trim();

            $('#officer-list .filter-item, #claimwh-list .filter-item').each(function() {
                const $item = $(this);
                const show = matchesGroups($item, selectedGroups);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#party-list .filter-item').each(function() {
                const $item = $(this);
                const partyType = String($item.data('party-type') || '');
                const searchText = String($item.data('search') || '');
                
                const groupMatch = matchesGroups($item, selectedGroups);
                const typeMatch = !selectedPartyTypes.length || selectedPartyTypes.includes(partyType);
                const searchMatch = !partySearch || searchText.indexOf(partySearch) > -1;
                
                const show = groupMatch && typeMatch && searchMatch;

                $item.toggle(show);
                if (!groupMatch || !typeMatch) uncheckItem($item);
            });

            $('#item-list .filter-item').each(function() {
                const $item = $(this);
                const searchText = String($item.data('search') || '');
                const show = !itemSearch || searchText.indexOf(itemSearch) > -1;
                $item.toggle(show);
            });

            updateCounters();
        }

        // Click on item to toggle
        $(document).on('click', '.filter-item', function(e) {
            if ($(e.target).is('input[type="checkbox"]')) return;
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            $(this).toggleClass('selected', checkbox.prop('checked'));
            updateCounters();
            const listId = $(this).closest('.filter-list').attr('id');
            if (['group-list', 'partytype-list'].includes(listId)) applyCascadeFilters();
        });

        $(document).on('change', '.filter-item input[type="checkbox"]', function() {
            const $item = $(this).closest('.filter-item');
            $item.toggleClass('selected', $(this).prop('checked'));
            updateCounters();
            const listId = $item.closest('.filter-list').attr('id');
            if (['group-list', 'partytype-list'].includes(listId)) {
                applyCascadeFilters();
            }
        });

        // Double-click to select ONLY this item
        $(document).on('dblclick', '.filter-item', function(e) {
            e.preventDefault();
            const $list = $(this).closest('.filter-list');
            $list.find('.filter-item').each(function() {
                uncheckItem($(this));
            });
            checkItem($(this));
            updateCounters();
            const listId = $list.attr('id');
            if (['group-list', 'partytype-list'].includes(listId)) applyCascadeFilters();
        });

        // Quick None button
        $(document).on('click', '.quick-none', function(e) {
            e.preventDefault();
            const targetId = $(this).data('target');
            $('#' + targetId).find('.filter-item').each(function() {
                uncheckItem($(this));
            });
            updateCounters();
            if (['group-list', 'partytype-list'].includes(targetId)) applyCascadeFilters();
        });

        // Select All column checkbox
        $('.select-all').on('change', function() {
            const targetId = $(this).data('target');
            const isChecked = $(this).prop('checked');
            const $list = $('#' + targetId);

            if (!isChecked) {
                // When unchecking, ALWAYS uncheck ALL items in this column
                $list.find('.filter-item').each(function() {
                    uncheckItem($(this));
                });
            } else {
                // When checking, check visible items
                $list.find('.filter-item').each(function() {
                    if ($(this).css('display') !== 'none') {
                        checkItem($(this));
                    }
                });
            }
            updateCounters();
            if (['group-list', 'partytype-list'].includes(targetId)) applyCascadeFilters();
        });

        // Global Select All
        $('#globalSelectAll').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.select-all').prop('checked', isChecked);
            $('.filter-item').each(function() {
                const cb = $(this).find('input[type="checkbox"]');
                cb.prop('checked', isChecked);
                $(this).toggleClass('selected', isChecked);
            });
            applyCascadeFilters();
            updateCounters();
        });

        // Search filtering for items with auto-deselect of non-matching items
        let itemFilterWasAllChecked = false;

        $('#itemSearch').on('focus', function() {
            const total = $('#item-list .filter-item').length;
            const checked = $('#item-list .filter-item input:checked').length;
            itemFilterWasAllChecked = (checked === total);
        });

        $('#itemSearch').on('keyup input search', function() {
            const term = ($(this).val() || '').toLowerCase().trim();
            const total = $('#item-list .filter-item').length;
            const checked = $('#item-list .filter-item input:checked').length;

            if (term && (itemFilterWasAllChecked || checked === total)) {
                itemFilterWasAllChecked = true;
                $('#item-list .filter-item').each(function() {
                    const search = String($(this).data('search') || '');
                    const match = search.indexOf(term) > -1;
                    $(this).toggle(match);
                    if (!match) {
                        uncheckItem($(this));
                    } else {
                        checkItem($(this));
                    }
                });
            } else {
                $('#item-list .filter-item').each(function() {
                    const search = String($(this).data('search') || '');
                    const match = !term || search.indexOf(term) > -1;
                    $(this).toggle(match);
                });
                if (!term) {
                    itemFilterWasAllChecked = false;
                }
            }
            updateCounters();
        });

        // Search filtering for party
        $('#partySearch').on('keyup input search', function() {
            applyCascadeFilters();
        });

        // Form submit: if itemSearch or partySearch has query, disable non-matching hidden items so they never get posted
        $('#reportForm').on('submit', function(e) {
            const itemTerm = ($('#itemSearch').val() || '').toLowerCase().trim();
            if (itemTerm) {
                $('#item-list .filter-item').each(function() {
                    if ($(this).css('display') === 'none') {
                        $(this).find('input[type="checkbox"]').prop('disabled', true);
                    }
                });
            }

            const partyTerm = ($('#partySearch').val() || '').toLowerCase().trim();
            if (partyTerm) {
                $('#party-list .filter-item').each(function() {
                    if ($(this).css('display') === 'none') {
                        $(this).find('input[type="checkbox"]').prop('disabled', true);
                    }
                });
            }

            setTimeout(function() {
                $('#item-list input:disabled, #party-list input:disabled').prop('disabled', false);
            }, 500);
        });

        // Auto-select all by default on load
        setTimeout(() => {
            $('#globalSelectAll').prop('checked', true).trigger('change');
            updateCounters();
        }, 300);
    });
</script>
@endsection
