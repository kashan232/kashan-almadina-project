<?php

$files = [
    'resources/views/admin_panel/inward/index.blade.php',
    'resources/views/admin_panel/purchase/index.blade.php',
    'resources/views/admin_panel/purchase_return/index.blade.php',
    'resources/views/admin_panel/stock_wastage/index.blade.php',
    'resources/views/admin_panel/vendors/index.blade.php',
    'resources/views/admin_panel/warehouses/stock_transfers/index.blade.php',
    'resources/views/admin_panel/warehouses/index.blade.php',
    'resources/views/admin_panel/sale_return/index.blade.php',
    'resources/views/admin_panel/stock_hold/stock_hold_list.blade.php',
    'resources/views/admin_panel/stock_hold/stock_relase_list.blade.php',
    'resources/views/admin_panel/customers/index.blade.php',
    'resources/views/admin_panel/sales_officer/index.blade.php',
    'resources/views/admin_panel/zone/index.blade.php',
    'resources/views/admin_panel/customer_claims/index.blade.php',
    'resources/views/admin_panel/claim_acceptance/index.blade.php',
    'resources/views/admin_panel/claim_item_receipt/index.blade.php',
    'resources/views/admin_panel/vochers/all_recepit_vochers.blade.php',
    'resources/views/admin_panel/vochers/payment_vochers/all_payment_vochers.blade.php',
    'resources/views/admin_panel/vochers/expense_vochers/all_expense_vochers.blade.php',
    'resources/views/admin_panel/vochers/income_vouchers/all_income_vouchers.blade.php',
    'resources/views/admin_panel/vochers/journal_vouchers/all_journal_vouchers.blade.php',
    'resources/views/admin_panel/vochers/adjustment_vouchers/all_adjustment_vouchers.blade.php',
];

foreach ($files as $path) {
    if (!file_exists($path)) {
        echo "File not found: $path\n";
        continue;
    }

    $content = file_get_contents($path);

    // Get loop variable
    if (!preg_match('/<tbody[^>]*>[\s\S]*?@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
        if (!preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
            echo "No loop var found: $path\n";
            continue;
        }
    }
    $varName = $matches[3];

    $hasCreatedBy = stripos($content, 'Created By') !== false;

    // If it already has Created By, it might be the old format, or it's Inward/Purchase/etc
    if ($hasCreatedBy) {
        // Replace old format <td class="small text-muted">{{ $gp->user->name ?? 'N/A' }}</td>
        // with the new one!
        // We look for <td>.*->user->name.*</td> or similar
        // Let's just find `{{ $var->user->name` and replace the whole <td>
        $content = preg_replace_callback('/<td[^>]*>[\s\S]*?\{\{\s*\$'.$varName.'->user->name[\s\S]*?<\/td>/is', function($m) use ($varName) {
            return "<td>
                                        @if(\${$varName}->creator)
                                            <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                        @else
                                            <span class=\"text-muted small\">System</span>
                                        @endif
                                    </td>";
        }, $content);
        
        file_put_contents($path, $content);
        echo "Updated existing Created By: $path\n";
    } else {
        // It doesn't have Created By. We need to insert TH and TD.
        // Insert TH before Status or Action
        $thReplaced = false;
        $content = preg_replace_callback('/(<th[^>]*>)\s*(Status|Action|Actions)\s*(<\/th>)/i', function($m) use (&$thReplaced) {
            if (!$thReplaced) {
                $thReplaced = true;
                return "<th>Created By</th>\n" . str_repeat(' ', 36) . $m[0];
            }
            return $m[0];
        }, $content);

        // Insert TD before the FIRST Status badge or Action buttons inside the tbody
        $parts = explode('<tbody', $content, 2);
        if (count($parts) == 2) {
            $beforeTbody = $parts[0];
            $afterTbody = '<tbody' . $parts[1];
            
            $tdReplaced = false;
            $afterTbody = preg_replace_callback('/<td[^>]*class="[^"]*text-center[^"]*"[^>]*>/is', function($m) use (&$tdReplaced, $varName) {
                if (!$tdReplaced) {
                    $tdReplaced = true;
                    return "<td>
                                        @if(\${$varName}->creator)
                                            <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                        @else
                                            <span class=\"text-muted small\">System</span>
                                        @endif
                                    </td>\n" . str_repeat(' ', 36) . $m[0];
                }
                return $m[0];
            }, $afterTbody, 1);

            if (!$tdReplaced) {
                $afterTbody = preg_replace_callback('/<td[^>]*>\s*(<div class="d-flex|<form action=|<a[^>]+btn)/is', function($m) use (&$tdReplaced, $varName) {
                    if (!$tdReplaced) {
                        $tdReplaced = true;
                        return "<td>
                                        @if(\${$varName}->creator)
                                            <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                        @else
                                            <span class=\"text-muted small\">System</span>
                                        @endif
                                    </td>\n" . str_repeat(' ', 36) . $m[0];
                    }
                    return $m[0];
                }, $afterTbody, 1);
            }

            if ($thReplaced && $tdReplaced) {
                file_put_contents($path, $beforeTbody . $afterTbody);
                echo "Inserted new Created By: $path\n";
            } else {
                echo "Failed to find injection points for: $path (th: $thReplaced, td: $tdReplaced)\n";
            }
        }
    }
}
