@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h4 class="mb-0 fw-bold text-center" style="color: #6a1b9a;">Sales Report Filters</h4>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('reports.sales.preview') }}" method="POST" target="_blank" id="reportForm">
                    @csrf
                    
                    <div class="filter-grid-container">
                        <!-- Row 1 -->
                        <div class="row g-2 mb-3">
                            <!-- Zone -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="zone-list"> Zone
                                    </div>
                                    <div class="filter-list" id="zone-list">
                                        @foreach($zones as $z)
                                            <div class="filter-item">
                                                <input type="checkbox" name="zone[]" value="{{ $z->zone }}">
                                                <span>{{ $z->zone }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Sales Officer -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="officer-list"> Sales Officer
                                    </div>
                                    <div class="filter-list" id="officer-list">
                                        @foreach($users as $u)
                                            <div class="filter-item">
                                                <input type="checkbox" name="sales_officer[]" value="{{ $u->id }}">
                                                <span>{{ $u->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Warehouse -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="warehouse-list"> Warehouse
                                    </div>
                                    <div class="filter-list" id="warehouse-list">
                                        @foreach($warehouses as $w)
                                            <div class="filter-item">
                                                <input type="checkbox" name="warehouse[]" value="{{ $w->id }}">
                                                <span>{{ $w->warehouse_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Category -->
                            <div class="col-md-2">
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
                            <!-- Sub-Category -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="subcat-list"> Sub-Category
                                    </div>
                                    <div class="filter-list" id="subcat-list">
                                        @foreach($subcategories as $sub)
                                            <div class="filter-item">
                                                <input type="checkbox" name="subcategory[]" value="{{ $sub->id }}">
                                                <span>{{ $sub->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Brand -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="brand-list"> Item Company
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
                        </div>

                        <!-- Row 2 -->
                        <div class="row g-2">
                            <!-- Items -->
                            <div class="col-md-4">
                                <div class="filter-column">
                                    <div class="filter-header d-flex justify-content-between align-items-center">
                                        <div><input type="checkbox" class="select-all" data-target="item-list"> Item</div>
                                        <input type="text" class="form-control form-control-sm w-50" id="itemSearch" placeholder="Search item...">
                                    </div>
                                    <div class="filter-list item-list-wide" id="item-list">
                                        @foreach($products as $prod)
                                            <div class="filter-item" data-search="{{ strtolower($prod->name) }}">
                                                <input type="checkbox" name="item[]" value="{{ $prod->id }}">
                                                <span>{{ $prod->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Party Type -->
                            <div class="col-md-2">
                                <div class="filter-column">
                                    <div class="filter-header">
                                        <input type="checkbox" class="select-all" data-target="partytype-list"> Party Type
                                    </div>
                                    <div class="filter-list" id="partytype-list">
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Customer"><span>Customer</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Vendor"><span>Vendor</span></div>
                                        <div class="filter-item"><input type="checkbox" name="party_type[]" value="Walkin"><span>Walkin</span></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Party -->
                            <div class="col-md-3">
                                <div class="filter-column">
                                    <div class="filter-header d-flex justify-content-between align-items-center">
                                        <div><input type="checkbox" class="select-all" data-target="party-list"> Party</div>
                                        <input type="text" class="form-control form-control-sm w-50" id="partySearch" placeholder="Search party...">
                                    </div>
                                    <div class="filter-list" id="party-list">
                                        @foreach($customers as $cust)
                                            <div class="filter-item" data-search="{{ strtolower($cust->customer_name) }}">
                                                <input type="checkbox" name="party[]" value="{{ $cust->id }}">
                                                <span>{{ $cust->customer_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- Invoice No & Report Type -->
                            <div class="col-md-3">
                                <div class="card border p-2 h-100">
                                    <div class="mb-2">
                                        <label class="fw-bold small">Invoice No.</label>
                                        <input type="text" name="invoice_no" class="form-control form-control-sm">
                                    </div>
                                    <div class="mb-2">
                                        <label class="fw-bold small">Report Type</label>
                                        <select name="report_type" class="form-select form-select-sm" required>
                                            <option value="Invoice Wise">Invoice Wise</option>
                                            <option value="Item Wise">Item Wise</option>
                                            <option value="Party Wise">Party Wise</option>
                                        </select>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="fw-bold small">From</label>
                                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="fw-bold small">To</label>
                                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4 text-center pb-4">
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button type="submit" class="btn btn-erp px-4">Preview</button>
                            <button type="button" class="btn btn-erp px-4">Preview (Qty Wise)</button>
                            <button type="button" class="btn btn-erp px-4">Preview (%)</button>
                            <button type="button" class="btn btn-erp px-4">Summary</button>
                            <button type="button" class="btn btn-erp px-4">Tax Detail</button>
                            <button type="button" class="btn btn-erp px-4">Tax Summary</button>
                            <button type="button" class="btn btn-danger btn-sm px-4" onclick="location.reload()">Reset</button>
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
    }
    
    .filter-header {
        background: #e9ecef;
        padding: 5px 10px;
        border-bottom: 1px solid #ced4da;
        font-weight: bold;
        font-size: 13px;
        color: #495057;
    }
    
    .filter-list {
        height: 200px;
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
        // Toggle item selection
        $(document).on('click', '.filter-item', function(e) {
            if ($(e.target).is('input[type="checkbox"]')) return;
            
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            $(this).toggleClass('selected', checkbox.prop('checked'));
        });

        // Select All functionality
        $('.select-all').on('change', function() {
            const targetId = $(this).data('target');
            const isChecked = $(this).prop('checked');
            const $list = $('#' + targetId);
            
            // Only affect visible items (if filtered)
            $list.find('.filter-item').each(function() {
                if ($(this).css('display') !== 'none') {
                    const cb = $(this).find('input[type="checkbox"]');
                    cb.prop('checked', isChecked);
                    $(this).toggleClass('selected', isChecked);
                }
            });
        });

        // Search functionality for Item
        $('#itemSearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#item-list .filter-item').each(function() {
                const text = $(this).data('search');
                $(this).toggle(text.indexOf(value) > -1);
            });
        });

        // Search functionality for Party
        $('#partySearch').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#party-list .filter-item').each(function() {
                const text = $(this).data('search');
                $(this).toggle(text.indexOf(value) > -1);
            });
        });
    });
</script>
@endsection
