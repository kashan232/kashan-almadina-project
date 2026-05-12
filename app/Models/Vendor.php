<?php

namespace App\Models;

use App\Models\VendorLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'opening_balance', 'debit', 'credit', 'user_group_ids', 'created_by'];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    use HasFactory, \App\Traits\GroupIsolation;
    // app/Models/Vendor.php

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgers()
    {
        return $this->hasMany(VendorLedger::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'vendor_id');
    }

    public function latestLedger()
    {
        return $this->hasOne(VendorLedger::class, 'vendor_id')->latestOfMany();
    }
}
