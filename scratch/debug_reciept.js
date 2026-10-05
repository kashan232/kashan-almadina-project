const fs = require('fs');
const file = 'resources/views/admin_panel/vochers/reciepts_vouchers.blade.php';
let lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);

lines.forEach((line, idx) => {
    if (line.includes('rowAccountSub')) {
        console.log(`LINE ${idx + 1}: ${JSON.stringify(line)}`);
        console.log(`NEXT 1: ${JSON.stringify(lines[idx+1])}`);
        console.log(`NEXT 2: ${JSON.stringify(lines[idx+2])}`);
    }
});
