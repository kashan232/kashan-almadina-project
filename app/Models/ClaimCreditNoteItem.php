<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimCreditNoteItem extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(ClaimCreditNote::class, 'claim_credit_note_id');
    }
}
