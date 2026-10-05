const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// Replace @endif with @endif in line 459 JS template string
content = content.replace(
    `'<td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option>@if(isset($allAccounts))@foreach($allAccounts as $acc)<option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}">{{ addslashes($acc->title) }}</option>@endforeach@endif</select></td>' +`,
    `'<td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option>@if(isset($allAccounts))@foreach($allAccounts as $acc)<option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}">{{ addslashes($acc->title) }}</option>@endforeach @endif</select></td>' +`
);

fs.writeFileSync(file, content, 'utf8');
console.log('Fixed @endif spacing');
