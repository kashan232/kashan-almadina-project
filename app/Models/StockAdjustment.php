<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class, 'stock_adjustment_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public static function generateAdjID()
    {
        $latest = self::latest()->first();
        if (!$latest) return 1;
        return intval($latest->adj_id) + 1;
    }
}
