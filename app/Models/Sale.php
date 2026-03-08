<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];


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
        $prefix = 'INVSLE-';

        // 1. Get max from Sales
        $lastSale = self::where('invoice_no', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->first();

        // 2. Get max from Productbookings
        $lastBooking = \App\Models\Productbooking::where('invoice_no', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(invoice_no) DESC, invoice_no DESC')
            ->first();

        $maxNum = 0;

        foreach ([$lastSale, $lastBooking] as $last) {
            if ($last && $last->invoice_no) {
                // get part after prefix and cast to int safely
                $numPart = substr($last->invoice_no, strlen($prefix));
                $num = (int) preg_replace('/[^0-9]/', '', $numPart);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $newNumber = str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }
}
