<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // app/Models/Customer.php
    protected $fillable = ['customer_id', 'customer_name', 'customer_name_ur', 'cnic', 'filer_type', 'zone', 'contact_person', 'mobile', 'email_address', 'contact_person_2', 'mobile_2', 'email_address_2', 'debit', 'credit', 'address', 'status', 'customer_type'];

    use HasFactory;

    public function customerLedger()
    {
        return $this->hasOne(CustomerLedger::class, 'customer_id');
    }
}
