<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'warehouse_id',
        'product_id',
        'stock',
        'price_level',
        'sales_price',
        'sales_qty',
        'retail_price',
        'discount_percent',
        'discount_amount',
        'amount',
        'invoice_no', 'customer_id', 'items'
    ];

    // Relation to Sale
    public function sale()
    {
        return $this->belongsTo(\App\Models\Sale::class, 'sale_id');
    }
    // Relation to Warehouse (agar model hai)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Relation to Product (agar model hai)
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
}
