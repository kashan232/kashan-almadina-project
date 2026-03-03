<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use HasFactory;
    protected $table = 'stock_adjustment_details';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }
}
