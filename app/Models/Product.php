<?php

namespace App\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];
    // protected $fillable = [
    //     'creater_id', 'category_id', 'sub_category_id', 'item_code', 'item_name', 'size',
    //     'opening_carton_quantity', 'carton_quantity', 'loose_pieces', 'pcs_in_carton',
    //     'wholesale_price', 'retail_price', 'initial_stock', 'alert_quantity'
    // ];
    // public function category_relation()
    // {
    //     return $this->belongsTo(Category::class,'category_id');
    // }

    // public function sub_category_relation()
    // {
    //     return $this->belongsTo(Subcategory::class,'sub_category_id');
    // }


    //     public function unit()
    //     {
    //         return $this->belongsTo(Unit::class, 'unit_id');
    //     }


    // App/Models/Product.php
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    // Agar latest price chahiye
    public function latest_price()
    {
        return $this->hasOne(ProductPrice::class)->latestOfMany();
    }
    public function latestPrice()
    {
        return $this->hasOne(ProductPrice::class)->latestOfMany();
    }

    public function brandRelation()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
