<?php

namespace App\Console\Commands;

use App\Models\AdjustmentVoucher;
use App\Models\ClaimAcceptance;
use App\Models\ClaimCreditNote;
use App\Models\ClaimItemReceipt;
use App\Models\CustomerClaim;
use App\Models\CustomerClaimRelease;
use App\Models\ExpenseVoucher;
use App\Models\IncomeVoucher;
use App\Models\InwardGatepass;
use App\Models\JournalVoucher;
use App\Models\PaymentVoucher;
use App\Models\Productbooking;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ReceiptsVoucher;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockHold;
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\StockReleaseVoucher;
use App\Models\StockTransfer;
use App\Models\StockWastage;
use App\Models\Voucher;
use App\Support\GroupContext;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillTransactionGroupIds extends Command
{
    protected $signature = 'groups:backfill {--dry-run : Show changes without saving}';

    protected $description = 'Backfill user_group_ids on transactions from linked party/warehouse groups';

    /** @var array<int, class-string<Model>> */
    protected array $models = [
        Productbooking::class,
        Sale::class,
        SaleReturn::class,
        Purchase::class,
        PurchaseReturn::class,
        InwardGatepass::class,
        StockWastage::class,
        StockTransfer::class,
        StockHoldVoucher::class,
        StockHold::class,
        StockReleaseVoucher::class,
        StockRelease::class,
        CustomerClaim::class,
        CustomerClaimRelease::class,
        ClaimAcceptance::class,
        ClaimCreditNote::class,
        ClaimItemReceipt::class,
        ReceiptsVoucher::class,
        PaymentVoucher::class,
        ExpenseVoucher::class,
        IncomeVoucher::class,
        JournalVoucher::class,
        AdjustmentVoucher::class,
        Voucher::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        foreach ($this->models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            /** @var Model $modelClass */
            $modelClass::withoutGlobalScopes()
                ->orderBy('id')
                ->chunkById(200, function ($records) use ($dryRun, &$updated, $modelClass) {
                    foreach ($records as $record) {
                        if (GroupContext::shouldSkipAutoResolve($record)) {
                            continue;
                        }

                        $resolved = GroupContext::resolveForModel($record);
                        if (empty($resolved)) {
                            continue;
                        }

                        $current = GroupContext::normalizeIds($record->user_group_ids ?? []);
                        sort($current);
                        $next = $resolved;
                        sort($next);

                        if ($current === $next) {
                            continue;
                        }

                        $updated++;

                        if ($dryRun) {
                            $this->line(sprintf(
                                '[dry-run] %s #%d: [%s] -> [%s]',
                                class_basename($modelClass),
                                $record->id,
                                implode(',', $current),
                                implode(',', $next)
                            ));
                            continue;
                        }

                        $record->timestamps = false;
                        $record->user_group_ids = $resolved;
                        $record->saveQuietly();
                    }
                });
        }

        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$updated} record(s).");

        return self::SUCCESS;
    }
}
