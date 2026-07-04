<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaleReturn extends Model
{
    public $timestamps = false; 
    use HasFactory, \App\Traits\GroupIsolation;
    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function items(){ return $this->hasMany(SaleReturnItem::class, 'sale_return_id'); }
    public function sale(){ return $this->belongsTo(Sale::class, 'sale_id'); }

    public function customer()
    {
        if ($this->party_type === 'vendor') {
            return $this->belongsTo(Vendor::class, 'customer_id');
        }

        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    public function getPartyNameAttribute() {
        if ($this->party_type == 'vendor') {
            return \App\Models\Vendor::find($this->customer_id)->name ?? 'N/A';
        } else {
            return \App\Models\Customer::find($this->customer_id)->customer_name ?? 'N/A';
        }
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Next SR- invoice number — global sequence across all users/groups.
     * Uses DB::table (never Eloquent scopes).
     */
    public static function generateReturnNo(bool $forUpdate = false): string
    {
        if ($forUpdate) {
            DB::table('sale_returns')->lockForUpdate()->select('id')->get();
        }

        $invoices = DB::table('sale_returns')->pluck('invoice_no');

        $maxNum = 0;
        foreach ($invoices as $inv) {
            $inv = trim((string) $inv);
            if ($inv === '') {
                continue;
            }
            if (preg_match('/^SR-(\d+)$/i', $inv, $m)) {
                $maxNum = max($maxNum, (int) $m[1]);
            }
        }

        do {
            $maxNum++;
            $nextInvoice = 'SR-' . $maxNum;
            $exists = DB::table('sale_returns')->where('invoice_no', $nextInvoice)->exists();
        } while ($exists);

        return $nextInvoice;
    }
}
