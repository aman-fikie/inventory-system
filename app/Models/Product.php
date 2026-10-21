<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'base_unit_id',
        'product_code',
        'name',
        'slug',
        'description',
        'status',
        'track_inventory',
        'is_sellable',
        'created_by',
    ];

    // Relationship: A product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship: A product belongs to a brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Relationship: A product has a base unit (e.g., pcs)
    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    // Relationship: A master product can have multiple variants (e.g., 128GB Black, 256GB Blue)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
