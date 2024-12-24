

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'account_id',
        'project_id',
        'cost_center_id',
        'start_date',
        'end_date', 
        'planned_amount',
        'description',
        'forecast_amount',
        'forecast_method',
        'is_approved'
        'category'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'planned_amount' => 'decimal:2',
        'forecast_amount' => 'decimal:2',
        'is_approved' => 'boolean'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getVarianceAttribute()
    {
        return $this->forecast_amount - $this->planned_amount;
    }

    public function getVariancePercentageAttribute()
    {
        if ($this->planned_amount == 0) return 0;
        return ($this->forecast_amount - $this->planned_amount) / $this->planned_amount * 100;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function getVariance()
    {
        $actualAmount = $this->getActualAmount();
        return $this->planned_amount - $actualAmount;
    }

    public function getActualAmount()
    {
        $query = Transaction::whereBetween('transaction_date', [$this->start_date, $this->end_date]);
        
        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }
        
        if ($this->cost_center_id) {
            $query->where('cost_center_id', $this->cost_center_id);
        }
        
        return $query->sum('amount');
      
    }
}