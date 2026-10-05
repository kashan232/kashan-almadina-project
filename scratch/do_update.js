const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);

let idx155 = 155; // 0-based is 155 for line 156
lines.splice(idx155, 3, 
    '                                            <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2" data-selected="{{ $rowAccounts[$index] ?? \'\' }}">',
    '                                                <option value="">Select Account...</option>',
    '                                                @if(isset($allAccounts))',
    '                                                    @foreach($allAccounts as $acc)',
    '                                                    <option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}" {{ ($rowAccounts[$index] ?? \'\') == $acc->id ? \'selected\' : \'\' }}>{{ $acc->title }}</option>',
    '                                                    @endforeach',
    '                                                @endif',
    '                                            </select>'
);

fs.writeFileSync(file, lines.join('\r\n'), 'utf8');
console.log('Successfully updated HTML select options in reciepts_vouchers.blade.php!');
