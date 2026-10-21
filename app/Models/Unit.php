<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    // Relationship: A unit can be the base unit for many products
    public function products()
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }
}
