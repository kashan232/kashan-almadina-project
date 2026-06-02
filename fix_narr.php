<?php
$file = 'app/Http/Controllers/GeneralLedgerController.php';
$content = file_get_contents($file);

$content = str_replace(
    '$desc = ($accName ? "$accName " : "") . ($narrText ? " ($narrText)" : ($rv->remarks ?? \'\'));',
    '$desc = $narrText ?: ($rv->remarks ?? \'Receipt Voucher\');',
    $content
);

$content = str_replace(
    '\'desc\' => ($partyName ? "$partyName : " : "") . ($rowNarr ?: ($rv->remarks ?? \'Receipt\')),',
    '\'desc\' => $rowNarr ?: ($rv->remarks ?? \'Receipt\'),',
    $content
);

$content = str_replace(
    '\'desc\' => ($partyName ? "$partyName : " : "") . ($rowNarr ?: ($pv->remarks ?? \'Payment\')),',
    '\'desc\' => $rowNarr ?: ($pv->remarks ?? \'Payment\'),',
    $content
);

file_put_contents($file, $content);
echo "Updated";
