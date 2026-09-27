<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BasicPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CLEAR OLD DATA FIRST - This is crucial!
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \DB::table('role_has_permissions')->truncate();
        \DB::table('model_has_permissions')->truncate();
        \DB::table('model_has_roles')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('permissions')->truncate();
        \DB::table('roles')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Comprehensive list of module and report permissions
        $permissions = [
            // Core & Setup
            'Dashboard',
            'Users',
            'Roles',
            'Permissions',
            'Branches',
            'User Groups',
            'Products',
            'Category',
            'Sub Category',
            'Brands',
            'Units',
            'Chart Of Accounts',
            'Narrations',
            'Vendor',
            'Customer',
            'Warehouse',
            'Sales Officer',
            'Zone',

            // Purchase & Inventory
            'Purchase',
            'Purchase Return',
            'Inward Gatepass',
            'Add Gatepass',
            'Warehouse Stock',

            // Sales
            'Sales',
            'Sale Return',

            // General & Hold
            'Stock Transfer',
            'Stock Wastage',
            'Stock Hold',
            'Stock Release',
            'Rollback Posting',
            'Un Post Entries',
            'General Ledger',

            // Claims
            'Customer Claim',
            'Claim Acceptance',
            'Claim Receipt',

            // Vouchers
            'Receipts Voucher',
            'Payment Voucher',
            'Expense Voucher',
            'Income Voucher',
            'Journal Voucher',
            'Adjustment Voucher',

            // Reports
            'Reports',
            'Reports Dashboard',
            'Daily Activity Report',
            'Sales Report',
            'Customer Outstanding Balance',
            'Purchase Report',
            'Claim Report',
            'Claim Acceptance Report',
            'Claim Receipt Report',
            'Stock Report',
            'Item Stock Ledger',
            'Hold & Release Summary',
            'Stock Wastage Report',
            'Stock Transfer Report',
            'Receipt Voucher Report',
            'Payment Voucher Report',
            'Expense Voucher Report',
            'Income Voucher Report',
            'Journal Voucher Report',
            'Adjustment Voucher Report'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Create Admin role and assign all
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // Assign Admin role to default user
        $adminUser = \App\Models\User::where('email', 'admin@admin.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }

        $this->command->info('Minimal module-level permissions seeded successfully.');
    }
}
