<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseAccountAllocaations extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'purchase_account_allocations'; // if it's not default

    public function head()
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

}
