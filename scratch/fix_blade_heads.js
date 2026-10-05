const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let content = fs.readFileSync(file, 'utf8');

// Replace lines 147-151 to allow ALL AccountHeads without filtering
const oldHeads = `@foreach($AccountHeads as $head)\n                                                 @if(str_contains(strtoupper($head->name), 'CASH') || str_contains(strtoupper($head->name), 'BANK') || $head->id == 100000 || strtoupper($head->name) == 'SCRAP')\n                                                 <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>\n                                                 @endif\n                                                 @endforeach`;

const newHeads = `@foreach($AccountHeads as $head)\n                                                 <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>\n                                                 @endforeach`;

if (content.includes(oldHeads.replace(/\n/g, '\r\n'))) {
    content = content.replace(oldHeads.replace(/\n/g, '\r\n'), newHeads.replace(/\n/g, '\r\n'));
} else {
    content = content.replace(oldHeads, newHeads);
}

fs.writeFileSync(file, content, 'utf8');
console.log('AccountHeads filter removed successfully!');
