<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHoldVoucher extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(StockHold::class, 'stock_hold_voucher_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
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
        $latest = self::orderBy('id', 'desc')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'SH-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
