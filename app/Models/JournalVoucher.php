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
        $last = self::orderBy('id', 'desc')->first();
        if (!$last) return 'JV-001';
        
        $num = (int)str_replace('JV-', '', $last->jvid);
        return 'JV-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
