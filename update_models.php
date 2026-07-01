<?php
$dir = 'app/Models/';
$files = glob($dir . '*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    // match belongsTo(Warehouse::class) or belongsTo(\App\Models\Warehouse::class, ...)
    $pattern = '/(belongsTo\s*\(\s*(?:\\\\App\\\\Models\\\\)?Warehouse::class(?:[^)]*)\s*\))(?!\s*->withoutGlobalScopes)/';
    $new_content = preg_replace($pattern, '$1->withoutGlobalScopes()', $content);
    if ($content !== $new_content) {
        file_put_contents($file, $new_content);
        echo 'Updated ' . $file . "\n";
    }
}
