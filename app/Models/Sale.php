<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    // Relation to sale items
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'customer_id');
    }


    public static function generateInvoiceNo()
    {
        // 1. Get max from Sales
        $lastSale = self::withoutGlobalScopes()
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->first();

        // 2. Get max from Productbookings
        $lastBooking = \App\Models\Productbooking::withoutGlobalScopes()
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->first();

        $maxNum = 0;

        foreach ([$lastSale, $lastBooking] as $last) {
            if ($last && $last->invoice_no) {
                // Remove any non-numeric characters to get the number part
                $num = (int) preg_replace('/[^0-9]/', '', $last->invoice_no);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return 'INVSLE-' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }
}
