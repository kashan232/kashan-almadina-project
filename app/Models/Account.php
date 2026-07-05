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
            'min' => \App\Support\ModuleIdSequence::SUB_HEAD_MIN,
            'max' => \App\Support\ModuleIdSequence::SUB_HEAD_MAX,
        ];
    }

    /** Keep account_code in sync with sub head id (50001+). */
    public function syncModuleCodeFromId(): void
    {
        if (empty($this->account_code) && $this->getKey()) {
            $this->account_code = (string) $this->getKey();
        }
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
     * Next sub head code — global sequence from 50001.
     */
    public static function generateAccountCode(int $headId = 0): string
    {
        unset($headId);

        $next = (int) \App\Support\ModuleIdSequence::peekNextSubHeadCode();

        while (DB::table('accounts')->where('account_code', (string) $next)->exists()) {
            $next++;
        }

        return (string) $next;
    }
}
