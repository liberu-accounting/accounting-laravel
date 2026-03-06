<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'allocation_method',
        'allocation_base',
        'status'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function calculateAllocation($totalAmount)
    {
        return match($this->allocation_method) {
            'fixed_percentage' => $totalAmount * ($this->allocation_base / 100),
            'headcount' => $totalAmount * ($this->getHeadcount() / $this->getTotalHeadcount()),
            'direct_labor_hours' => $totalAmount * ($this->getDirectLaborHours() / $this->getTotalDirectLaborHours()),
            default => 0,
        };
    }
}