@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Daily Activity Report Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.daily-activity.preview') }}" method="POST" id="reportForm" target="_blank">
                    @csrf

                    <div class="filter-grid-container">
                        <div class="row g-2 mb-3">
                            <div class="col-md-2" style="min-width: 140px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="group-list"> User Group
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

                            <div class="col-md-2" style="min-width: 130px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="partytype-list"> Account Category
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_types[]" value="customer">
                                            <span>Customers</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_types[]" value="walkin">
                                            <span>Walking</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_types[]" value="vendor">
                                            <span>Vendors</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_types[]" value="bank">
                                            <span>Banks & Cash</span>
                                        </div>
                                        <div class="filter-item">
                                            <input type="checkbox" name="party_types[]" value="expense">
                                            <span>Expenses</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="account-list"> Specific Accounts / Parties
                                    </div>
                                    <div class="p-1 bg-light border-bottom">
                                        <input type="text" class="form-control form-control-sm" id="accountSearch" placeholder="Search account or party..." style="height: 24px; font-size: 11px;">
                                    </div>
                                    <div class="filter-list" id="account-list">
                                        @foreach($customers as $customer)
                                            @php
                                                $partyTypeKey = $customer->customer_type === 'Walking Customer' ? 'walkin' : 'customer';
                                                $partyTypeLabel = $partyTypeKey === 'walkin' ? 'Walking' : 'Customer';
                                            @endphp
                                            <div class="filter-item" data-search="{{ strtolower($customer->customer_name . ' ' . $partyTypeLabel) }}">
                                                <input type="checkbox" name="accounts[]" value="{{ $partyTypeKey }}:{{ $customer->id }}">
                                                <span>{{ $customer->customer_name }} <small class="text-muted">({{ $partyTypeLabel }})</small></span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item" data-search="{{ strtolower($vendor->name . ' vendor') }}">
                                                <input type="checkbox" name="accounts[]" value="vendor:{{ $vendor->id }}">
                                                <span>{{ $vendor->name }} <small class="text-muted">(Vendor)</small></span>
                                            </div>
                                        @endforeach
                                        @foreach($accounts as $acc)
                                            <div class="filter-item" data-search="{{ strtolower(($acc->account_code ? $acc->account_code . ' ' : '') . $acc->title) }}">
                                                <input type="checkbox" name="accounts[]" value="account:{{ $acc->id }}">
                                                <span>{{ $acc->account_code ? $acc->account_code . ' - ' : '' }}{{ $acc->title }} <small class="text-muted">({{ $acc->accountHead->name ?? 'Account' }})</small></span>
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
                                    <label class="form-label small fw-bold mb-1">From Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">To Date</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3 ms-auto text-end">
                                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                                        <i class="fas fa-eye me-1"></i> PREVIEW REPORT
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .filter-grid-container { background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px solid #de2e6; }
    .filter-column { border: 1px solid #ced4da; border-radius: 4px; background: #fff; height: 260px; display: flex; flex-direction: column; }
    .filter-header { background: #e9ecef; padding: 4px 8px; font-weight: bold; font-size: 11px; border-bottom: 1px solid #ced4da; }
    .filter-list { overflow-y: auto; flex: 1; padding: 4px; }
    .filter-item { font-size: 11px; padding: 2px 4px; display: flex; align-items: center; gap: 6px; }
    .filter-item:hover { background: #f1f3f5; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Account search filter
    const searchInput = document.getElementById('accountSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#account-list .filter-item').forEach(item => {
                const searchStr = item.getAttribute('data-search') || '';
                item.style.display = searchStr.includes(query) ? 'flex' : 'none';
            });
        });
    }

    // Select all checkboxes handlers
    document.querySelectorAll('.select-all').forEach(cb => {
        cb.addEventListener('change', function() {
            const targetId = this.getAttribute('data-target');
            document.querySelectorAll(`#${targetId} input[type="checkbox"]`).forEach(c => {
                if (c.parentElement.style.display !== 'none') {
                    c.checked = this.checked;
                }
            });
        });
    });

    const globalCb = document.getElementById('globalSelectAll');
    if (globalCb) {
        globalCb.addEventListener('change', function() {
            document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = this.checked);
        });
    }
});
</script>
@endsection
