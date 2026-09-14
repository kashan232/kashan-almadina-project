@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Stock Hold &amp; Release Summary — Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.stock-hold-release.preview') }}" method="POST" id="reportForm">
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
                                        <input type="checkbox" class="select-all" data-target="warehouse-list"> Warehouse
                                    </div>
                                    <div class="filter-list" id="warehouse-list">
                                        <div class="filter-item" data-groups="{{ $shopGroupIds }}">
                                            <input type="checkbox" name="warehouse[]" value="0">
                                            <span>Shop Stock</span>
                                        </div>
                                        @foreach($warehouses as $w)
                                            <div class="filter-item" data-groups="{{ is_array($w->user_group_ids) ? implode(',', $w->user_group_ids) : '' }}">
                                                <input type="checkbox" name="warehouse[]" value="{{ $w->id }}">
                                                <span>{{ $w->warehouse_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Party Type -->
                            <div class="col-md-1" style="min-width: 90px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="partytype-list"> Type
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Main Customer"><span>Main</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Walking Customer"><span>Walk-in</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Vendor"><span>Vendor</span></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Party -->
                            <div class="col-md-2" style="min-width: 150px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="party-list"> Party
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="partySearch" placeholder="Search party..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="party-list">
                                        @foreach($customers as $customer)
                                            @php
                                                $partyTypeKey = $customer->customer_type === 'Walking Customer' ? 'walkin' : 'customer';
                                            @endphp
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($customer->customer_name) }}"
                                                 data-party-type="{{ $customer->customer_type }}"
                                                 data-groups="{{ is_array($customer->user_group_ids) ? implode(',', $customer->user_group_ids) : '' }}">
                                                <input type="checkbox" name="party[]" value="{{ $partyTypeKey }}:{{ $customer->id }}">
                                                <span>{{ $customer->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($vendor->name) }}"
                                                 data-party-type="Vendor"
                                                 data-groups="{{ is_array($vendor->user_group_ids) ? implode(',', $vendor->user_group_ids) : '' }}">
                                                <input type="checkbox" name="party[]" value="vendor:{{ $vendor->id }}">
                                                <span>{{ $vendor->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Item -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="item-list"> Item
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="itemSearch" placeholder="Search item..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="item-list">
                                        @foreach($products as $prod)
                                            <div class="filter-item" data-search="{{ strtolower($prod->name) }}">
                                                <input type="checkbox" name="item[]" value="{{ $prod->id }}">
                                                <span>{{ $prod->name }}</span>
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
                                    <select name="report_type" id="reportType" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="party" selected>Customer / Party Wise (Main: Party, Sub: Item)</option>
                                        <option value="item">Hold and Summary Report</option>
                                        <option value="detailed">Detailed Stock Hold and Release Statement</option>
                                        <option value="hold_only">Stock Hold Report</option>
                                        <option value="release_only">Stock Release Report</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Start Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-01-01') }}" required style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">End Date</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required style="height: 30px; font-size: 12px;">
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
            filterByPartyType();
        });

        $('#itemSearch').on('keyup', function() {
            const term = ($(this).val() || '').toLowerCase();
            $('#item-list .filter-item').each(function() {
                const $item = $(this);
                const show = !term || String($item.data('search') || '').includes(term);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });
        });

        function filterByPartyType() {
            const selectedTypes = getCheckedValues('partytype-list', 'party_type[]');
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

        $('#partytype-list .filter-item').on('click', function() {
            setTimeout(filterByPartyType, 50);
        });

        function filterByGroup() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            if (selectedGroups.length === 0) {
                $('#warehouse-list .filter-item, #party-list .filter-item').show();
                return;
            }
            ['warehouse-list', 'party-list'].forEach(function(listId) {
                $('#' + listId + ' .filter-item').each(function() {
                    const groups = String($(this).data('groups') || '').split(',').filter(Boolean);
                    const visible = groups.length === 0 || groups.some(g => selectedGroups.includes(g));
                    $(this).toggle(visible);
                    if (!visible) uncheckItem($(this));
                });
            });
        }

        $('#group-list .filter-item').on('click', function() {
            setTimeout(filterByGroup, 50);
        });
    });
</script>
@endsection
