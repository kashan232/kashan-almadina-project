<?php

namespace App\Services;

use App\Models\AdjustmentVoucher;
use App\Models\ClaimAcceptance;
use App\Models\ClaimCreditNote;
use App\Models\ClaimItemReceipt;
use App\Models\CustomerClaim;
use App\Models\ExpenseVoucher;
use App\Models\IncomeVoucher;
use App\Models\InwardGatepass;
use App\Models\JournalVoucher;
use App\Models\PaymentVoucher;
use App\Models\Productbooking;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ReceiptsVoucher;
use App\Models\SaleReturn;
use App\Models\StockAdjustment;
use App\Models\StockHoldVoucher;
use App\Models\StockReleaseVoucher;
use App\Models\StockTransfer;
use App\Models\StockWastage;
use App\Models\User;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnpostEntriesReportBuilder
{
    /** @var array<int, string>|null */
    private ?array $userNames = null;

    public function build(Request $request): array
    {
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $rows = collect();

        foreach ($this->sources() as $source) {
            $rows = $rows->merge($this->collectSource($source, $fromDate, $toDate));
        }

        $rows = $rows->sortBy([
            fn ($row) => $row['date_sort'],
            fn ($row) => $row['definition'],
            fn ($row) => (int) $row['record_id'],
        ])->values();

        return [
            'rows' => $rows,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    private function sources(): array
    {
        return [
            ['definition' => 'Purchase', 'model' => Purchase::class, 'scope' => 'unposted', 'date_columns' => ['current_date', 'entry_date']],
            ['definition' => 'Purchase Return', 'model' => PurchaseReturn::class, 'scope' => 'unposted', 'date_columns' => ['current_date', 'entry_date']],
            ['definition' => 'Sales', 'model' => Productbooking::class, 'scope' => 'all', 'date_columns' => ['entry_date', 'created_at']],
            ['definition' => 'Sale Return', 'model' => SaleReturn::class, 'scope' => 'unposted', 'date_columns' => ['current_date', 'entry_date']],
            ['definition' => 'Inward Gatepass', 'model' => InwardGatepass::class, 'scope' => 'unposted', 'date_columns' => ['gatepass_date', 'entry_date']],
            ['definition' => 'Stock Hold', 'model' => StockHoldVoucher::class, 'scope' => 'unposted', 'date_columns' => ['entry_date', 'date', 'created_at']],
            ['definition' => 'Stock Release', 'model' => StockReleaseVoucher::class, 'scope' => 'unposted', 'date_columns' => ['date', 'entry_date', 'created_at']],
            ['definition' => 'Stock Transfer', 'model' => StockTransfer::class, 'scope' => 'unposted', 'date_columns' => ['entry_date', 'date', 'created_at']],
            ['definition' => 'Stock Wastage', 'model' => StockWastage::class, 'scope' => 'unposted', 'date_columns' => ['date', 'entry_date', 'created_at']],
            ['definition' => 'Warehouse Stock', 'model' => WarehouseStock::class, 'scope' => 'unposted', 'date_columns' => ['entry_date', 'created_at']],
            ['definition' => 'Customer Claim', 'model' => CustomerClaim::class, 'scope' => 'not_posted', 'date_columns' => ['claim_date', 'entry_date']],
            ['definition' => 'Claim Acceptance', 'model' => ClaimAcceptance::class, 'scope' => 'not_posted', 'date_columns' => ['date', 'entry_date']],
            ['definition' => 'Claim Item Receipt', 'model' => ClaimItemReceipt::class, 'scope' => 'not_posted', 'date_columns' => ['date', 'entry_date']],
            ['definition' => 'Claim Credit Note', 'model' => ClaimCreditNote::class, 'scope' => 'not_posted', 'date_columns' => ['date', 'entry_date']],
            ['definition' => 'Receipt Voucher', 'model' => ReceiptsVoucher::class, 'scope' => 'voucher', 'date_columns' => ['receipt_date', 'entry_date']],
            ['definition' => 'Payment Voucher', 'model' => PaymentVoucher::class, 'scope' => 'draft', 'date_columns' => ['payment_date', 'entry_date']],
            ['definition' => 'Expense Voucher', 'model' => ExpenseVoucher::class, 'scope' => 'draft', 'date_columns' => ['expense_date', 'entry_date']],
            ['definition' => 'Income Voucher', 'model' => IncomeVoucher::class, 'scope' => 'draft', 'date_columns' => ['income_date', 'entry_date']],
            ['definition' => 'Journal Voucher', 'model' => JournalVoucher::class, 'scope' => 'draft', 'date_columns' => ['journal_date', 'entry_date']],
            ['definition' => 'Adjustment Voucher', 'model' => AdjustmentVoucher::class, 'scope' => 'draft', 'date_columns' => ['date', 'entry_date']],
            ['definition' => 'Stock Adjustment', 'model' => StockAdjustment::class, 'scope' => 'not_posted', 'date_columns' => ['date', 'entry_date']],
        ];
    }

    private function collectSource(array $source, ?string $fromDate, ?string $toDate): Collection
    {
        $modelClass = $source['model'];
        $query = in_array(\App\Traits\GroupIsolation::class, class_uses_recursive($modelClass), true)
            ? $modelClass::withoutGlobalScopes()
            : $modelClass::query();

        $this->applyUnpostedScope($query, $source['scope']);

        $rows = collect();
        $query->orderBy('id')->chunkById(500, function ($records) use (&$rows, $source, $fromDate, $toDate) {
            foreach ($records as $record) {
                $date = $this->pickDate($record, $source['date_columns']);
                if (!$this->dateInPeriod($date, $fromDate, $toDate)) {
                    continue;
                }

                $rows->push([
                    'definition' => $source['definition'],
                    'record_id' => (string) ($record->id ?? ''),
                    'date' => $date ? Carbon::parse($date)->format('d-m-y') : '',
                    'date_sort' => $date ?: '0000-00-00',
                    'user_name' => $this->userName($record->created_by ?? null),
                    'view_url' => $this->viewUrl($source['definition'], (string) ($record->id ?? '')),
                ]);
            }
        });

        return $rows;
    }

    private function applyUnpostedScope($query, string $scope): void
    {
        match ($scope) {
            'unposted' => $query->where('status', 'Unposted'),
            'not_posted' => $query->where('status', '!=', 'Posted'),
            'draft' => $query->whereIn('status', ['draft', 'Draft']),
            'voucher' => $query->whereNotIn('status', ['posted', 'Posted']),
            'all' => null,
            default => null,
        };
    }

    private function dateInPeriod(?string $date, ?string $fromDate, ?string $toDate): bool
    {
        if (empty($date)) {
            return empty($fromDate) && empty($toDate);
        }

        if ($fromDate && $date < $fromDate) {
            return false;
        }

        if ($toDate && $date > $toDate) {
            return false;
        }

        return true;
    }

    private function pickDate($model, array $columns): ?string
    {
        foreach ($columns as $col) {
            $val = data_get($model, $col);
            if (!empty($val)) {
                return Carbon::parse($val)->toDateString();
            }
        }

        if (!empty($model->created_at)) {
            return Carbon::parse($model->created_at)->toDateString();
        }

        return null;
    }

    private function userName($userId): string
    {
        if (empty($userId)) {
            return '';
        }

        if ($this->userNames === null) {
            $this->userNames = User::pluck('name', 'id')->all();
        }

        return $this->userNames[$userId] ?? '';
    }

    private function viewUrl(string $definition, string $recordId): ?string
    {
        if ($recordId === '') {
            return null;
        }

        return match ($definition) {
            'Purchase' => route('purchase.edit', $recordId),
            'Purchase Return' => route('purchase.return.edit', $recordId),
            'Sales' => route('editBooking.index', $recordId),
            'Sale Return' => route('sale.return.edit', $recordId),
            'Inward Gatepass' => route('InwardGatepass.edit', $recordId),
            'Stock Hold' => route('stock-holds.edit', $recordId),
            'Stock Release' => route('stock-holds.release.edit', $recordId),
            'Stock Transfer' => route('stock_transfers.edit', $recordId),
            'Stock Wastage' => route('stock-wastage.edit', $recordId),
            'Warehouse Stock' => route('warehouse_stocks.index', ['view' => 'history']),
            'Customer Claim' => route('customer-claims.edit', $recordId),
            'Claim Acceptance' => route('claim-acceptance.edit', $recordId),
            'Claim Item Receipt' => route('claim-item-receipt.edit', $recordId),
            'Claim Credit Note' => route('claim-credit-note.edit', $recordId),
            'Receipt Voucher' => route('recepit-vochers', $recordId),
            'Payment Voucher' => route('Payment-vochers', $recordId),
            'Expense Voucher' => route('expense-vochers', $recordId),
            'Income Voucher' => route('income-vochers', $recordId),
            'Journal Voucher' => route('journal-vochers', $recordId),
            'Adjustment Voucher' => route('adjustment-vochers', $recordId),
            'Stock Adjustment' => route('warehouse_stocks.edit', $recordId),
            default => null,
        };
    }
}
