

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'account_id',
        'start_date',
        'end_date', 
        'planned_amount',
        'description',
        'forecast_amount',
        'forecast_method',
        'is_approved'
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
    }
}