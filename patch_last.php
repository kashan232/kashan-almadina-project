<?php
$path = 'resources/views/admin_panel/vochers/all_recepit_vochers.blade.php';

if (file_exists($path)) {
    $content = file_get_contents($path);
    
    if (strpos($content, 'Created By') === false) {
        if (preg_match('/@foreach\s*\(\s*\$([a-zA-Z0-9_]+)\s+as\s+(?:\$([a-zA-Z0-9_]+)\s*=>\s*)?\$([a-zA-Z0-9_]+)\s*\)/i', $content, $matches)) {
            $varName = $matches[3]; 

            $thReplaced = false;
            $content = preg_replace_callback('/(<th[^>]*>)\s*(Action|Status|Actions)\s*(<\/th>)/i', function($m) use (&$thReplaced) {
                if (!$thReplaced) {
                    $thReplaced = true;
                    return "<th>Created By</th>\n                                            " . $m[0];
                }
                return $m[0];
            }, $content);

            $tdReplaced = false;
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
            }
        }
    }
}
