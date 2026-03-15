<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    public $timestamps = false; 
    use HasFactory;
    protected $guarded = [];
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
    
    public function getPartyNameAttribute() {
        if ($this->party_type == 'vendor') {
            return \App\Models\Vendor::find($this->customer_id)->name ?? 'N/A';
        } else {
            return \App\Models\Customer::find($this->customer_id)->customer_name ?? 'N/A';
        }
    }
}
