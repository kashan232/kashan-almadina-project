<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimCreditNote extends Model
{
    use \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(ClaimCreditNoteItem::class, 'claim_credit_note_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateVoucherNo()
    {
        $latest = self::orderBy('id', 'desc')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        return 'CCN-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
