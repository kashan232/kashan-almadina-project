<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    public $timestamps = false; 
    use HasFactory, \App\Traits\GroupIsolation;
    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];
    // protected $fillable = [
    //     'invoice_no',
    //     'date',
    //     'customer_id',
    //     'warehouse_id',
    //     'total_qty',
    //     'total_discount',
    //     'total_tax',
    //     'total_price',
    //     'note',
    //     'created_by',
    // ];
    // protected $fillable = [
    //     'sale_return_id','warehouse_id','product_id','stock','price_level',
    //     'sales_price','sales_qty','discount_percent','discount_amount','amount',
    // ];

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
     * Next SR- invoice number across ALL groups (admin + user portals share one sequence).
     */
    public static function generateReturnNo(bool $forUpdate = false): string
    {
        $query = self::withoutGlobalScopes()->where('invoice_no', 'LIKE', 'SR-%');
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $maxNum = 0;
        foreach ($query->pluck('invoice_no') as $inv) {
            $num = (int) preg_replace('/[^0-9]/', '', (string) $inv);
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }

        do {
            $maxNum++;
            $nextInvoice = 'SR-' . $maxNum;
            $exists = self::withoutGlobalScopes()->where('invoice_no', $nextInvoice)->exists();
        } while ($exists);

        return $nextInvoice;
    }
}
