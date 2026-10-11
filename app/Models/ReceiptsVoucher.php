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
        $allRvids = self::withoutGlobalScopes()
            ->whereNotNull('rvid')
            ->pluck('rvid');

        $maxNumber = 0;
        foreach ($allRvids as $rvid) {
            $num = (int) preg_replace('/[^0-9]/', '', (string)$rvid);
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        do {
            $maxNumber++;
            $nextInvoice = str_pad($maxNumber, 3, '0', STR_PAD_LEFT);
            $exists = self::withoutGlobalScopes()->where('rvid', $nextInvoice)->exists();
        } while ($exists);

        return $nextInvoice;
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Receipt rows created from Sale posting — not standalone voucher module. */
    public function scopeStandalone($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('remarks')
                ->orWhere('remarks', 'not like', 'Auto-generated from Sale:%');
        });
    }

    public function isSaleLinked(): bool
    {
        return str_starts_with((string) ($this->remarks ?? ''), 'Auto-generated from Sale:');
    }
}
