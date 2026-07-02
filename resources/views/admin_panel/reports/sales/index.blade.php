@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">Sales Report Filters</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.sales.preview') }}" method="POST" id="reportForm">
                    @csrf
                    
                    <div class="filter-grid-container">
                        <div class="row g-1 mb-3">
                            <!-- User Group -->
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
                            <!-- User (Sales Officer) -->
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
                            <!-- Warehouse -->
                            <div class="col-md-1">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="warehouse-list"> WH
                                    </div>
                                    <div class="filter-list" id="warehouse-list">
                                        <div class="filter-item" data-groups="{{ $shopGroupIds }}">
                                            <input type="checkbox" name="warehouse[]" value="0">
                                            <span>Shop</span>
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
                            <!-- Category -->
                            <div class="col-md-1">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="category-list"> Cat
                                    </div>
                                    <div class="filter-list" id="category-list">
                                        @foreach($categories as $cat)
                                            <div class="filter-item">
                                                <input type="checkbox" name="category[]" value="{{ $cat->id }}">
                                                <span>{{ $cat->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Sub-Category -->
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="subcat-list"> Sub-Cat
                                    </div>
                                    <div class="filter-list" id="subcat-list">
                                        @foreach($subcategories as $sub)
                                            <div class="filter-item" data-category="{{ $sub->category_id }}">
                                                <input type="checkbox" name="subcategory[]" value="{{ $sub->id }}">
                                                <span>{{ $sub->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Brand -->
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="brand-list"> Brand
                                    </div>
                                    <div class="filter-list" id="brand-list">
                                        @foreach($brands as $brand)
                                            <div class="filter-item">
                                                <input type="checkbox" name="brand[]" value="{{ $brand->id }}">
                                                <span>{{ $brand->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Items -->
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
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($prod->name) }}"
                                                 data-brand="{{ $prod->brand_id }}"
                                                 data-category="{{ $prod->category_id }}"
                                                 data-subcategory="{{ $prod->sub_category_id }}">
                                                <input type="checkbox" name="item[]" value="{{ $prod->id }}">
                                                <span>{{ $prod->name }}</span>
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
                            <div class="col-md-1" style="min-width: 130px;">
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
                                                 data-party-type="{{ $cust->customer_type }}">
                                                <input type="checkbox" name="party[]" value="{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($vendors as $vendor)
                                            <div class="filter-item"
                                                 data-search="{{ strtolower($vendor->name) }}"
                                                 data-party-type="Vendor">
                                                <input type="checkbox" name="party[]" value="{{ $vendor->id }}">
                                                <span>{{ $vendor->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Settings (Moved to Bottom) -->
                    <div class="card border border-primary mb-3 shadow-sm">
                        <div class="card-header py-1 bg-primary text-white fw-bold small">
                            <i class="fas fa-cog me-1"></i> REPORT SETTINGS
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Transaction Type</label>
                                    <select name="transaction_type" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="sale" selected>Sale</option>
                                        <option value="sale_return">Sale Return</option>
                                        <option value="both">Both (Return Minus)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Invoice No.</label>
                                    <input type="text" name="invoice_no" class="form-control form-control-sm" placeholder="Invoice No" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                    <select name="report_type" id="report_type_select" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="Invoice Wise">Invoice Wise</option>
                                        <option value="Item Wise">Item Wise</option>
                                        <option value="Party Wise">Party Wise</option>
                                        <option value="Qty Wise">Qty Wise</option>
                                        <option value="Claim Ratio">Claim Ratio</option>
                                        <option value="Tax Summary">Tax Summary</option>
                                        <option value="Sale vs List">Sale vs List</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">From Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" style="height: 30px; font-size: 12px;">
                                </div>
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">To Date</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 30px; font-size: 12px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
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
        height: 280px !important; /* Force same height for all */
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
    
    .filter-list {
        flex-grow: 1;
        overflow-y: auto;
        padding: 0;
    }
    
    .item-list-wide {
        height: 250px;
    }
    
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
    
    .filter-item:last-child {
        border-bottom: none;
    }
    
    .filter-item:hover {
        background-color: #f8f9fa;
    }
    
    .filter-item.selected {
        background-color: #3498db !important;
        color: #fff !important;
    }
    
    .filter-item input[type="checkbox"] {
        display: none;
    }
    
    /* Scrollbar Styling */
    .filter-list::-webkit-scrollbar {
        width: 6px;
    }
    .filter-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .filter-list::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }
    .filter-list::-webkit-scrollbar-thumb:hover {
        background: #bbb;
    }

    .btn-erp {
        background-color: #dee2e6;
        border: 1px solid #adb5bd;
        color: #212529;
        font-weight: 600;
        font-size: 13px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .btn-erp:hover {
        background-color: #ced4da;
        border-color: #6c757d;
    }

    #itemSearch, #partySearch {
        height: 24px;
        font-size: 11px;
    }
</style>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {

        function uncheckItem($item) {
            const cb = $item.find('input[type="checkbox"]');
            cb.prop('checked', false);
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
            const selectedCategories = getCheckedValues('category-list', 'category[]');
            const selectedBrands = getCheckedValues('brand-list', 'brand[]');
            const selectedPartyTypes = getCheckedValues('partytype-list', 'party_type[]');
            const itemSearch = ($('#itemSearch').val() || '').toLowerCase();
            const partySearch = ($('#partySearch').val() || '').toLowerCase();

            $('#officer-list .filter-item, #warehouse-list .filter-item').each(function() {
                const $item = $(this);
                const show = matchesGroups($item, selectedGroups);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#subcat-list .filter-item').each(function() {
                const $item = $(this);
                const catId = String($item.data('category'));
                const show = !selectedCategories.length || selectedCategories.includes(catId);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#item-list .filter-item').each(function() {
                const $item = $(this);
                const brandId = String($item.data('brand'));
                const searchText = String($item.data('search') || '');
                const brandMatch = !selectedBrands.length || selectedBrands.includes(brandId);
                const searchMatch = !itemSearch || searchText.indexOf(itemSearch) > -1;
                const show = brandMatch && searchMatch;
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

        // Toggle item selection
        $(document).on('click', '.filter-item', function(e) {
            if ($(e.target).is('input[type="checkbox"]')) return;

            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            $(this).toggleClass('selected', checkbox.prop('checked'));

            const listId = $(this).closest('.filter-list').attr('id');
            if (['group-list', 'category-list', 'brand-list', 'partytype-list'].includes(listId)) {
                applyCascadeFilters();
            }
        });

        // Select All functionality
        $('.select-all').on('change', function() {
            const targetId = $(this).data('target');
            const isChecked = $(this).prop('checked');
            const $list = $('#' + targetId);

            $list.find('.filter-item').each(function() {
                if ($(this).css('display') !== 'none') {
                    const cb = $(this).find('input[type="checkbox"]');
                    cb.prop('checked', isChecked);
                    $(this).toggleClass('selected', isChecked);
                }
            });

            if (['group-list', 'category-list', 'brand-list', 'partytype-list'].includes(targetId)) {
                applyCascadeFilters();
            }
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
        });

        // Search functionality for Item
        $('#itemSearch').on('keyup', function() {
            applyCascadeFilters();
        });

        // Search functionality for Party
        $('#partySearch').on('keyup', function() {
            applyCascadeFilters();
        });

        // AUTO-SELECT ALL FILTERS BY DEFAULT
        setTimeout(() => {
            $('#globalSelectAll').prop('checked', true).trigger('change');
        }, 300);
    });
</script>
@endsection
