<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'name',
        'code',
        'description',
        'allocation_method',
        'allocation_base',
        'status',
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

    public function calculateAllocation($totalAmount): float|int
    {
        return match ($this->allocation_method) {
            'fixed_percentage' => $totalAmount * ($this->allocation_base / 100),
            'headcount' => $totalAmount * ($this->getHeadcount() / $this->getTotalHeadcount()),
            'direct_labor_hours' => $totalAmount * ($this->getDirectLaborHours() / $this->getTotalDirectLaborHours()),
            default => 0,
        };
    }
}
