<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function posSessions()
    {
        return $this->hasMany(PosSession::class);
    }
}
