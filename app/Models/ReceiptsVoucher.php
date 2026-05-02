<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptsVoucher extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;
    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public static function generateInvoiceNo()
    {
        $prefix = 'RVID-';

        $lastInvoice = self::where('rvid', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(rvid) DESC, rvid DESC')
            ->first();

        $lastNumber = 0;
        if ($lastInvoice && $lastInvoice->rvid) {
            $numPart = substr($lastInvoice->rvid, strlen($prefix));
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $numPart);
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }





    // ReceiptsVoucher.php (Model)
    // public function Customer()
    // {
    //     return $this->belongsTo(Customer::class, 'party_id', 'id');
    // }

    public function Vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id', 'id');
    }

    public function getPartyAttribute()
    {
        if ($this->type === 'vendor') {
            return $this->Vendor;   // vendor relation return karega
        } elseif ($this->type === 'customer' || $this->type === '1') {
            return $this->Customer; // customer relation return karega
        }
        return null;
    }


    // Account Head
    public function AccountHead()
    {
        return $this->belongsTo(AccountHead::class, 'row_account_head', 'id');
    }

    // Account
    public function Account()
    {
        return $this->belongsTo(Account::class, 'row_account_id', 'id');
    }
}
