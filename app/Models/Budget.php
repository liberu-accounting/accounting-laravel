<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'account_id',
        'project_id',
        'start_date',
        'end_date',
        'planned_amount',
        'description',
        'forecast_amount',
        'forecast_method',
        'is_approved',
    ];

    #[\Override]
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'planned_amount' => 'decimal:2',
        'forecast_amount' => 'decimal:2',
        'is_approved' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getVarianceAttribute(): float|int
    {
        return ($this->forecast_amount ?? 0) - ($this->planned_amount ?? 0);
    }

    public function getVariancePercentageAttribute(): float|int
    {
        if (! $this->planned_amount) {
            return 0;
        }

        return (($this->forecast_amount ?? 0) - $this->planned_amount) / $this->planned_amount * 100;
    }

    public function getActualAmount(): float|int
    {
        $query = Transaction::whereBetween('transaction_date', [$this->start_date, $this->end_date]);

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        return $query->sum('amount');
    }
}
