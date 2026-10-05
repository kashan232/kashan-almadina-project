const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Build all accounts options string for Javascript template when Add Line button is clicked
// We will generate the options string in Blade format

content = content.replace(
    `'<td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option></select></td>' +`,
    `'<td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option>@if(isset($allAccounts))@foreach($allAccounts as $acc)<option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}">{{ addslashes($acc->title) }}</option>@endforeach@endif</select></td>' +`
);

// 2. Update .rowAccountSub change handler so selecting an account automatically sets rowAccountHead and rowAccountCode
content = content.replace(
    `$(document).on('change', '.rowAccountSub', function() { let code = $(this).find('option:selected').data('code'); $(this).closest('tr').find('.rowAccountCode').val(code || ''); });`,
    `$(document).on('change', '.rowAccountSub', function() {
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
    });`
);

// 3. Update rowAccountHead change handler so if user changes head, we filter Destination Accounts or if updated-from-sub we don't clear
content = content.replace(
    `$(document).on('change', '.rowAccountHead', function() {
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
    });`,
    `$(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        if ($row.find('.rowAccountHead').data('updating-from-sub')) {
            return;
        }
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let selected = $subSelect.val() || $subSelect.data('selected');
        $row.find('.rowAccountCode').val('');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(\`<option value="\${acc.id}" data-head-id="\${headId}" data-code="\${acc.account_code}" \${sel}>\${acc.title}</option>\`);
                });
                if(selected) { let code = $subSelect.find('option:selected').data('code'); $row.find('.rowAccountCode').val(code || ''); }
            });
        } else {
            $subSelect.html('<option value="">Select Account...</option>');
        }
    });`
);

// 4. Update page load initialization so existing rowAccountSub triggers code and head set
content = content.replace(
    `$('.rowAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });`,
    `$('.rowAccountSub').each(function() {
        let $opt = $(this).find('option:selected');
        if ($opt.length && $opt.val()) {
            let code = $opt.data('code');
            let headId = $opt.data('head-id');
            let $row = $(this).closest('tr');
            if (code) $row.find('.rowAccountCode').val(code);
            if (headId && !$row.find('.rowAccountHead').val()) $row.find('.rowAccountHead').val(headId).trigger('change.select2');
        }
    });`
);

fs.writeFileSync(file, content, 'utf8');
console.log('Update script executed successfully!');
