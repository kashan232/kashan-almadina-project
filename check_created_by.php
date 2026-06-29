<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = DB::select('SHOW TABLES');

foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    $cols = DB::select("SHOW COLUMNS FROM `{$tableName}`");
    $hasCreatedBy = false;
    foreach($cols as $col) {
        if ($col->Field == 'created_by') $hasCreatedBy = true;
    }
    if ($hasCreatedBy) {
        echo $tableName . " HAS created_by\n";
    }
}
