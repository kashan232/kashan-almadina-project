const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// Replace JS template line 457
content = content.replace(
    `'<td><select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head) @if(str_contains(strtoupper($head->name), \'CASH\') || str_contains(strtoupper($head->name), \'BANK\') || $head->id == 100000 || strtoupper($head->name) == \'SCRAP\')<option value="{{ $head->id }}">{{ addslashes($head->name) }}</option>@endif @endforeach</select></td>' +`,
    `'<td><select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head) @if(str_contains(strtoupper($head->name), \\'CASH\\') || str_contains(strtoupper($head->name), \\'BANK\\') || $head->id == 100000 || strtoupper($head->name) == \\'SCRAP\\')<option value="{{ $head->id }}">{{ addslashes($head->name) }}</option>@endif @endforeach</select></td>' +`
);

fs.writeFileSync(file, content, 'utf8');
console.log('Fixed JS string quoting');
