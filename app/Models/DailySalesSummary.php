<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySalesSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'report_date',
        'total_sales_count',
        'gross_revenue',
        'total_discount',
        'total_tax',
    ];

    protected $casts = [
        'report_date' => 'date',
        'gross_revenue' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
