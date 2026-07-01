<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockRelease extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];
    protected $casts = [
        'user_group_ids' => 'array',
        'meta' => 'array',
        'sale_qty' => 'float',
        'release_qty' => 'float',
    ];

    public function voucher()
    {
        return $this->belongsTo(StockReleaseVoucher::class, 'stock_release_voucher_id');
    }

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
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id')->withoutGlobalScopes();
    }
}
