<?php

$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        // Skip if it doesn't have <th>Created By</th>
        if (strpos($content, '<th>Created By</th>') === false) {
            continue;
        }

        // Check if it already has the <td> for creator
        if (strpos($content, '->creator') !== false && strpos($content, 'text-muted small">System') !== false) {
            // Already has it, maybe it's stock_transfers which I fixed manually.
            echo "Skipping (already has TD): $path\n";
            continue;
        }

        if (preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
            $varName = $matches[3]; 

            // Find the <th> index from the end
            // Let's just find the closing </tr> of the thead, and count <th> from the end
            if (preg_match('/<th>Created By<\/th>\s*(<th[^>]*>.*?<\/th>\s*)*(<\/tr>)/is', $content, $thMatches)) {
                // Number of THs after Created By
                preg_match_all('/<th/i', $thMatches[1], $thCountMatches);
                $thsAfter = count($thCountMatches[0]);

                // Now we need to insert the TD into the tbody row.
                // The tbody row ends with </tr>. We need to find the </tr> inside the @foreach loop.
                // We will match the last ($thsAfter) TDs before </tr> and insert before them.
                
                $tdPattern = str_repeat('\s*<td[^>]*>[\s\S]*?<\/td>', $thsAfter);
                
                // Match the end of the row: (tds after)(</tr>)
                // We will use preg_replace_callback to replace the END of the row inside the foreach
                $rowEndPattern = '/(' . $tdPattern . '\s*<\/tr>)/is';

                $tdReplaced = false;
                $content = preg_replace_callback($rowEndPattern, function($m) use (&$tdReplaced, $varName) {
                    if (!$tdReplaced) {
                        $tdReplaced = true;
                        $td = "<td>
                                                @if(\${$varName}->creator)
                                                    <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                                @else
                                                    <span class=\"text-muted small\">System</span>
                                                @endif
                                            </td>\n                                            ";
                        return $td . $m[1];
                    }
                    return $m[0];
                }, $content);

                if ($tdReplaced) {
                    file_put_contents($path, $content);
                    echo "Fixed successfully: $path (inserted before $thsAfter columns)\n";
                } else {
                    echo "Failed to replace TD in: $path\n";
                }

            } else {
                echo "Could not match TH structure in: $path\n";
            }
        }
    }
}
