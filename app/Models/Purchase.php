<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Purchase extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

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
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id')->withoutGlobalScopes();
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function accountAllocations()
    {
        return $this->hasMany(PurchaseAccountAllocaations::class, 'purchase_id');
    }

    public function whtAccount()
    {
        return $this->belongsTo(Account::class, 'wht_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public static function generateInvoiceNo()
    {
        // Fetch last invoice
        $lastInvoice = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();

        $lastNumber = 0;
        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastInvoice->invoice_no);
        }

        return str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
