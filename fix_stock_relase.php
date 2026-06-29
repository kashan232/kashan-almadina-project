<?php
$path = 'resources/views/admin_panel/stock_hold/stock_relase_list.blade.php';
$content = file_get_contents($path);

// Insert it before the Status <td>
$correctTd = "<td>
                                                @if(\$v->creator)
                                                    <span class=\"text-dark small\">{{ \$v->creator->name }}</span>
                                                @else
                                                    <span class=\"text-muted small\">System</span>
                                                @endif
                                            </td>\n$1";

$content = preg_replace('/(<td class="text-center">\s*@if\(\$v->status == \'Posted\'\))/is', $correctTd, $content);

file_put_contents($path, $content);
echo "Fixed $path\n";
