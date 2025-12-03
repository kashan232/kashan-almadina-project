<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockRelease extends Model
{
    protected $guarded = [];
    protected $casts = [
        'meta' => 'array',
        'sale_qty' => 'float',
        'release_qty' => 'float',
    ];

    public function hold()
    {
        return $this->belongsTo(StockHold::class, 'hold_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }
}
