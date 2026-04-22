<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimAcceptanceItem extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function voucher()
    {
        return $this->belongsTo(ClaimAcceptance::class, 'claim_acceptance_id');
    }
}
