<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'location_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the store that owns the stock location.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the stock balances for the stock location.
     */
    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }
}
