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
    'resources/views/admin_panel/sale/index.blade.php', // I need to remove role from sale too!
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

    if ($hasCreatedBy) {
        // It already has a "Created By" column! (like sale, inward, purchase, etc.)
        // We just need to update its <td>.
        // It's usually <td>{{ $varName->user->name ?? 'N/A' }}</td>
        // Or in sale: <td>@if($varName->user)...</td>
        
        // Find the <td> that contains either `$varName->user` or `$varName->creator` and has `->name`
        // We will split by <tr> inside tbody.
        $parts = explode('<tbody', $content, 2);
        if (count($parts) == 2) {
            $tbody = '<tbody' . $parts[1];
            
            // Just replace the inner content of the exact TD
            $tbody = preg_replace('/(<td[^>]*>)\s*\{\{\s*\$'.$varName.'->user->name[^}]*\}\}\s*(<\/td>)/i', '$1
                                        @if($'.$varName.'->creator)
                                            <span class="text-dark small">{{ $'.$varName.'->creator->name }}</span>
                                        @else
                                            <span class="text-muted small">System</span>
                                        @endif
                                    $2', $tbody);

            // Also fix if it was using the old complex format with roles
            $tbody = preg_replace('/(<td[^>]*>)\s*@if\(\$'.$varName.'->(user|creator)\)[\s\S]*?(System|User)[\s\S]*?@endif\s*(<\/td>)/i', '$1
                                        @if($'.$varName.'->creator)
                                            <span class="text-dark small">{{ $'.$varName.'->creator->name }}</span>
                                        @else
                                            <span class="text-muted small">System</span>
                                        @endif
                                    $4', $tbody);
                                    
            // For stock_wastage, it has <th>Prepared By</th> and <td>{{ $wastage->user->name ?? 'N/A' }}</td>
            // We should change 'Prepared By' or 'User' to 'Created By' in the th? The user didn't ask to rename them, but maybe it's good. Let's leave headers alone unless we're injecting a new one.
            
            $content = $parts[0] . $tbody;
            file_put_contents($path, $content);
            echo "Updated existing Created By TD in: $path\n";
        }
    } else {
        // Doesn't have Created By. Safe to inject TH and TD.
        $thReplaced = false;
        $content = preg_replace_callback('/(<th[^>]*>)\s*(Status|Action|Actions)\s*(<\/th>)/i', function($m) use (&$thReplaced) {
            if (!$thReplaced) {
                $thReplaced = true;
                return "<th>Created By</th>\n" . str_repeat(' ', 36) . $m[0];
            }
            return $m[0];
        }, $content);

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
