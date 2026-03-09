<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class AccountTemplate extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'industry_type',
        'structure'
    ];

    protected $casts = [
        'structure' => 'array'
    ];
}