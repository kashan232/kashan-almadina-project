<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCustomerLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_customer_id',
        'admin_or_user_id',
        'previous_balance',
        'closing_balance',
    ];

    public function subCustomer()
    {
        return $this->belongsTo(SubCustomer::class);
    }

    public function adminOrUser()
    {
        return $this->belongsTo(User::class, 'admin_or_user_id');
    }
}
