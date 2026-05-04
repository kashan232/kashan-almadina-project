<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReleaseVoucher extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;
    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(StockRelease::class, 'stock_release_voucher_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function holdVoucher()
    {
        return $this->belongsTo(StockHoldVoucher::class, 'hold_voucher_id');
    }

    public function partyCustomer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function partyVendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id');
    }

    public static function generateVoucherNo()
    {
        $latest = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'SR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
