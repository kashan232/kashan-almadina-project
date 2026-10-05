<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdjustmentVoucher;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;
use App\Services\PartyLedgerService;

class FixRolledbackAdjustments extends Command
{
    protected $signature = 'ledger:fix-rolledback-adjustments';
    protected $description = 'Find past rolled-back Adjustment Vouchers and apply missing reversals to Customer & Vendor Ledgers';

    public function handle()
    {
        $this->info("Scanning for past rolled-back Adjustment Vouchers...");
        $res = self::runFix();
        $this->info($res['message']);
        foreach ($res['fixed'] as $item) {
            $this->line(" - " . $item);
        }
        return 0;
    }

    public static function runFix(): array
    {
        $vouchers = AdjustmentVoucher::whereIn('status', ['draft', 'Draft', 'Unposted', 'unposted'])->get();
        $ledger = app(PartyLedgerService::class);
        $fixedCount = 0;
        $details = [];
        $affectedParties = [];

        foreach ($vouchers as $v) {
            $avid = $v->avid ?: $v->id;
            $amount = (float) $v->total_amount;
            $date = $v->entry_date ?? substr((string)$v->created_at, 0, 10);
            $descMarker = "Adjustment Voucher #" . $avid;

            // 1. Check Header Party (Source)
            $pType = $v->party_type;
            $pId = (int)$v->party_id;

            if ($pId > 0 && in_array($pType, ['vendor', 'customer', 'walkin'], true)) {
                $normType = $ledger->normalizePartyType($pType);
                $resolved = $ledger->resolveLedger($normType);
                if ($resolved) {
                    [$modelClass, $col] = $resolved;
                    
                    // Check if posted row exists for this AV
                    $hasPost = $modelClass::where($col, $pId)
                        ->where('description', 'LIKE', '%' . $descMarker . '%')
                        ->where('description', 'NOT LIKE', '%Rollback%')
                        ->exists();

                    if ($hasPost) {
                        // Check if rollback reversal row exists
                        $hasRev = $modelClass::where($col, $pId)
                            ->where('description', 'LIKE', '%Rollback%' . $descMarker . '%')
                            ->exists();

                        if (!$hasRev) {
                            $ledger->appendReversal($normType, $pId, $amount, 0, $date, "Rollback Adjustment Voucher #$avid");
                            $fixedCount++;
                            $details[] = "Header Party ($normType #$pId): Added Rollback Reversal for AV #$avid (Credit $amount)";
                            $affectedParties[] = [$normType, $pId];
                        }
                    }
                }
            }

            // 2. Check Row Accounts (Destinations)
            $accHeads = json_decode($v->account_head, true) ?? [];
            $accIds = json_decode($v->account_id, true) ?? [];
            $amounts = json_decode($v->amount, true) ?? [];

            foreach ($accIds as $idx => $accId) {
                $rowAmount = (float)($amounts[$idx] ?? 0);
                if ($rowAmount <= 0) continue;
                $rType = $accHeads[$idx] ?? '';
                $rId = (int)$accId;

                if ($rId > 0 && in_array($rType, ['vendor', 'customer', 'walkin'], true)) {
                    $normRowType = $ledger->normalizePartyType($rType);
                    $resolvedRow = $ledger->resolveLedger($normRowType);
                    if ($resolvedRow) {
                        [$modelClass, $col] = $resolvedRow;
                        
                        $hasPostRow = $modelClass::where($col, $rId)
                            ->where('description', 'LIKE', '%' . $descMarker . '%')
                            ->where('description', 'NOT LIKE', '%Rollback%')
                            ->exists();

                        if ($hasPostRow) {
                            $hasRevRow = $modelClass::where($col, $rId)
                                ->where('description', 'LIKE', '%Rollback%' . $descMarker . '%')
                                ->exists();

                            if (!$hasRevRow) {
                                $ledger->appendReversal($normRowType, $rId, 0, $rowAmount, $date, "Rollback Adjustment Voucher #$avid");
                                $fixedCount++;
                                $details[] = "Row Party ($normRowType #$rId): Added Rollback Reversal for AV #$avid (Debit $rowAmount)";
                                $affectedParties[] = [$normRowType, $rId];
                            }
                        }
                    }
                }
            }
        }

        // Recalculate ledger chain for unique affected parties
        $uniqueParties = array_map("unserialize", array_unique(array_map("serialize", $affectedParties)));
        foreach ($uniqueParties as [$pType, $pId]) {
            $ledger->recalculateChain($pType, $pId);
        }

        $msg = $fixedCount > 0 
            ? "Successfully applied missing reversals for $fixedCount party entries."
            : "All rolled-back Adjustment Vouchers are already fully synced and up to date!";

        return [
            'success' => true,
            'fixed_count' => $fixedCount,
            'message' => $msg,
            'fixed' => $details
        ];
    }
}
