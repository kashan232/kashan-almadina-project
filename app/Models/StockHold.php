<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockHold extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
        'meta' => 'array',
        'entry_date' => 'date',
        'sale_qty' => 'float',
        'hold_qty' => 'float',
    ];

    // Relations
    public function voucher()
    {
        return $this->belongsTo(\App\Models\StockHoldVoucher::class, 'stock_hold_voucher_id');
    }

    public function sale()
    {
        return $this->belongsTo(\App\Models\Sale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function releases()
    {
        return $this->hasMany(StockRelease::class, 'hold_id');
    }

    /** Original held qty for list/history — remaining + already released. */
    public function getDisplayHoldQtyAttribute(): float
    {
        $released = (float) ($this->released_qty ?? 0);

        if ($released <= 0 && $this->relationLoaded('releases')) {
            $released = (float) $this->releases
                ->whereIn('status', ['Posted', 'posted'])
                ->sum('release_qty');
        }

        return (float) $this->hold_qty + $released;
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id')->withoutGlobalScopes();
    }

    // explicit relations so we can eager-load without error
    public function partyCustomer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'party_id');
    }

    public function partyVendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'party_id');
    }

    /**
     * Backwards-compatible helper if you want a single relation accessor.
     * Note: this cannot be eager-loaded as a single relation in advance,
     * so use partyCustomer/partyVendor for eager-loading.
     */
    public function party()
    {
        if ($this->party_type === 'vendor') {
            return $this->belongsTo(\App\Models\Vendor::class, 'party_id');
        }

        return $this->belongsTo(\App\Models\Customer::class, 'party_id');
    }

    /**
     * Accessor to get a readable party name (friendlier in blade).
     */
    public function getPartyNameAttribute()
    {
        // Respect loaded relations to avoid extra queries
        if ($this->party_type === 'vendor') {
            if ($this->relationLoaded('partyVendor') && $this->partyVendor) {
                return $this->partyVendor->name ?? $this->partyVendor->phone ?? null;
            }
            return optional($this->partyVendor)->name ?? optional($this->partyVendor)->phone ?? null;
        }

        if ($this->party_type === 'customer') {
            if ($this->relationLoaded('partyCustomer') && $this->partyCustomer) {
                return $this->partyCustomer->customer_name ?? $this->partyCustomer->mobile ?? null;
            }
            return optional($this->partyCustomer)->customer_name ?? optional($this->partyCustomer)->mobile ?? null;
        }

        // fallback / walkin
        return $this->attributes['party_name'] ?? null;
    }
}
