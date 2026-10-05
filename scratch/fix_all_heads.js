const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Remove Account Head filter @if condition so all Account Heads are available in options
content = content.replace(
    `@if(str_contains(strtoupper($head->name), 'CASH') || str_contains(strtoupper($head->name), 'BANK') || $head->id == 100000 || strtoupper($head->name) == 'SCRAP')\n                                                  <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>\n                                                  @endif`,
    `<option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>`
);

content = content.replace(
    `@if(str_contains(strtoupper($head->name), "CASH") || str_contains(strtoupper($head->name), "BANK") || $head->id == 100000 || strtoupper($head->name) == "SCRAP")<option value="{{ $head->id }}">{{ addslashes($head->name) }}</option>@endif`,
    `<option value="{{ $head->id }}">{{ addslashes($head->name) }}</option>`
);

// 2. Fix rowAccountSub change handler to update rowAccountHead using .val(headId).trigger('change')
const oldSubHandler = `    $(document).on('change', '.rowAccountSub', function() {
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
    });`;

const newSubHandler = `    $(document).on('change', '.rowAccountSub', function() {
        let $row = $(this).closest('tr');
        let $opt = $(this).find('option:selected');
        let code = $opt.attr('data-code') || $opt.data('code');
        let headId = $opt.attr('data-head-id') || $opt.data('head-id');
        
        $row.find('.rowAccountCode').val(code || '');
        
        if (headId) {
            let $headSelect = $row.find('.rowAccountHead');
            $headSelect.val(headId).trigger('change');
        }
    });`;

if (content.includes(oldSubHandler.replace(/\r\n/g, '\n'))) {
    content = content.replace(oldSubHandler.replace(/\r\n/g, '\n'), newSubHandler.replace(/\r\n/g, '\n'));
} else {
    content = content.replace(oldSubHandler.replace(/\n/g, '\r\n'), newSubHandler.replace(/\n/g, '\r\n'));
}

fs.writeFileSync(file, content, 'utf8');
console.log('Successfully updated reciepts_vouchers.blade.php');
