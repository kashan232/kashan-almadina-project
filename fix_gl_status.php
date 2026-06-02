<?php
$file = 'app/Http/Controllers/GeneralLedgerController.php';
$content = file_get_contents($file);

// Find all ->whereBetween(DB::raw(...)) lines
$lines = explode("\n", $content);
$modified = false;

for ($i = 0; $i < count($lines); $i++) {
    // If we find a ->whereBetween line
    if (strpos($lines[$i], '->whereBetween(DB::raw(') !== false) {
        
        // Check if the previous few lines have a 'status' check
        $hasStatus = false;
        for ($j = $i; $j >= max(0, $i - 5); $j--) {
            if (strpos($lines[$j], "'status'") !== false || strpos($lines[$j], '"status"') !== false) {
                $hasStatus = true;
                break;
            }
            if (strpos($lines[$j], '::where') !== false || strpos($lines[$j], 'DB::table') !== false) {
                // reached the start of the query
                break;
            }
        }
        
        // Also check if it's a Sales query
        $isSales = false;
        for ($j = $i; $j >= max(0, $i - 5); $j--) {
            if (preg_match('/\bSale::/', $lines[$j])) {
                $isSales = true;
                break;
            }
        }

        if (!$hasStatus && !$isSales) {
            // Inject ->whereIn('status', ['posted', 'Posted'])
            $lines[$i] = str_replace('->whereBetween', "->whereIn('status', ['posted', 'Posted'])->whereBetween", $lines[$i]);
            $modified = true;
        }
    }
}

if ($modified) {
    file_put_contents($file, implode("\n", $lines));
    echo "Updated successfully.";
} else {
    echo "No modifications needed.";
}
