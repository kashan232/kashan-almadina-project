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

        // One permission per module only.
        $permissions = [
            'Dashboard',
            'Products',
            'Category',
            'Sub Category',
            'Brands',
            'Units',
            'Inward Gatepass',
            'Purchase',
            'Stock Wastage',
            'Vendor',
            'Warehouse',
            'Stock Transfer',
            'Sales',
            'Sale Return',
            'Stock Hold',
            'Customer',
            'Sales Officer',
            'Zone',
            'Chart Of Accounts',
            'Narrations',
            'Receipts Voucher',
            'Payment Voucher',
            'Expense Voucher',
            'Journal Voucher',
            'Reports',
            'Users',
            'Roles',
            'Branches'
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
