<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function getStockByProductAndWarehouse($product_id, $warehouse_id)
    {
        return WarehouseStock::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->first();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function purchasable()
    {
        return $this->morphTo();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function generateReturnNo()
    {
        $last = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $num = $last ? (int) preg_replace('/[^0-9]/', '', $last->invoice_no) + 1 : 1;
        return str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    public function whtAccount()
    {
        return $this->belongsTo(Account::class, 'wht_account_id');
    }
}
