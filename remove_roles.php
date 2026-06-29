<?php
$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Match the <br> and the span that follows it for the role
        // Specifically: <br><span class="text-muted" style="font-size: 9px;">({{ ... }})</span>
        $newContent = preg_replace('/<br>\s*<span class="text-muted" style="font-size:\s*9px;">\(\{\{.*?\}\}\)<\/span>/i', '', $content);
        
        // Sometimes the user role in the original sales view was:
        // <br><span class="text-muted" style="font-size: 9px;">({{ $sale->user->roles->first()->name ?? 'User' }})</span>
        // Let's broaden the regex slightly just in case.
        $newContent = preg_replace('/<br>\s*<span class="text-muted"[^>]*>\(\{\{.*?\}\}\)<\/span>/i', '', $newContent);

        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            echo "Removed role from: $path\n";
            $count++;
        }
    }
}
echo "Total files updated: $count\n";
