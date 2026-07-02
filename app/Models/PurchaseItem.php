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
     * Net purchase rate per unit (after discount) — matches Rate on purchase form.
     */
    public function getNetRateAttribute()
    {
        if ($this->purchase_rate !== null && (float) $this->purchase_rate > 0) {
            return (float) $this->purchase_rate;
        }

        $qty = (float) ($this->qty ?? 0);
        if ($qty > 0) {
            return (float) ($this->line_total ?? 0) / $qty;
        }

        return (float) ($this->price ?? 0);
    }
}
