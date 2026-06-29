<?php

$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $original = $content;

        // 1. Remove the improperly placed Created By <td>
        // Wait, the previous pattern was:
        // <td>\s*@if\(\$[a-zA-Z0-9_]+->creator\)\s*<span class="text-dark small">\{\{.*?\}\}<\/span>\s*@else\s*<span class="text-muted small">System<\/span>\s*@endif\s*<\/td>
        
        $content = preg_replace('/<td>\s*@if\(\$[a-zA-Z0-9_]+->creator\)\s*<span class="text-dark small">\{\{.*?\}\}<\/span>\s*@else\s*<span class="text-muted small">System<\/span>\s*@endif\s*<\/td>\s*/is', '', $content);

        if ($content !== $original) {
            echo "Cleaned up bad TD in: $path\n";
            
            // 2. Now find the variable name from @foreach
            if (preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
                $varName = $matches[3]; 

                // 3. Inject correctly before the FIRST <td class="text-center"> inside the tbody
                // Wait, to be extremely safe, we only replace the FIRST <td class="text-center"> that appears AFTER the @foreach
                
                // Let's split by @foreach
                $parts = explode('@foreach', $content, 2);
                if (count($parts) == 2) {
                    $beforeForeach = $parts[0];
                    $afterForeach = '@foreach' . $parts[1];
                    
                    // We only want to inject once per row. Wait, if we replace the FIRST <td class="text-center"> in the $afterForeach, it's the one in the loop!
                    // What if there is no text-center? Then we look for the <td... Action buttons.
                    
                    $tdReplaced = false;
                    $afterForeach = preg_replace_callback('/<td[^>]*class="[^"]*text-center[^"]*"[^>]*>/is', function($m) use (&$tdReplaced, $varName) {
                        if (!$tdReplaced) {
                            $tdReplaced = true;
                            $td = "<td>
                                        @if(\${$varName}->creator)
                                            <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                        @else
                                            <span class=\"text-muted small\">System</span>
                                        @endif
                                    </td>\n                                    ";
                            return $td . $m[0];
                        }
                        return $m[0];
                    }, $afterForeach, 1); // limit to 1 replacement

                    // If text-center was not found, fallback to the Action form or div
                    if (!$tdReplaced) {
                        $afterForeach = preg_replace_callback('/<td[^>]*>\s*(<div class="d-flex|<form action=)/is', function($m) use (&$tdReplaced, $varName) {
                            if (!$tdReplaced) {
                                $tdReplaced = true;
                                $td = "<td>
                                            @if(\${$varName}->creator)
                                                <span class=\"text-dark small\">{{ \${$varName}->creator->name }}</span>
                                            @else
                                                <span class=\"text-muted small\">System</span>
                                            @endif
                                        </td>\n                                    ";
                                return $td . $m[0];
                            }
                            return $m[0];
                        }, $afterForeach, 1);
                    }

                    if ($tdReplaced) {
                        file_put_contents($path, $beforeForeach . $afterForeach);
                        echo "Successfully injected before text-center: $path\n";
                    } else {
                        echo "Failed to find injection point in: $path\n";
                        // write back cleaned content anyway
                        file_put_contents($path, $content);
                    }
                }
            } else {
                echo "Could not find foreach variable in: $path\n";
                file_put_contents($path, $content);
            }
        }
    }
}
