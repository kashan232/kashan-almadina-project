<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jvs = \App\Models\JournalVoucher::where('jvid', 'like', 'PJ-%')->get();
foreach ($jvs as $jv) {
    $remark = $jv->remarks;
    if (strpos($remark, 'WHT (Tax):') !== false) {
        // Try to get account from party_id json
        $pIds = json_decode($jv->party_id, true) ?? [];
        if (isset($pIds[1])) {
            $title = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $pIds[1])->value('title');
            if ($title) {
                $jv->remarks = $title;
                $jv->save();
            }
        }
    } elseif (strpos($remark, 'Total Discount -') !== false) {
        $pIds = json_decode($jv->party_id, true) ?? [];
        if (isset($pIds[1])) {
            $title = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $pIds[1])->value('title');
            if ($title) {
                $jv->remarks = $title;
                $jv->save();
            }
        }
    }
}
echo "Fixed DB";
