<?php

$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $originalContent = $content;

        // The pattern we injected:
        // <th>Created By</th>\n                                            <th>Status</th> (or Action)
        // Let's remove <th>Created By</th> completely first.
        $content = preg_replace('/<th>Created By<\/th>\s*/i', '', $content);
        
        // Remove the injected <td> block. 
        // It starts with <td>\s*@if(\$[^>]+->creator) and ends with </td>
        $content = preg_replace('/<td>\s*@if\(\$[a-zA-Z0-9_]+->creator\)\s*<span class="text-dark small">\{\{.*?\}\}<\/span>\s*@else\s*<span class="text-muted small">System<\/span>\s*@endif\s*<\/td>\s*/is', '', $content);
        
        // If it was changed, we need to carefully insert it back right before the Action/Status column!
        if ($content !== $originalContent) {
            // Re-insert TH
            $thReplaced = false;
            $content = preg_replace_callback('/(<th[^>]*>)\s*(Action|Status|Actions)\s*(<\/th>)/i', function($m) use (&$thReplaced) {
                if (!$thReplaced) {
                    $thReplaced = true;
                    return "<th>Created By</th>\n" . str_repeat(' ', 44) . $m[0];
                }
                return $m[0];
            }, $content);

            // Re-insert TD
            // We need to inject right before the Status or Action TD.
            // How do we identify the Status/Action TD?
            // Usually, the action TD contains a form with action=... or btn-info / btn-primary / fa-edit
            // We can match the last 2 TDs of the row.
            // A better way: find `</tr>` and go backwards. But there might be nested tables?
            // Since we know the variable name, let's extract it again:
            if (preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
                $varName = $matches[3]; 

                // Let's use a very targeted regex to find the end of the row
                // We want to insert the TD before the LAST or SECOND TO LAST TD.
                // Let's just find the closing </tr> of the loop.
                // Actually, the Action TD usually has class="text-center" and contains 'btn'
                // Let's search for the first TD that contains 'fa-send' or 'fa-pencil' or 'fa-edit' or 'fa-trash' or 'fa-eye' or 'Status' badge?
                // Wait, some tables have Status then Action. So we should insert before Status.
                // Status TD usually has `@if($...->status == ...)`
                
                // Let's just split the tbody contents by </tr>
                // For each row in tbody, we insert the TD before the td that contains the status badge or action buttons.
                
                // Let's manually edit stock_transfers first to make sure, then use a script if possible.
                file_put_contents($path, $content);
                echo "Reverted: $path\n";
            }
        }
    }
}
