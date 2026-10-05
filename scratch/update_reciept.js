const fs = require('fs');
const path = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(path, 'utf8');

const target = `<select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2" data-selected="{{ $rowAccounts[$index] ?? '' }}">
                                                <option value="">Select Account...</option>
                                            </select>`;

const replacement = `<select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2" data-selected="{{ $rowAccounts[$index] ?? '' }}">
                                                <option value="">Select Account...</option>
                                                @if(isset($allAccounts))
                                                    @foreach($allAccounts as $acc)
                                                    <option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}" {{ ($rowAccounts[$index] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->title }}</option>
                                                    @endforeach
                                                @endif
                                            </select>`;

if (content.includes(target)) {
    content = content.replace(target, replacement);
    fs.writeFileSync(path, content, 'utf8');
    console.log('SUCCESS');
} else {
    // try with \n
    const targetLF = target.replace(/\r\n/g, '\n');
    const replacementLF = replacement.replace(/\r\n/g, '\n');
    if (content.includes(targetLF)) {
        content = content.replace(targetLF, replacementLF);
        fs.writeFileSync(path, content, 'utf8');
        console.log('SUCCESS LF');
    } else {
        console.log('TARGET NOT FOUND');
    }
}
