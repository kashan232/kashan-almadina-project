<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockWastageDetail extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function wastage()
    {
        return $this->belongsTo(StockWastage::class, 'stock_wastage_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
