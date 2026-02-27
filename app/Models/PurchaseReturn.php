<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function generateReturnNo()
    {
        $prefix = 'PUR-RET-';
        $last = self::orderBy('id', 'desc')->first();
        $num = $last ? (int)str_replace($prefix, '', $last->invoice_no) + 1 : 1;
        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
