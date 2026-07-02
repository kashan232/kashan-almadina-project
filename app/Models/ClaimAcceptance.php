<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimAcceptance extends Model
{
    use \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(ClaimAcceptanceItem::class, 'claim_acceptance_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function partyName(): string
    {
        if ($this->party_type === 'vendor') {
            return strtoupper($this->vendor->name ?? 'N/A');
        }

        return strtoupper($this->customer->customer_name ?? 'N/A');
    }

    public static function generateVoucherNo()
    {
        $last = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $num = 0;
        if ($last) {
            $num = (int) preg_replace('/[^0-9]/', '', $last->voucher_no);
        }

        do {
            $num++;
            $nextInvoice = str_pad($num, 4, '0', STR_PAD_LEFT);
            $exists = self::withoutGlobalScopes()->where('voucher_no', $nextInvoice)->exists();
        } while ($exists);

        return $nextInvoice;
    }
}
