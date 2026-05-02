<?php

use Spatie\Permission\Models\Role;
use App\Models\User;

$role = Role::where('name', 'Cashier')->first();
if ($role) {
    echo "Permissions for role 'Cashier':\n";
    print_r($role->permissions->pluck('name')->toArray());
} else {
    echo "Role 'Cashier' not found.\n";
}

$user = User::where('email', 'cashier@gmail.com')->first();
if ($user) {
    echo "\nRoles for user 'cashier@gmail.com':\n";
    print_r($user->getRoleNames()->toArray());
    echo "\nDirect Permissions for user 'cashier@gmail.com':\n";
    print_r($user->getPermissionNames()->toArray());
    echo "\nAll Permissions (via roles) for user 'cashier@gmail.com':\n";
    print_r($user->getAllPermissions()->pluck('name')->toArray());
} else {
    echo "User 'cashier@gmail.com' not found.\n";
}
