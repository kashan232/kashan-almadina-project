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
        if (isset($this->retail_price) && (float)$this->retail_price > 0) {
            $retail = (float) $this->retail_price;
        } elseif (isset($this->purchase_retail_price) && (float)$this->purchase_retail_price > 0) {
            $retail = (float) $this->purchase_retail_price;
        } elseif ($this->product) {
            $purchase = $this->purchase;
            $rawDate = !empty($purchase?->entry_date) ? $purchase->entry_date : (!empty($purchase?->current_date) ? $purchase->current_date : ($purchase?->created_at ? \Carbon\Carbon::parse($purchase->created_at)->toDateString() : now()->toDateString()));
            $purchaseTime = $purchase?->created_at ? \Carbon\Carbon::parse($purchase->created_at) : now();

            $pricesForProd = \App\Models\ProductPrice::where('product_id', $this->product_id)
                ->orderBy('start_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($pricesForProd->isNotEmpty()) {
                $matched = $pricesForProd->filter(function($pr) use ($rawDate) {
                    $start = $pr->start_date;
                    $end = $pr->end_date;
                    if ($start && $rawDate < $start) return false;
                    if ($end && $rawDate > $end) return false;
                    return true;
                });

                if ($matched->count() > 1) {
                    $exact = $matched->filter(fn($pr) => \Carbon\Carbon::parse($pr->created_at) <= $purchaseTime)->last();
                    $chosen = $exact ?: $matched->last();
                } else {
                    $chosen = $matched->first();
                }

                if (!$chosen) {
                    $chosen = $pricesForProd->filter(fn($pr) => !$pr->start_date || $pr->start_date <= $rawDate)->last() ?: $pricesForProd->last();
                }

                $retail = (float) ($chosen->purchase_retail_price ?? $chosen->sale_retail_price ?? 0);
            }

            if ($retail <= 0) {
                $lp = $this->product->latestPrice;
                if ($lp) {
                    $retail = (float) ($lp->purchase_retail_price ?? $lp->sale_retail_price ?? 0);
                }
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
