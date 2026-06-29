<?php
$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'index.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Skip if already has Created By
        if (strpos($content, 'Created By') !== false) {
            echo "Skipping (already has Created By): $path\n";
            continue;
        }

        // Find the variable name in @foreach
        // Example: @foreach($items as $item) or @foreach($items as $key => $item)
        if (preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
            $varName = $matches[3]; // The item variable
        } else {
            echo "Could not find @foreach in $path\n";
            continue;
        }

        // Add <th>Created By</th> before Action or Status
        $thReplaced = false;
        $content = preg_replace_callback('/(<th[^>]*>)\s*(Action|Status)\s*(<\/th>)/i', function($m) use (&$thReplaced) {
            if (!$thReplaced) {
                $thReplaced = true;
                return "<th>Created By</th>\n                                            " . $m[0];
            }
            return $m[0];
        }, $content);

        // Add <td>Created By</td> before the corresponding action/status td
        $tdReplaced = false;
        // The action TD usually has buttons or status has badges.
        // It's tricky to find the EXACT td. But we know it's at the end of the row.
        // Let's find the </td>\s*</tr> and insert before the last or second to last </td>.
        // Let's just find the closing </tr> of the row inside the tbody!
        // Actually, we can use a simpler approach. If we replace the TH before "Action" or "Status",
        // we can find the TD that contains "Action" buttons (usually has form, btn, fa-edit, etc.)
        // Or we can just insert the <td> snippet into the blade manually for safety?
        // Let's try to find the Action TD by looking for 'btn' and 'href' or 'form' inside a TD.
        
        // A better approach: Find the row inside the foreach, and insert the TD before the last TD.
        $content = preg_replace_callback('/(<td[^>]*>[\s\S]*?(?:btn|fa-edit|fa-eye|fa-trash|badge)[\s\S]*?<\/td>\s*<\/tr>)/i', function($m) use (&$tdReplaced, $varName) {
            if (!$tdReplaced) {
                $tdReplaced = true;
                $td = "<td>
                                                @if(\${$varName}->creator)
                                                    <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                                    <br><span class=\"text-muted\" style=\"font-size: 9px;\">({{ \${$varName}->creator->roles->first()->name ?? \${$varName}->creator->usertype ?? 'User' }})</span>
                                                @else
                                                    <span class=\"text-muted small\">System</span>
                                                @endif
                                            </td>\n                                            ";
                return $td . $m[0];
            }
            return $m[0];
        }, $content);

        if ($thReplaced && $tdReplaced) {
            file_put_contents($path, $content);
            echo "Patched successfully: $path (var: $$varName)\n";
        } else {
            echo "Failed to patch both TH and TD in: $path\n";
        }
    }
}
