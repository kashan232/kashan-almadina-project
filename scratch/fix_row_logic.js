const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// Replace rowAccountHead and rowAccountSub change handlers with solid logic
const oldBlock = `    // 🏦 Row Account Logic
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

const newBlock = `    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountSub', function() {
        let $row = $(this).closest('tr');
        let $opt = $(this).find('option:selected');
        let code = $opt.attr('data-code') || $opt.data('code');
        let headId = $opt.attr('data-head-id') || $opt.data('head-id');
        
        $row.find('.rowAccountCode').val(code || '');
        
        if (headId) {
            let $headSelect = $row.find('.rowAccountHead');
            if ($headSelect.val() != headId) {
                $headSelect.val(headId).trigger('change.select2');
            }
        }
    });

    $(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let currentSubOpt = $subSelect.find('option:selected');
        let currentHeadOfSub = currentSubOpt.attr('data-head-id') || currentSubOpt.data('head-id');
        
        // If the selected head matches current sub-account's head, don't clear it
        if (headId && currentHeadOfSub == headId) {
            return;
        }

        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    $subSelect.append(\`<option value="\${acc.id}" data-head-id="\${headId}" data-code="\${acc.account_code}">\${acc.title}</option>\`);
                });
                $subSelect.trigger('change.select2');
            });
        }
    });

    // Page Load Init for existing rows
    $('.rowAccountSub').each(function() {
        if ($(this).val()) {
            $(this).trigger('change');
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

if (content.includes(oldBlock.replace(/\r\n/g, '\n'))) {
    content = content.replace(oldBlock.replace(/\r\n/g, '\n'), newBlock.replace(/\r\n/g, '\n'));
    fs.writeFileSync(file, content, 'utf8');
    console.log('SUCCESS replacing JS block');
} else {
    // Try CRLF
    content = content.replace(oldBlock.replace(/\n/g, '\r\n'), newBlock.replace(/\n/g, '\r\n'));
    fs.writeFileSync(file, content, 'utf8');
    console.log('SUCCESS replacing CRLF JS block');
}
