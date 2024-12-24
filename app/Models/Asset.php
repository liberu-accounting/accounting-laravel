<?php

namespace App\Models;

use Carbon\Carbon;
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
        'depreciation_method',
        'salvage_value',
        'acquisition_date',
        'is_active'
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function assetAcquisitions()
    {
        return $this->hasMany(AssetAcquisition::class, 'asset_id');
    }

    public function depreciationCalculations()
    {
        return $this->hasMany(DepreciationCalculation::class, 'asset_id');
    }

    public function calculateDepreciation()
    {
        if ($this->depreciation_method === 'straight_line') {
            return $this->calculateStraightLineDepreciation();
        }
        
        return $this->calculateReducingBalanceDepreciation();
    }

    protected function calculateStraightLineDepreciation()
    {
        $depreciableAmount = $this->asset_cost - $this->salvage_value;
        $annualDepreciation = $depreciableAmount / $this->useful_life_years;
        
        $startDate = $this->acquisition_date;
        $currentValue = $this->asset_cost;
        
        for ($year = 1; $year <= $this->useful_life_years; $year++) {
            $currentValue -= $annualDepreciation;
            
            DepreciationCalculation::create([
                'asset_id' => $this->asset_id,
                'year' => $year,
                'depreciation_amount' => $annualDepreciation,
                'accumulated_depreciation' => $annualDepreciation * $year,
                'book_value' => max($currentValue, $this->salvage_value),
                'calculation_date' => $startDate->copy()->addYears($year)
            ]);
        }
    }

    protected function calculateReducingBalanceDepreciation()
    {
        $rate = 2 / $this->useful_life_years; // Double declining rate
        $currentValue = $this->asset_cost;
        $startDate = $this->acquisition_date;
        
        for ($year = 1; $year <= $this->useful_life_years; $year++) {
            $depreciation = $currentValue * $rate;
            $currentValue -= $depreciation;
            
            if ($currentValue < $this->salvage_value) {
                $depreciation = $currentValue - $this->salvage_value;
                $currentValue = $this->salvage_value;
            }
            
            DepreciationCalculation::create([
                'asset_id' => $this->asset_id,
                'year' => $year,
                'depreciation_amount' => $depreciation,
                'accumulated_depreciation' => $this->asset_cost - $currentValue,
                'book_value' => $currentValue,
                'calculation_date' => $startDate->copy()->addYears($year)
            ]);
        }
    }
}
