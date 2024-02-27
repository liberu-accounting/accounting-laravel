<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'asset_name',
        'asset_cost',
        'useful_life_years',
    ];

    public function assetAcquisitions()
    {
        return $this->hasMany(AssetAcquisition::class, 'asset_id');
    }
}
