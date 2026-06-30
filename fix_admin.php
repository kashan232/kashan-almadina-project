<?php
$dir1 = new RecursiveDirectoryIterator('C:\xampp\htdocs\Al-madina-bettery\app');
$ite1 = new RecursiveIteratorIterator($dir1);
$files = new RegexIterator($ite1, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$dir2 = new RecursiveDirectoryIterator('C:\xampp\htdocs\Al-madina-bettery\resources\views');
$ite2 = new RecursiveIteratorIterator($dir2);
$files2 = new RegexIterator($ite2, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$all_files = array_merge(iterator_to_array($files), iterator_to_array($files2));

foreach($all_files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $new_content = str_replace([
        " || Auth::user()->usertype == 'admin'",
        " || \$user->usertype == 'admin'",
        " || \Illuminate\Support\Facades\Auth::user()->usertype == 'admin'",
        " || auth()->user()->usertype == 'admin'"
    ], "", $content);
    
    if ($content !== $new_content) {
        file_put_contents($path, $new_content);
        echo "Updated: $path\n";
    }
}
