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

     public function product(){ return $this->belongsTo(Product::class); }
    public function saleReturn(){ return $this->belongsTo(SaleReturn::class); }
}
