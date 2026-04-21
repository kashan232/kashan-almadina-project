<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerClaim extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function replacementProduct()
    {
        return $this->belongsTo(\App\Models\Product::class, 'replacement_product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'claim_warehouse_id');
    }

    public function originalWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'original_warehouse_id');
    }

    public function replacementFromWarehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'replacement_from_warehouse_id');
    }

    public function party()
    {
        if ($this->party_type === 'vendor') {
            return $this->belongsTo(\App\Models\Vendor::class, 'party_id');
        }
        return $this->belongsTo(\App\Models\Customer::class, 'party_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function release()
    {
        return $this->hasOne(\App\Models\CustomerClaimRelease::class, 'claim_id');
    }
}
