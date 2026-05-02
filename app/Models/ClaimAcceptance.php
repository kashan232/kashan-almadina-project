<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimAcceptance extends Model
{
    use \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(ClaimAcceptanceItem::class, 'claim_acceptance_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public static function generateVoucherNo()
    {
        $last = self::orderBy('id', 'desc')->first();
        $nextId = $last ? $last->id + 1 : 1;
        return 'ACC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
