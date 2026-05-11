<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InwardGatepass extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(InwardGatepassItem::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'vendor_id');
    }

    public function getPartyNameAttribute()
    {
        if ($this->vendor_type === 'customer' || $this->vendor_type === 'walkin') {
            return $this->customer->customer_name ?? 'Unknown';
        }
        return $this->vendor->name ?? 'Unknown';
    }

    public static function generateInvoiceNo()
    {
        // Fetch the last invoice number from the database
        $lastInvoice = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();

        // Extract last number from invoice_no
        $lastNumber = 0;
        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $lastInvoice->invoice_no);
        }

        // Increment and pad with leading zeros
        return str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
