<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockWastage extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(StockWastageDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class);
    }
    
    public static function generateGWN()
    {
        // Simple logic: last ID + 1? Or formatted string?
        // Let's check Purchase logic.
        // Purchase uses PUR-001.
        // User screenshot shows "GWN ID: 1".
        // It seems simple Integer ID or just "1" formatted.
        // I'll stick to simple logic or formatted if database field is string.
        // Migration has `gwn_id` string.
        // I'll format it like SW-001 or just 1 if user wants simple number input (but screenshot shows label "GWN ID" and input readonly).
        // I'll implement auto-increment logic.
        
        $latest = self::latest()->first();
        if (!$latest) {
            return 1;
        }
        // If gwn_id is number, increment. If string, parse.
        // Assuming simple number for now based on screenshot "1".
        return intval($latest->gwn_id) + 1;
    }
}
