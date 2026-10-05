const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// We will replace lines 322 to 395 cleanly
const targetSection = `    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let selected = $subSelect.data('selected');
        $row.find('.rowAccountCode').val('');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(\`<option value="\${acc.id}" data-code="\${acc.account_code}" \${sel}>\${acc.title}</option>\`);
                });
                if(selected) { let code = $subSelect.find('option:selected').data('code'); $row.find('.rowAccountCode').val(code || ''); }
            });
        }
    });

    // Trigger on page load
    $('.rowAccountSub').each(function() {
        let $opt = $(this).find('option:selected');
        if ($opt.length && $opt.val()) {
            let code = $opt.data('code');
            let headId = $opt.data('head-id');
            let $row = $(this).closest('tr');
            if (code) $row.find('.rowAccountCode').val(code);
            if (headId && !$row.find('.rowAccountHead').val()) $row.find('.rowAccountHead').val(headId).trigger('change.select2');
        }
    });
    $('.discountAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });

    // 🏷️ Voucher Level Discount Head & Sub Head Logic
    $(document).on('change', '#discount_head', function() {
        let headId = $(this).val();
        let $subSelect = $('#discount_account_id');
        let selected = $subSelect.data('selected');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Sub Head Account...</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(\`<option value="\${acc.id}" \${sel}>\${acc.title}</option>\`);
                });
                $subSelect.data('selected', '');
                $subSelect.trigger('change');
            });
        } else {
            $subSelect.html('<option value="">Select Sub Head Account...</option>').trigger('change');
        }
    });

    if ($('#discount_head').val()) {
        $('#discount_head').trigger('change');
    }

    $(document).on('change', '.rowAccountSub', function() {
        let $row = $(this).closest('tr');
        let $opt = $(this).find('option:selected');
        let code = $opt.data('code');
        let headId = $opt.data('head-id');
        $row.find('.rowAccountCode').val(code || '');
        if (headId && !$row.find('.rowAccountHead').data('updating-from-sub')) {
            let $headSelect = $row.find('.rowAccountHead');
            if ($headSelect.val() != headId) {
                $headSelect.data('updating-from-sub', true);
                $headSelect.val(headId).trigger('change');
                setTimeout(() => { $headSelect.data('updating-from-sub', false); }, 100);
            }
        }
    });`;

const replacementSection = `    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountSub', function() {
        let $row = $(this).closest('tr');
        let $opt = $(this).find('option:selected');
        let code = $opt.attr('data-code') || $opt.data('code');
        let headId = $opt.attr('data-head-id') || $opt.data('head-id');
        
        if (code) {
            $row.find('.rowAccountCode').val(code);
        }
        
        if (headId) {
            let $headSelect = $row.find('.rowAccountHead');
            if ($headSelect.val() != headId) {
                $headSelect.val(headId).trigger('change.select2');
            }
        }
    });

    $(document).on('change', '.rowAccountHead', function(e, isUserAction) {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let selectedSubId = $subSelect.val();
        
        // Only fetch/reload sub-accounts if this change was directly triggered by selecting a Head manually
        if (headId && isUserAction) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selectedSubId) ? 'selected' : '';
                    $subSelect.append(\`<option value="\${acc.id}" data-head-id="\${headId}" data-code="\${acc.account_code}" \${sel}>\${acc.title}</option>\`);
                });
                $subSelect.trigger('change.select2');
            });
        }
    });

    // 🏷️ Voucher Level Discount Head & Sub Head Logic
    $(document).on('change', '#discount_head', function() {
        let headId = $(this).val();
        let $subSelect = $('#discount_account_id');
        let selected = $subSelect.data('selected');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Sub Head Account...</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(\`<option value="\${acc.id}" \${sel}>\${acc.title}</option>\`);
                });
                $subSelect.data('selected', '');
                $subSelect.trigger('change');
            });
        } else {
            $subSelect.html('<option value="">Select Sub Head Account...</option>').trigger('change');
        }
    });

    if ($('#discount_head').val()) {
        $('#discount_head').trigger('change');
    }`;

let targetClean = targetSection.replace(/\r\n/g, '\n');
let contentClean = content.replace(/\r\n/g, '\n');

if (contentClean.includes(targetClean)) {
    let result = contentClean.replace(targetClean, replacementSection.replace(/\r\n/g, '\n'));
    fs.writeFileSync(file, result, 'utf8');
    console.log('Successfully updated JS logic!');
} else {
    console.log('Target block not found!');
}
