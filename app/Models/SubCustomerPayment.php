<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_customer_id', 'payment_date', 'amount', 'payment_method', 'note'
    ];

    public function subCustomer()
    {
        return $this->belongsTo(SubCustomer::class);
    }
}
