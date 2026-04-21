<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerClaimRelease extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function claim()
    {
        return $this->belongsTo(CustomerClaim::class, 'claim_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function party()
    {
        if ($this->party_type === 'vendor') {
            return $this->belongsTo(Vendor::class, 'party_id');
        }
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
