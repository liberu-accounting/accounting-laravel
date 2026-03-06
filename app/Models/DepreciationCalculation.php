<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepreciationCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'year',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'calculation_date'
    ];

    protected $casts = [
        'calculation_date' => 'date'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}