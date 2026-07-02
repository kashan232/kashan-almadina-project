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
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="claimwh-list"> Claim WH
                                    </div>
                                    <div class="filter-list" id="claimwh-list">
                                        @foreach($claimWarehouses as $w)
                                            <div class="filter-item" data-groups="{{ is_array($w->user_group_ids) ? implode(',', $w->user_group_ids) : '' }}">
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
                                        <input type="checkbox" class="select-all" data-target="claimtype-list"> Claim Type
                                    </div>
                                    <div class="filter-list" id="claimtype-list">
                                        <div class="filter-item"><input type="checkbox" name="claim_type[]" value="item_return"><span>Item Return</span></div>
                                        <div class="filter-item"><input type="checkbox" name="claim_type[]" value="credit_note"><span>Credit Note</span></div>
                                        <div class="filter-item"><input type="checkbox" name="claim_type[]" value="claim_hold"><span>Claim Hold</span></div>
                                    </div>
                                </div>
                            </div>
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
                            <div class="col-md-1" style="min-width: 90px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="partytype-list"> Type
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="customer"><span>Customer</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="walkin"><span>Walk-in</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="vendor"><span>Vendor</span></div>
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
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($cust->customer_name) }}"
                                                 data-party-type="customer">
                                                <input type="checkbox" name="party[]" value="customer:{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($vendor->name) }}"
                                                 data-party-type="vendor">
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
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-01-01') }}" style="height: 30px; font-size: 12px;">
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
            if (!selectedGroups.length) return true;
            const itemGroups = parseDataList($item.data('groups'));
            if (!itemGroups.length) return true;
            return selectedGroups.some(g => itemGroups.includes(g));
        }

        function applyCascadeFilters() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            const selectedPartyTypes = getCheckedValues('partytype-list', 'party_type[]');
            const itemSearch = ($('#itemSearch').val() || '').toLowerCase();
            const partySearch = ($('#partySearch').val() || '').toLowerCase();

            $('#officer-list .filter-item, #claimwh-list .filter-item').each(function() {
                const $item = $(this);
                const show = matchesGroups($item, selectedGroups);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#item-list .filter-item').each(function() {
                const $item = $(this);
                const searchText = String($item.data('search') || '');
                const show = !itemSearch || searchText.indexOf(itemSearch) > -1;
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#party-list .filter-item').each(function() {
                const $item = $(this);
                const partyType = String($item.data('party-type') || '');
                const searchText = String($item.data('search') || '');
                const typeMatch = !selectedPartyTypes.length || selectedPartyTypes.includes(partyType);
                const searchMatch = !partySearch || searchText.indexOf(partySearch) > -1;
                const show = typeMatch && searchMatch;
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });
        }

        $(document).on('click', '.filter-item', function(e) {
            if ($(e.target).is('input[type="checkbox"]')) return;
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            $(this).toggleClass('selected', checkbox.prop('checked'));
            const listId = $(this).closest('.filter-list').attr('id');
            if (['group-list', 'partytype-list'].includes(listId)) applyCascadeFilters();
        });

        $('.select-all').on('change', function() {
            const targetId = $(this).data('target');
            const isChecked = $(this).prop('checked');
            $('#' + targetId).find('.filter-item').each(function() {
                if ($(this).css('display') !== 'none') {
                    const cb = $(this).find('input[type="checkbox"]');
                    cb.prop('checked', isChecked);
                    $(this).toggleClass('selected', isChecked);
                }
            });
            if (['group-list', 'partytype-list'].includes(targetId)) applyCascadeFilters();
        });

        $('#globalSelectAll').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.select-all').prop('checked', isChecked);
            $('.filter-item').each(function() {
                const cb = $(this).find('input[type="checkbox"]');
                cb.prop('checked', isChecked);
                $(this).toggleClass('selected', isChecked);
            });
            applyCascadeFilters();
        });

        $('#itemSearch, #partySearch').on('keyup', applyCascadeFilters);
        setTimeout(() => { $('#globalSelectAll').prop('checked', true).trigger('change'); }, 300);
    });
</script>
@endsection
