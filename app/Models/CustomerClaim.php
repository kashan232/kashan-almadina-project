<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerClaim extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;
    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function replacementProduct()
    {
        return $this->belongsTo(\App\Models\Product::class, 'replacement_product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'claim_warehouse_id')->withoutGlobalScopes();
    }

    public function originalWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'original_warehouse_id')->withoutGlobalScopes();
    }

    public function replacementFromWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'replacement_from_warehouse_id')->withoutGlobalScopes();
    }

    public function party()
    {
        if ($this->party_type === 'vendor') {
            return $this->belongsTo(\App\Models\Vendor::class, 'party_id');
        }
        return $this->belongsTo(\App\Models\Customer::class, 'party_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function release()
    {
        return $this->hasOne(\App\Models\CustomerClaimRelease::class, 'claim_id');
    }

    public function getReportAmountAttribute(): float
    {
        $sales = (float) ($this->sales_price ?? 0);

        if ($this->claim_type === 'credit_note') {
            $replacement = (float) ($this->replacement_sales_price ?? 0);
            return $sales > 0 ? $sales : $replacement;
        }

        return $sales;
    }

    public function getPartyNameAttribute(): string
    {
        $party = $this->party;
        if (!$party) {
            return 'N/A';
        }

        return $party->name ?? $party->customer_name ?? 'N/A';
    }

    public function getClaimTypeLabelAttribute(): string
    {
        return match ($this->claim_type) {
            'item_return' => 'Item Return',
            'credit_note' => 'Credit Note',
            'claim_hold'  => 'Claim Hold',
            default       => ucfirst(str_replace('_', ' ', (string) $this->claim_type)),
        };
    }

    public static function generateClaimNo()
    {
        $last = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $num = 0;
        if ($last) {
            $num = (int) preg_replace('/[^0-9]/', '', $last->claim_no);
        }

        do {
            $num++;
            $nextInvoice = 'CLM-' . $num;
            $exists = self::withoutGlobalScopes()->where('claim_no', $nextInvoice)->exists();
        } while ($exists);

        return $nextInvoice;
    }
}
