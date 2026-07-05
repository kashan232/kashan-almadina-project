<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory, \App\Traits\GroupIsolation, \App\Traits\HasModuleIdSequence;

    protected static function defaultModuleIdRange(): array
    {
        return [
            'min' => \App\Support\ModuleIdSequence::ACCOUNT_MIN,
            'max' => \App\Support\ModuleIdSequence::ACCOUNT_MAX,
        ];
    }

    protected $fillable = [
        'head_id',       // foreign key: account head
        'account_code',  // account code
        'title',         // account title
        'type',          // Debit / Credit
        'total_debit',
        'total_credit',
        'status',        // active/inactive
        'opening_balance',
        'user_group_ids',
        'created_by',
    ];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    // Relation with AccountHead
    public function head()
    {
        return $this->belongsTo(AccountHead::class, 'head_id');
    }

    /**
     * Next account code for a head — global sequence (ignores group scopes).
     */
    public static function generateAccountCode(int $headId): string
    {
        $lastRow = DB::table('accounts')
            ->where('head_id', $headId)
            ->orderByDesc('id')
            ->first();

        if ($lastRow && is_numeric($lastRow->account_code)) {
            $nextCode = (string) ((int) $lastRow->account_code + 1);
        } else {
            $nextCode = $headId . '001';
        }

        while (DB::table('accounts')->where('account_code', $nextCode)->exists()) {
            if (is_numeric($nextCode)) {
                $nextCode = (string) ((int) $nextCode + 1);
            } else {
                $prefix = (string) $headId;
                $suffix = max(1, (int) substr($nextCode, strlen($prefix)) + 1);
                $nextCode = $prefix . str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
            }
        }

        return $nextCode;
    }
}
