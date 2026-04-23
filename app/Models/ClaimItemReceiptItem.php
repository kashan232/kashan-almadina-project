<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimItemReceiptItem extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function receipt()
    {
        return $this->belongsTo(ClaimItemReceipt::class, 'claim_item_receipt_id');
    }
}
