<?php
$path = 'resources/views/admin_panel/warehouses/stock_transfers/index.blade.php';
$content = file_get_contents($path);

// Remove the <th>Prepared By</th>
$content = str_replace("<th>Prepared By</th>", "", $content);

// Remove the old <td><small>{{ $t->creator->name ?? '—' }}</small></td>
$content = str_replace("<td><small>{{ \$t->creator->name ?? '—' }}</small></td>", "", $content);

// Add 'Pending Approval' to the status checks
$content = str_replace("@elseif(\$t->status == 'Unposted' || \$t->status == 'pending')", "@elseif(\$t->status == 'Unposted' || \$t->status == 'pending' || \$t->status == 'Pending Approval')", $content);

$content = str_replace("@if(\$t->status == 'Unposted' || \$t->status == 'pending')", "@if(\$t->status == 'Unposted' || \$t->status == 'pending' || \$t->status == 'Pending Approval')", $content);

file_put_contents($path, $content);
echo "Fixed $path\n";
