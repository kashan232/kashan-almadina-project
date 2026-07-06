@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Outstanding Balance Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.customer-outstanding.preview') }}" method="POST" id="reportForm">
                    @csrf

                    <div class="filter-grid-container">
                        <div class="row g-1 mb-3">
                            <div class="col-md-2" style="min-width: 120px;">
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
                            <div class="col-md-2" style="min-width: 120px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="partytype-list"> Type
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="customer">
                                            <span>Customer</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="walkin">
                                            <span>Walking</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_type[]" value="vendor">
                                            <span>Vendor</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
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
                                                $partyTypeLabel = $partyTypeKey === 'walkin' ? 'Walking' : 'Customer';
                                            @endphp
                                            <div class="filter-item"
                                                 data-party-type="{{ $partyTypeKey }}"
                                                 data-search="{{ strtolower($customer->customer_name . ' ' . $partyTypeLabel) }}">
                                                <input type="checkbox" name="party[]" value="{{ $partyTypeKey }}:{{ $customer->id }}">
                                                <span>{{ $customer->customer_name }} <small class="text-muted">({{ $partyTypeLabel }})</small></span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item"
                                                 data-party-type="vendor"
                                                 data-search="{{ strtolower($vendor->name . ' vendor') }}">
                                                <input type="checkbox" name="party[]" value="vendor:{{ $vendor->id }}">
                                                <span>{{ $vendor->name }} <small class="text-muted">(Vendor)</small></span>
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
                                        <option value="short" selected>Short View</option>
                                        <option value="detailed">Detailed View</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Start Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" required style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-3">
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
        height: 320px !important;
    }
    .filter-header {
        background: #f1f3f5;
        padding: 6px 10px;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        font-size: 12px;
        color: #2c3e50;
        text-transform: uppercase;
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

        $('.filter-item').on('click', function(e) {
            if ($(e.target).is('input')) return;
            const $cb = $(this).find('input[type="checkbox"]');
            $cb.prop('checked', !$cb.prop('checked'));
            $(this).toggleClass('selected', $cb.prop('checked'));

            if ($(this).closest('#partytype-list').length) {
                setTimeout(applyPartyFilters, 0);
            }
        });

        $('.select-all').on('change', function() {
            const target = $(this).data('target');
            const checked = $(this).is(':checked');
            $('#' + target + ' .filter-item:visible').each(function() {
                $(this).find('input[type="checkbox"]').prop('checked', checked);
                $(this).toggleClass('selected', checked);
            });
            if (target === 'partytype-list') {
                applyPartyFilters();
            }
        });

        $('#globalSelectAll').on('change', function() {
            $('.select-all').prop('checked', $(this).is(':checked')).trigger('change');
        });

        function getCheckedValues(listId, inputName) {
            const values = [];
            $('#' + listId + ' .filter-item input[name="' + inputName + '"]:checked').each(function() {
                if ($(this).closest('.filter-item').is(':visible')) {
                    values.push($(this).val());
                }
            });
            return values;
        }

        function applyPartyFilters() {
            const selectedTypes = getCheckedValues('partytype-list', 'party_type[]');
            const term = ($('#partySearch').val() || '').toLowerCase();

            $('#party-list .filter-item').each(function() {
                const $item = $(this);
                const partyType = String($item.data('party-type') || '');
                const searchText = String($item.data('search') || '');
                const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(partyType);
                const searchMatch = !term || searchText.includes(term);
                const show = typeMatch && searchMatch;

                $item.toggle(show);
                if (!show) uncheckItem($item);
            });
        }

        $('#partySearch').on('keyup', applyPartyFilters);
        applyPartyFilters();
    });
</script>
@endsection
