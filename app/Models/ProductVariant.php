<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_name',
        'attributes',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'minimum_selling_price',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array', // Automatically casts JSON database column to PHP array
        'is_active' => 'boolean',
    ];

    // Relationship: A variant belongs to a master product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship: A variant has stock balances across locations
    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }
}
