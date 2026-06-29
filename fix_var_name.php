<?php

$dir = new RecursiveDirectoryIterator('resources/views/admin_panel');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $original = $content;

        // Clean up the previously injected TD which might have the wrong variable name!
        // The pattern is: <td>\s*@if\(\$[a-zA-Z0-9_]+->creator\)\s*<span class="text-dark small">\{\{.*?\}\}<\/span>\s*@else\s*<span class="text-muted small">System<\/span>\s*@endif\s*<\/td>\s*
        $content = preg_replace('/<td>\s*@if\(\$[a-zA-Z0-9_]+->creator\)\s*<span class="text-dark small">\{\{.*?\}\}<\/span>\s*@else\s*<span class="text-muted small">System<\/span>\s*@endif\s*<\/td>\s*/is', '', $content);

        // Now we need to find the CORRECT variable name.
        // It's the @foreach that occurs right after <tbody>
        if (preg_match('/<tbody[^>]*>[\s\S]*?@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
            $varName = $matches[3]; 
            
            // Now we inject it.
            // But wait, since we just removed it from EVERYWHERE, we need to inject it before the FIRST <td class="text-center"> inside the tbody!
            // Let's just find the @foreach block and do it there.
            
            $parts = explode('<tbody', $content, 2);
            if (count($parts) == 2) {
                $beforeTbody = $parts[0];
                $afterTbody = '<tbody' . $parts[1];
                
                $tdReplaced = false;
                $afterTbody = preg_replace_callback('/<td[^>]*class="[^"]*text-center[^"]*"[^>]*>/is', function($m) use (&$tdReplaced, $varName) {
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
                }, $afterTbody, 1);

                if (!$tdReplaced) {
                    $afterTbody = preg_replace_callback('/<td[^>]*>\s*(<div class="d-flex|<form action=)/is', function($m) use (&$tdReplaced, $varName) {
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
                    }, $afterTbody, 1);
                }

                if ($tdReplaced) {
                    file_put_contents($path, $beforeTbody . $afterTbody);
                    echo "Corrected var $$varName in: $path\n";
                } else {
                    echo "Failed to find injection point in: $path\n";
                    file_put_contents($path, $content);
                }
            } else {
                echo "No tbody found in: $path\n";
                file_put_contents($path, $content);
            }
        } else {
            // Write cleaned content back if no match found
            file_put_contents($path, $content);
        }
    }
}
