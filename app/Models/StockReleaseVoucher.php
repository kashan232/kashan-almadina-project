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
        return $this->belongsTo(Warehouse::class)->withoutGlobalScopes();
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
        $lastNumber = 0;
        foreach (self::withoutGlobalScopes()->pluck('voucher_no') as $voucherNo) {
            if ($voucherNo) {
                $num = (int) preg_replace('/[^0-9]/', '', $voucherNo);
                if ($num > $lastNumber) {
                    $lastNumber = $num;
                }
            }
        }

        do {
            $lastNumber++;
            $next = str_pad($lastNumber, 3, '0', STR_PAD_LEFT);
            $exists = self::withoutGlobalScopes()->where('voucher_no', $next)->exists();
        } while ($exists);

        return $next;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayNoAttribute(): string
    {
        $num = (int) preg_replace('/[^0-9]/', '', (string) $this->voucher_no);

        return 'SR-' . str_pad((string) max(0, $num), 3, '0', STR_PAD_LEFT);
    }
}
