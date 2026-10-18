<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class StockTransfer extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    protected $fillable = [
        'from_warehouse_id',
        'from_shop',
        'to_warehouse_id',
        'to_shop',
        'entry_date',
        'entry_time',
        'remarks',
        'status',
        'created_by',
        'confirmed_by',
        'user_group_ids',
    ];

    public function items()
    {
        return $this->hasMany(StockTransferProduct::class, 'stock_transfer_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id')->withoutGlobalScopes();
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id')->withoutGlobalScopes();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
