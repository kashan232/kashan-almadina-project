<?php

namespace App\Models;

use App\Models\Productbooking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductBookingItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'booking_id',
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
    public function booking()
    {
        return $this->belongsTo(Productbooking::class);
    }

    // Relation to Warehouse (agar model hai)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Relation to Product (agar model hai)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
