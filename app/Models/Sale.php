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

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public static function generateInvoiceNo()
    {
        $salesInvoices = self::withoutGlobalScopes()->pluck('invoice_no');
        $bookingInvoices = \App\Models\Productbooking::withoutGlobalScopes()->pluck('invoice_no');

        $maxNum = 0;

        foreach ($salesInvoices->concat($bookingInvoices) as $invoiceNo) {
            if ($invoiceNo) {
                // Extract only numbers
                $num = (int) preg_replace('/[^0-9]/', '', $invoiceNo);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }
}
