const fs = require('fs');
const file = 'resources/views/admin_panel/sale_return/add_return.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Blade loop line 310
content = content.replace(
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end" value="{{ $item->retail_price }}"></td>',
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end input-readonly" readonly value="{{ $item->retail_price }}"></td>'
);

// 2. JS manual row template line 1018
content = content.replace(
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end"></td>',
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end input-readonly" readonly></td>'
);

// 3. JS invoice row template line 1118
content = content.replace(
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end" value="${item.retail_price}"></td>',
    '<td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end input-readonly" readonly value="${item.retail_price}"></td>'
);

fs.writeFileSync(file, content, 'utf8');
console.log('Disabled retail_price inputs in add_return.blade.php successfully!');
