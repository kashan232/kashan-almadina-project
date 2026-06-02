<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // app/Models/Customer.php
    protected $fillable = ['customer_id', 'customer_name', 'customer_name_ur', 'cnic', 'filer_type', 'zone', 'contact_person', 'mobile', 'email_address', 'contact_person_2', 'mobile_2', 'email_address_2', 'opening_balance', 'debit', 'credit', 'address', 'address_ur', 'status', 'transport_ur', 'customer_type', 'user_group_ids', 'created_by'];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    use HasFactory, \App\Traits\GroupIsolation;

    public function customerLedger()
    {
        return $this->hasOne(CustomerLedger::class, 'customer_id')->latestOfMany();
    }

    public function sales()
    {
        return $this->hasMany(\App\Models\Sale::class, 'customer_id')
            ->where('partyType', 'customer');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
