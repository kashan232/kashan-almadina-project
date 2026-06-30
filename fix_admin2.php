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
    if (strpos($path, 'GroupIsolationScope.php') !== false) continue; // Already manually fixed
    $content = file_get_contents($path);
    
    // Replace Auth::user()->roles->pluck('name')->contains('Admin')
    $new_content = str_replace(
        "Auth::user()->roles->pluck('name')->contains('Admin')",
        "Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1",
        $content
    );
    
    // Replace \Illuminate\Support\Facades\Auth::user()->roles->pluck('name')->contains('Admin')
    $new_content = str_replace(
        "\Illuminate\Support\Facades\Auth::user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1",
        "\Illuminate\Support\Facades\Auth::user()->roles->pluck('name')->contains('Admin') || \Illuminate\Support\Facades\Auth::id() == 1",
        $new_content
    );
    
    // Replace auth()->user()->roles->pluck('name')->contains('Admin')
    $new_content = str_replace(
        "auth()->user()->roles->pluck('name')->contains('Admin') || Auth::id() == 1",
        "auth()->user()->roles->pluck('name')->contains('Admin') || auth()->id() == 1",
        $new_content
    );
    
    // Fix double fallbacks if they accidentally got added multiple times
    $new_content = str_replace(" || Auth::id() == 1 || Auth::id() == 1", " || Auth::id() == 1", $new_content);
    $new_content = str_replace(" || \Illuminate\Support\Facades\Auth::id() == 1 || Auth::id() == 1", " || \Illuminate\Support\Facades\Auth::id() == 1", $new_content);
    $new_content = str_replace(" || auth()->id() == 1 || Auth::id() == 1", " || auth()->id() == 1", $new_content);
    
    if ($content !== $new_content) {
        file_put_contents($path, $new_content);
        echo "Updated: $path\n";
    }
}
