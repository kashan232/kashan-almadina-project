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

        // Find last sale that uses this prefix (ignore other random invoice formats)
        $last = self::where('invoice_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($last && $last->invoice_no) {
            // get part after prefix and cast to int safely
            $numPart = substr($last->invoice_no, strlen($prefix));
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $numPart);
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }
}
