<?php

namespace App\Services;

use App\Models\CustomerLedger;
use App\Models\SubCustomerLedger;
use App\Models\VendorLedger;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified party ledger: closing = previous + debit - credit (positive = DR, negative = CR).
 * Always appends rows — never mutates prior transaction rows except opening-balance sync.
 */
class PartyLedgerService
{
    /** @return array{0: class-string<Model>, 1: string, 2: string}|null */
    public function resolveLedger(string $partyType, ?int $partyId = null): ?array
    {
        $type = strtolower(class_basename($partyType));

        if (in_array($type, ['vendor'], true)) {
            return [VendorLedger::class, 'vendor_id', 'vendor'];
        }

        if (in_array($type, ['customer', 'walkin', 'walking', 'main customer', 'walking customer'], true)) {
            return [CustomerLedger::class, 'customer_id', 'customer'];
        }

        if (in_array($type, ['subcustomer'], true)) {
            return [SubCustomerLedger::class, 'sub_customer_id', 'subcustomer'];
        }

        return null;
    }

    public function latestClosing(string $partyType, int $partyId): float
    {
        $resolved = $this->resolveLedger($partyType);
        if (!$resolved) {
            return 0.0;
        }

        [$model, $col] = $resolved;

        return (float) ($model::where($col, $partyId)->latest('id')->value('closing_balance') ?? 0);
    }

    /**
     * @param array{date?: string, description: string, debit?: float, credit?: float, opening_balance?: float, admin_or_user_id?: int} $entry
     */
    public function append(string $partyType, int $partyId, array $entry): ?Model
    {
        $resolved = $this->resolveLedger($partyType);
        if (!$resolved) {
            return null;
        }

        [$model, $col] = $resolved;

        $debit = round((float) ($entry['debit'] ?? 0), 2);
        $credit = round((float) ($entry['credit'] ?? 0), 2);
        if ($debit == 0.0 && $credit == 0.0) {
            return null;
        }

        $prev = $this->latestClosing($partyType, $partyId);

        return $model::create([
            $col => $partyId,
            'admin_or_user_id' => $entry['admin_or_user_id'] ?? auth()->id(),
            'date' => $entry['date'] ?? now()->toDateString(),
            'description' => $entry['description'],
            'opening_balance' => $entry['opening_balance'] ?? 0,
            'previous_balance' => $prev,
            'debit' => $debit,
            'credit' => $credit,
            'closing_balance' => round($prev + $debit - $credit, 2),
        ]);
    }

    public function postOpeningBalance(string $partyType, int $partyId, float $opening, ?int $userId = null): ?Model
    {
        if ($opening == 0.0) {
            return null;
        }

        $debit = $opening > 0 ? $opening : 0;
        $credit = $opening < 0 ? abs($opening) : 0;

        return $this->append($partyType, $partyId, [
            'date' => now()->toDateString(),
            'description' => 'Opening Balance',
            'opening_balance' => $opening,
            'debit' => $debit,
            'credit' => $credit,
            'admin_or_user_id' => $userId ?? auth()->id(),
        ]);
    }

    /** Update opening row and recalculate all subsequent closing balances. */
    public function syncOpeningBalance(string $partyType, int $partyId, float $newOpening, ?int $userId = null): void
    {
        $resolved = $this->resolveLedger($partyType);
        if (!$resolved) {
            return;
        }

        [$model, $col] = $resolved;

        $first = $model::where($col, $partyId)->orderBy('id')->first();
        $debit = $newOpening > 0 ? $newOpening : 0;
        $credit = $newOpening < 0 ? abs($newOpening) : 0;

        if ($first) {
            $first->update([
                'opening_balance' => $newOpening,
                'debit' => $debit,
                'credit' => $credit,
                'previous_balance' => 0,
                'closing_balance' => round($newOpening, 2),
                'description' => $first->description ?: 'Opening Balance',
                'date' => $first->date ?? now()->toDateString(),
                'admin_or_user_id' => $first->admin_or_user_id ?? ($userId ?? auth()->id()),
            ]);
        } elseif ($newOpening != 0.0) {
            $this->postOpeningBalance($partyType, $partyId, $newOpening, $userId);
            return;
        }

        $this->recalculateChain($partyType, $partyId);
    }

    public function recalculateChain(string $partyType, int $partyId): void
    {
        $resolved = $this->resolveLedger($partyType);
        if (!$resolved) {
            return;
        }

        [$model, $col] = $resolved;

        $rows = $model::where($col, $partyId)->orderBy('id')->get();
        $running = 0.0;

        foreach ($rows as $row) {
            $row->previous_balance = round($running, 2);
            $row->closing_balance = round($running + (float) $row->debit - (float) $row->credit, 2);
            $row->save();
            $running = (float) $row->closing_balance;
        }
    }

    /** PJ — party credited by net amount (balance decreases). */
    public function postPurchaseCredit(string $partyType, int $partyId, float $netAmount, string $date, string $invoiceNo): ?Model
    {
        return $this->append($partyType, $partyId, [
            'date' => $date,
            'description' => 'Purchase: ' . $invoiceNo,
            'debit' => 0,
            'credit' => $netAmount,
        ]);
    }

    /** Reverse PJ — debit party by net amount. */
    public function reversePurchaseCredit(string $partyType, int $partyId, float $netAmount, string $date, string $invoiceNo, string $label = 'Purchase Reversal'): ?Model
    {
        return $this->append($partyType, $partyId, [
            'date' => $date,
            'description' => $label . ': ' . $invoiceNo,
            'debit' => $netAmount,
            'credit' => 0,
        ]);
    }

    /** PRJ — party debited by net amount (reduces payable / increases DR side). */
    public function postPurchaseReturnDebit(string $partyType, int $partyId, float $netAmount, string $date, string $invoiceNo): ?Model
    {
        return $this->append($partyType, $partyId, [
            'date' => $date,
            'description' => 'Purchase Return: ' . $invoiceNo,
            'debit' => $netAmount,
            'credit' => 0,
        ]);
    }

    /** Reverse PRJ — credit party by net amount. */
    public function reversePurchaseReturnDebit(string $partyType, int $partyId, float $netAmount, string $date, string $invoiceNo, string $label = 'Purchase Return Reversal'): ?Model
    {
        return $this->append($partyType, $partyId, [
            'date' => $date,
            'description' => $label . ': ' . $invoiceNo,
            'debit' => 0,
            'credit' => $netAmount,
        ]);
    }

    /** Append equal-and-opposite reversal for rollback/unpost. */
    public function appendReversal(string $partyType, int $partyId, float $originalDebit, float $originalCredit, string $date, string $description): ?Model
    {
        return $this->append($partyType, $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => $originalCredit,
            'credit' => $originalDebit,
        ]);
    }

    public function partyTypeFromPurchasable(?string $purchasableType): string
    {
        return $this->normalizePartyType(class_basename((string) $purchasableType));
    }

    public function normalizePartyType(string $partyType): string
    {
        $type = strtolower(trim($partyType));

        if (in_array($type, ['walking', 'walkin', 'walking customer', 'main customer'], true)) {
            return 'customer';
        }

        return $type;
    }

    /** SJ — debit sale amount, credit discount + receipts (matches GL net). */
    public function postSale(string $partyType, int $partyId, float $debit, float $credit, string $date, string $invoiceNo): ?Model
    {
        if ($debit == 0.0 && $credit == 0.0) {
            return null;
        }

        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => 'Sale: ' . $invoiceNo,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    public function reverseSale(string $partyType, int $partyId, float $debit, float $credit, string $date, string $invoiceNo, string $label = 'Rollback Sale'): ?Model
    {
        return $this->appendReversal($this->normalizePartyType($partyType), $partyId, $debit, $credit, $date, $label . ': ' . $invoiceNo);
    }

    /** SRJ — credit sub_total2, debit discount (matches GL). */
    public function postSaleReturn(string $partyType, int $partyId, float $credit, float $debit, string $date, string $invoiceNo): ?Model
    {
        if ($debit == 0.0 && $credit == 0.0) {
            return null;
        }

        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => 'Sale Return: ' . $invoiceNo,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    public function reverseSaleReturn(string $partyType, int $partyId, float $credit, float $debit, string $date, string $invoiceNo, string $label = 'Rollback Sale Return'): ?Model
    {
        return $this->appendReversal($this->normalizePartyType($partyType), $partyId, $debit, $credit, $date, $label . ': ' . $invoiceNo);
    }

    /** RV — credit party (amount + discount). */
    public function postReceiptCredit(string $partyType, int $partyId, float $credit, string $date, string $description): ?Model
    {
        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => 0,
            'credit' => $credit,
        ]);
    }

    /** PV — debit party (amount + discount). */
    public function postPaymentDebit(string $partyType, int $partyId, float $debit, string $date, string $description): ?Model
    {
        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => $debit,
            'credit' => 0,
        ]);
    }

    /** EV — credit party by expense row / total. */
    public function postExpenseCredit(string $partyType, int $partyId, float $credit, string $date, string $description): ?Model
    {
        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => 0,
            'credit' => $credit,
        ]);
    }

    /** IV — debit party by income row. */
    public function postIncomeDebit(string $partyType, int $partyId, float $debit, string $date, string $description): ?Model
    {
        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => $debit,
            'credit' => 0,
        ]);
    }

    /** CLM/CIR — claim credit note debit to party. */
    public function postClaimDebit(string $partyType, int $partyId, float $debit, string $date, string $description): ?Model
    {
        return $this->append($this->normalizePartyType($partyType), $partyId, [
            'date' => $date,
            'description' => $description,
            'debit' => $debit,
            'credit' => 0,
        ]);
    }
}
