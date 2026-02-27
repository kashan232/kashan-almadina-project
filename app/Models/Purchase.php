<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;
    // app/Models/Purchase.php
    protected $table = 'purchases'; // if it's not default

    protected $guarded = [];

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    // Polymorphic relationship for Vendor/Customer/Walking Customer
    public function purchasable()
    {
        return $this->morphTo();
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function accountAllocations()
    {
        return $this->hasMany(PurchaseAccountAllocaations::class, 'purchase_id');
    }


    public static function generateInvoiceNo()
    {
        $prefix = 'PUR-';

        // Fetch last invoice
        $lastInvoice = self::orderBy('id', 'desc')->first();

        $lastNumber = 0;
        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int)substr($lastInvoice->invoice_no, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }
}
