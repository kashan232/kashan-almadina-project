<?php
$f = 'c:\xampp\htdocs\Al-madina-bettery\app\Http\Controllers\GeneralLedgerController.php';
$c = file_get_contents($f);

$c = preg_replace('/function fetchSummaryTransactions\(\$type, \$id, \$start, \$end\)\s*\{/', "function fetchSummaryTransactions(\$type, \$id, \$start, \$end) {\n        \$typeArray = (\$type === 'customer') ? ['customer', 'walking', 'walkin'] : [\$type];", $c);

$c = preg_replace('/function fetchTransactions\(\$type, \$id, \$start, \$end\)\s*\{/', "function fetchTransactions(\$type, \$id, \$start, \$end) {\n        \$typeArray = (\$type === 'customer') ? ['customer', 'walking', 'walkin'] : [\$type];", $c);

$c = preg_replace('/function getOpeningBalance\(\$type, \$id, \$start\)\s*\{/', "function getOpeningBalance(\$type, \$id, \$start) {\n        \$typeArray = (\$type === 'customer') ? ['customer', 'walking', 'walkin'] : [\$type];", $c);

$c = str_replace("where('partyType', \$type)", "whereIn('partyType', \$typeArray)", $c);
$c = str_replace("where('party_type', \$type)", "whereIn('party_type', \$typeArray)", $c);
$c = str_replace("where('type', \$type)", "whereIn('type', \$typeArray)", $c);

file_put_contents($f, $c);
echo "Done";
