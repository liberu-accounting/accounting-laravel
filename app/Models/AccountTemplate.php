

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'industry_type',
        'structure'
    ];

    protected $casts = [
        'structure' => 'array'
    ];
}