<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$users = \App\Models\User::all();
foreach($users as $u) {
    echo json_encode(['name' => $u->name, 'email' => $u->email, 'roles' => $u->roles->pluck('name'), 'usertype' => $u->usertype, 'groups' => $u->userGroups()->pluck('user_groups.id')]) . "\n";
}
