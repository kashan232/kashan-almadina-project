<?php

namespace App\Models;

use App\Models\VendorLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'status', 'opening_balance', 'debit', 'credit', 'user_group_ids', 'created_by'];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    use HasFactory, \App\Traits\GroupIsolation, \App\Traits\HasModuleIdSequence, \App\Traits\FiltersInactiveRecords;

    protected static function defaultModuleIdRange(): array
    {
        return [
            'min' => \App\Support\ModuleIdSequence::VENDOR_MIN,
            'max' => \App\Support\ModuleIdSequence::VENDOR_MAX,
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withInactive()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    // app/Models/Vendor.php

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgers()
    {
        return $this->hasMany(VendorLedger::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'vendor_id');
    }

    public function latestLedger()
    {
        return $this->hasOne(VendorLedger::class, 'vendor_id')->latestOfMany();
    }

    /**
     * Calculate live closing balance matching General Ledger.
     */
    public function getLiveClosingBalance(): float
    {
        $gl = app(\App\Http\Controllers\GeneralLedgerController::class);
        $txs = $gl->fetchTransactions('vendor', $this->id, '2000-01-01', '2099-12-31');
        $debits = 0;
        $credits = 0;
        foreach ($txs as $t) {
            $debits += (float) ($t['debit'] ?? 0);
            $credits += (float) ($t['credit'] ?? 0);
        }
        $op = (float) ($this->opening_balance ?? 0);

        return round($op + $debits - $credits, 2);
    }
}
