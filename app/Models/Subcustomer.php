<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'customer_main_id', 'customer_name', 'customer_name_ur',
        'cnic', 'filer_type', 'zone', 'contact_person', 'mobile',
        'email_address', 'contact_person_2', 'mobile_2', 'email_address_2',
        'opening_balance', 'address'
    ];

    public function mainCustomer()
    {
        return $this->belongsTo(Customer::class, 'customer_main_id');
    }

    public function ledger()
    {
        return $this->hasMany(SubCustomerLedger::class);
    }
}
