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

    public static function postedReleasesWithSum(): array
    {
        return [
            'releases as released_qty' => function ($q) {
                $q->withoutGlobalScopes()->where(function ($sub) {
                    $sub->whereHas('voucher', function ($v) {
                        $v->withoutGlobalScopes()->where('status', 'Posted');
                    })->orWhereIn('status', ['Posted', 'posted']);
                });
            },
        ];
    }

    public function postedReleaseQty(): float
    {
        if (array_key_exists('released_qty', $this->attributes)) {
            return (float) ($this->attributes['released_qty'] ?? 0);
        }

        if ($this->relationLoaded('releases')) {
            return (float) $this->releases
                ->filter(fn ($release) => self::isPostedRelease($release))
                ->sum('release_qty');
        }

        return (float) $this->releases()
            ->withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereHas('voucher', function ($v) {
                    $v->withoutGlobalScopes()->where('status', 'Posted');
                })->orWhereIn('status', ['Posted', 'posted']);
            })
            ->sum('release_qty');
    }

    /** Original qty on the hold document (never reduced when release is posted). */
    public function grossHoldQty(): float
    {
        return (float) $this->hold_qty;
    }

    /** Qty still available to release against this hold line. */
    public function remainingHoldQty(): float
    {
        return max(0, (float) $this->hold_qty - $this->postedReleaseQty());
    }

    public function isFormalHoldLine(): bool
    {
        return !empty($this->stock_hold_voucher_id);
    }

    public static function isPostedRelease(StockRelease $release): bool
    {
        $status = strtolower((string) ($release->status ?? ''));
        if (in_array($status, ['posted'], true)) {
            return true;
        }

        $voucher = $release->relationLoaded('voucher') ? $release->voucher : null;

        return $voucher && strtolower((string) ($voucher->status ?? '')) === 'posted';
    }

    /** Qty counted against available stock (remaining on formal hold lines). */
    public function netHoldQtyForDisplay(): float
    {
        if ($this->isFormalHoldLine()) {
            return $this->remainingHoldQty();
        }

        return (float) $this->hold_qty;
    }

    /** Original held qty shown on hold list / view (unchanged after release). */
    public function getDisplayHoldQtyAttribute(): float
    {
        return (float) $this->hold_qty;
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
