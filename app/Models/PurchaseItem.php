<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Rate column on purchase form: Price minus per-unit discount (discount % on retail/base).
     */
    public function getFormRateAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $discPct = (float) ($this->item_discount ?? 0);

        $retail = 0.0;
        if ($this->product) {
            $lp = $this->product->latestPrice;
            if ($lp) {
                $retail = (float) ($lp->sale_retail_price ?? $lp->purchase_retail_price ?? 0);
            }
        }

        $base = $retail > 0 ? $retail : $price;
        $unitDiscAmt = $base * $discPct / 100;

        return $price - $unitDiscAmt;
    }

    /** Total after line discount — matches form Total column. */
    public function getFormLineTotalAttribute(): float
    {
        return $this->form_rate * (float) ($this->qty ?? 0);
    }

    /**
     * Net purchase rate per unit (after discount) — matches Rate on purchase form.
     */
    public function getNetRateAttribute(): float
    {
        return $this->form_rate;
    }
}
