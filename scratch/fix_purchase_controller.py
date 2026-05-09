import sys

file_path = r'c:\xampp\htdocs\Al-madina-bettery\app\Http\Controllers\PurchaseController.php'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Line numbers are 1-indexed, so line 732 is index 731
# We want to replace from line 733 (index 732) to line 757 (index 756)
# Based on previous view_file:
# 732:             } else {
# 733:                 // UPDATE WAREHOUSE STOCK
# ...
# 757:         }

start_idx = 732
end_idx = 756

new_code = [
    '                // UPDATE WAREHOUSE STOCK\n',
    '                $stock = \\App\\Models\\WarehouseStock::firstOrNew([\n',
    '                    \'warehouse_id\' => $purchase->warehouse_id,\n',
    '                    \'product_id\'   => $productId,\n',
    '                ]);\n',
    '                $stock->quantity = ($stock->quantity ?? 0) + $qty;\n',
    '                $stock->save();\n',
    '            }\n',
    '        }\n',
    '\n',
    '        // 2. Ledger Impact (Standardized with history)\n',
    '        $amount   = $purchase->net_amount;\n',
    '        $type     = strtolower(class_basename($purchase->purchasable_type));\n',
    '        $party_id = $purchase->purchasable_id;\n',
    '\n',
    '        if ($type === \'vendor\') {\n',
    '            $latestLedger = VendorLedger::where(\'vendor_id\', $party_id)->latest(\'id\')->first();\n',
    '            $prevBalance = $latestLedger ? $latestLedger->closing_balance : 0;\n',
    '            \n',
    '            VendorLedger::create([\n',
    '                \'vendor_id\'        => $party_id,\n',
    '                \'admin_or_user_id\' => auth()->id(),\n',
    '                \'date\'             => $purchase->current_date,\n',
    '                \'description\'      => \'Purchase ID: \' . $purchase->invoice_no,\n',
    '                \'opening_balance\'  => $prevBalance,\n',
    '                \'previous_balance\' => $prevBalance,\n',
    '                \'debit\'            => 0,\n',
    '                \'credit\'           => $amount,\n',
    '                \'closing_balance\'  => $prevBalance + $amount, // Credit increases liability\n',
    '            ]);\n',
    '        } elseif ($type === \'customer\') {\n',
    '            $latestLedger = CustomerLedger::where(\'customer_id\', $party_id)->latest(\'id\')->first();\n',
    '            $prevBalance = $latestLedger ? $latestLedger->closing_balance : 0;\n',
    '\n',
    '            CustomerLedger::create([\n',
    '                \'customer_id\'      => $party_id,\n',
    '                \'admin_or_user_id\' => auth()->id(),\n',
    '                \'date\'             => $purchase->current_date,\n',
    '                \'description\'      => \'Purchase ID: \' . $purchase->invoice_no,\n',
    '                \'previous_balance\' => $prevBalance,\n',
    '                \'opening_balance\'  => $prevBalance,\n',
    '                \'closing_balance\'  => $prevBalance + $amount,\n',
    '            ]);\n',
    '        }\n',
    '\n',
    '        // 3. Account Allocation\n',
    '        foreach ($purchase->accountAllocations as $allocation) {\n',
    '            $account = $allocation->account;\n',
    '            if ($account) {\n',
    '                // Update Account Balance\n',
    '                $account->opening_balance = ($account->opening_balance ?? 0) - $allocation->amount; \n',
    '                $account->save();\n',
    '\n',
    '                // Create a Journal Voucher entry to show in reports\n',
    '                $jvid = \'PJ-ALLOC-\' . $purchase->id;\n',
    '                JournalVoucher::create([\n',
    '                    \'jvid\' => $jvid,\n',
    '                    \'entry_date\' => $purchase->current_date,\n',
    '                    \'status\' => \'posted\',\n',
    '                    \'total_debit\' => 0,\n',
    '                    \'total_credit\' => $allocation->amount,\n',
    '                    \'party_type\' => $account->head_id, \n',
    '                    \'party_id\' => json_encode([$account->id]),\n',
    '                    \'debit\' => json_encode([0]),\n',
    '                    \'credit\' => json_encode([$allocation->amount]),\n',
    '                    \'remarks\' => \'Allocation from Purchase: \' . $purchase->invoice_no,\n',
    '                ]);\n',
    '            }\n'
]

lines[start_idx:end_idx+1] = new_code

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(lines)
