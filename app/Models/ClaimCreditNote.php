<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimCreditNote extends Model
{
    use \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(ClaimCreditNoteItem::class, 'claim_credit_note_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id')->withoutGlobalScopes();
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id')->withoutGlobalScopes();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function whtAccount()
    {
        return $this->belongsTo(Account::class, 'wht_account_id');
    }

    public function partyName(): string
    {
        if ($this->party_type === 'vendor') {
            return strtoupper($this->vendor->name ?? 'N/A');
        }

        return strtoupper($this->customer->customer_name ?? 'N/A');
    }

    public static function generateVoucherNo()
    {
        $num1 = 0;
        $latestCredit = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        if ($latestCredit && $latestCredit->voucher_no) {
            $num1 = (int) preg_replace('/[^0-9]/', '', $latestCredit->voucher_no);
        }

        $num2 = 0;
        $latestReceipt = \App\Models\ClaimItemReceipt::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        if ($latestReceipt && $latestReceipt->voucher_no) {
            $num2 = (int) preg_replace('/[^0-9]/', '', $latestReceipt->voucher_no);
        }

        $num = max($num1, $num2);

        do {
            $num++;
            $nextInvoice = str_pad($num, 4, '0', STR_PAD_LEFT);
            $exists1 = self::withoutGlobalScopes()->where('voucher_no', $nextInvoice)->exists();
            $exists2 = \App\Models\ClaimItemReceipt::withoutGlobalScopes()->where('voucher_no', $nextInvoice)->exists();
        } while ($exists1 || $exists2);

        return $nextInvoice;
    }
}
