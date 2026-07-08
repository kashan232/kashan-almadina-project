@php
    $isEdit = isset($product) && $product;
    $initialCategory = old('category', $isEdit ? $product->category_id : null);
    $initialSubCategory = old('sub_category', $isEdit ? $product->sub_category_id : null);
@endphp

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: "{{ session('success') }}",
        timer: 2000,
        showConfirmButton: false
    });
</script>
@endif

@if ($errors->any())
<script>
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: `{!! implode('<br>', $errors->all()) !!}`,
        timer: 3000,
        showConfirmButton: false
    });
</script>
@endif

<script>
    $(document).ready(function() {
        const initialCategory = @json($initialCategory ?? null);
        const initialSubCategory = @json($initialSubCategory ?? null);

        $('#form').on('keydown', 'input, select', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });

        function parseFormNum(val) {
            if (typeof window.parseUS === 'function') {
                const parsed = window.parseUS(val);
                return parsed === '' ? 0 : Number(parsed);
            }

            return parseFloat(String(val).replace(/,/g, '')) || 0;
        }

        $('#form').on('submit', function(e) {
            if (window.USNumber) {
                USNumber.unformatForm(this);
            }

            const submitter = e.originalEvent && e.originalEvent.submitter;
            if (!submitter || submitter.id !== 'btnSave') {
                e.preventDefault();
            }
        });

        $('#category-dropdown').on('change', function() {
            loadSubcategories($(this).val());
        });

        function loadSubcategories(categoryId, selectedSubId = null) {
            if (categoryId) {
                $.ajax({
                    url: '/get-subcategories/' + categoryId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#subcategory-dropdown').empty();
                        $('#subcategory-dropdown').append('<option selected disabled>Select</option>');
                        $.each(data, function(key, value) {
                            const isSelected = (selectedSubId && String(selectedSubId) === String(value.id)) ? 'selected' : '';
                            $('#subcategory-dropdown').append('<option value="' + value.id + '" ' + isSelected + '>' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#subcategory-dropdown').empty().append('<option selected disabled>Select</option>');
            }
        }

        if ($('#subcategory-dropdown option').length <= 1 && initialCategory) {
            loadSubcategories(initialCategory, initialSubCategory);
        }

        function calculateValues(section) {
            const retailPrice = parseFormNum($(`[name="${section}_retail_price"]`).val());
            const taxPercent = parseFormNum($(`[name="${section}_tax_percent"]`).val());
            const discountPercent = parseFormNum($(`[name="${section}_discount_percent"]`).val());

            const taxAmount = (retailPrice * taxPercent / 100).toFixed(2);
            const discountAmount = (retailPrice * discountPercent / 100).toFixed(2);
            const netAmount = (retailPrice + parseFloat(taxAmount) - parseFloat(discountAmount)).toFixed(2);

            $(`[name="${section}_tax_amount"]`).val(taxAmount);
            $(`[name="${section}_discount_amount"]`).val(discountAmount);
            $(`[name="${section}_net_amount"]`).val(netAmount);
        }

        function calculateSaleValues() {
            const retail = parseFormNum($('[name="sale_retail_price"]').val());
            const taxPct = parseFormNum($('[name="sale_tax_percent"]').val());
            const whtPct = parseFormNum($('[name="sale_wht_percent"]').val());
            const discPct = parseFormNum($('[name="sale_discount_percent"]').val());

            const taxAmount = retail * (taxPct / 100);
            $('[name="sale_tax_amount"]').val(taxAmount.toFixed(2));

            const afterTax = retail + taxAmount;
            $('[name="sale_after_tax_amount"]').val(afterTax.toFixed(2));

            const whtAmount = afterTax * (whtPct / 100);
            $('[name="sale_wht_amount"]').val(whtAmount.toFixed(2));

            const discountAmount = retail * (discPct / 100);
            $('[name="sale_discount_amount"]').val(discountAmount.toFixed(2));

            const net = afterTax + whtAmount - discountAmount;
            $('[name="sale_net_amount"]').val(net.toFixed(2));
        }

        $('[name="purchase_retail_price"], [name="purchase_tax_percent"], [name="purchase_discount_percent"]').on('input', function() {
            calculateValues('purchase');
        });

        $('[name="sale_retail_price"], [name="sale_tax_percent"], [name="sale_wht_percent"], [name="sale_discount_percent"]').on('input', calculateSaleValues);

        calculateValues('purchase');
        calculateSaleValues();

        function updateShopStock() {
            let total = parseFormNum($('#total-stock-input').val());
            let distributed = 0;
            $('.warehouse-stock-input').each(function() {
                distributed += parseFormNum($(this).val());
            });
            let shopBalance = total - distributed;
            $('#shop-stock-display').text(shopBalance);

            if (shopBalance < 0) {
                $('#shop-stock-display').removeClass('bg-info').addClass('bg-danger');
            } else {
                $('#shop-stock-display').removeClass('bg-danger').addClass('bg-info');
            }
        }

        $(document).on('input', '#total-stock-input, .warehouse-stock-input', updateShopStock);
        updateShopStock();
    });
</script>
