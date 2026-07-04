@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold" style="color: #0d47a1;">{{ $pageTitle ?? 'Stock Report Filters' }}</h4>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="globalSelectAll">
                    <label class="form-check-label fw-bold text-danger" for="globalSelectAll" style="cursor:pointer;">
                        SELECT ALL FILTERS
                    </label>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ $previewRoute ?? route('reports.stock.preview') }}" method="POST" id="reportForm">
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
                            <div class="col-md-1">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="category-list"> Category
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
                            <div class="col-md-1" style="min-width: 100px;">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="subcat-list"> Sub Category
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
                        </div>
                    </div>

                    <div class="card border border-primary mb-3 shadow-sm">
                        <div class="card-header py-1 bg-primary text-white fw-bold small">
                            <i class="fas fa-cog me-1"></i> REPORT SETTINGS
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2 align-items-end">
                                @if(!empty($fixedReportType))
                                    @if($fixedReportType === 'ledger')
                                    <input type="hidden" name="report_type" value="ledger">
                                    <div class="col-md-3">
                                        <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                        <div class="form-control form-control-sm bg-light fw-bold" style="height: 30px; font-size: 12px; line-height: 18px;">
                                            Item Stock Ledger
                                        </div>
                                    </div>
                                    @else
                                    <div class="col-md-3">
                                        <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                        <select name="report_type" id="stockReportType" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                            <option value="summary" selected>Without Values (Qty Movement)</option>
                                            <option value="retail">With Retail (Physical &amp; Hold)</option>
                                        </select>
                                    </div>
                                    @endif
                                @else
                                <div class="col-md-3">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Report Type</label>
                                    <select name="report_type" id="stockReportType" class="form-select form-select-sm" required style="height: 30px; font-size: 12px;">
                                        <option value="summary" selected>Without Values (Qty Movement)</option>
                                        <option value="retail">With Retail (Physical &amp; Hold)</option>
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-2">
                                    <label class="fw-bold mb-1" style="font-size: 11px;">Start Date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}" required style="height: 30px; font-size: 12px;">
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
    #itemSearch { height: 24px; font-size: 11px; }
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

            const listId = $(this).closest('.filter-list').attr('id');
            if (['category-list', 'subcat-list', 'brand-list'].includes(listId)) {
                setTimeout(applyItemFilters, 50);
            }
        });

        $('.select-all').on('change', function() {
            const target = $(this).data('target');
            const checked = $(this).is(':checked');
            $('#' + target + ' .filter-item:visible').each(function() {
                $(this).find('input[type="checkbox"]').prop('checked', checked);
                $(this).toggleClass('selected', checked);
            });
            if (['category-list', 'subcat-list', 'brand-list'].includes(target)) {
                applyItemFilters();
            }
        });

        $('#globalSelectAll').on('change', function() {
            const checked = $(this).is(':checked');
            $('.select-all').prop('checked', checked).trigger('change');
        });

        $('#itemSearch').on('keyup', function() {
            applyItemFilters();
        });

        function applyItemFilters() {
            const brands = getCheckedValues('brand-list', 'brand[]');
            const categories = getCheckedValues('category-list', 'category[]');
            const subcategories = getCheckedValues('subcat-list', 'subcategory[]');
            const term = ($('#itemSearch').val() || '').toLowerCase();

            $('#subcat-list .filter-item').each(function() {
                const $item = $(this);
                const catId = String($item.data('category') || '');
                const show = !categories.length || categories.includes(catId);
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });

            $('#item-list .filter-item').each(function() {
                const $item = $(this);
                const brandId = String($item.data('brand') || '');
                const catId = String($item.data('category') || '');
                const subcatId = String($item.data('subcategory') || '');
                const searchText = String($item.data('search') || '');
                const brandMatch = !brands.length || brands.includes(brandId);
                const catMatch = !categories.length || categories.includes(catId);
                const subcatMatch = !subcategories.length || subcategories.includes(subcatId);
                const searchMatch = !term || searchText.includes(term);
                const show = brandMatch && catMatch && subcatMatch && searchMatch;
                $item.toggle(show);
                if (!show) uncheckItem($item);
            });
        }

        function filterByGroup() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            if (selectedGroups.length === 0) {
                $('#warehouse-list .filter-item').show();
                return;
            }
            $('#warehouse-list .filter-item').each(function() {
                const groups = String($(this).data('groups') || '').split(',').filter(Boolean);
                const visible = groups.length === 0 || groups.some(g => selectedGroups.includes(g));
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#group-list .filter-item').on('click', function() {
            setTimeout(filterByGroup, 50);
        });

        @if(!empty($fixedReportType) && $fixedReportType === 'ledger')
        $('#reportForm').on('submit', function(e) {
            if ($('#item-list input[name="item[]"]:checked').length === 0) {
                e.preventDefault();
                alert('Please select at least one Item for Item Stock Ledger.');
            }
        });
        @endif
    });
</script>
@endsection
