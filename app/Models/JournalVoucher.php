<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalVoucher extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    protected $fillable = [
        'jvid',
        'entry_date',
        'party_type',
        'party_id',
        'reference_no',
        'remarks',
        'narration_id',
        'account_id',
        'debit',
        'credit',
        'total_debit',
        'total_credit',
        'status',
    ];

    public static function generateJournalNo()
    {
        $last = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        if (!$last) return '001';
        
        $num = (int) preg_replace('/[^0-9]/', '', $last->jvid);
        return str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
