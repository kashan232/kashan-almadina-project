@php
    $filterTitle = $filterTitle ?? 'Voucher Report Filters';
    $previewRoute = $previewRoute ?? '#';
    $showReceiptDates = $showReceiptDates ?? false;
    $reportTypeOptions = $reportTypeOptions ?? [
        'source_party' => 'Source Party Name',
        'sub_head' => 'Sub Head',
    ];
    $defaultReportType = $defaultReportType ?? array_key_first($reportTypeOptions);
@endphp

<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">{{ $filterTitle }}</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route($previewRoute) }}" method="POST" id="reportForm">
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

                    <div class="card border border-primary mb-3 shadow-sm report-settings-card">
                        <div class="card-header py-2 bg-primary text-white fw-bold small">
                            <i class="fas fa-cog me-1"></i> REPORT SETTINGS
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                @if(count($reportTypeOptions) > 1)
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <label class="report-field-label">Report Type</label>
                                    <select name="report_type" class="form-select form-select-sm report-field-input" required>
                                        @foreach($reportTypeOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($value === $defaultReportType)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @else
                                    <input type="hidden" name="report_type" value="{{ $defaultReportType }}">
                                @endif

                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">Vouc. ID</label>
                                    <input type="text" name="voucher_id" class="form-control form-control-sm report-field-input" placeholder="Search voucher no...">
                                </div>

                                @if($showReceiptDates)
                                <div class="col-12 mt-1">
                                    <span class="report-date-group-label">Receipt Date</span>
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">From</label>
                                    <input type="date" name="receipt_from" class="form-control form-control-sm report-field-input" value="{{ date('Y-01-01') }}">
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">To</label>
                                    <input type="date" name="receipt_to" class="form-control form-control-sm report-field-input" value="{{ date('Y-m-d') }}">
                                </div>
                                @endif

                                <div class="col-12 mt-1">
                                    <span class="report-date-group-label">Entry Date</span>
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">From</label>
                                    <input type="date" name="entry_from" class="form-control form-control-sm report-field-input" value="{{ date('Y-01-01') }}">
                                </div>
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <label class="report-field-label">To</label>
                                    <input type="date" name="entry_to" class="form-control form-control-sm report-field-input" value="{{ date('Y-m-d') }}">
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
