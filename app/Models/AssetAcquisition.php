<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAcquisition extends Model
{
    use HasFactory;

    protected $primaryKey = 'asset_acquisition_id';

    protected $fillable = [
        'asset_id',
        'acquisition_date',
        'acquisition_price',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
