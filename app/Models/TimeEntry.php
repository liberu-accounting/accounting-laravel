<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'start_time',
        'end_time',
        'description',
        'hourly_rate',
        'total_amount',
    ];

    #[\Override]
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateTotalAmount(): int|float
    {
        $hours = $this->end_time->diffInHours($this->start_time);
        $this->total_amount = $hours * $this->hourly_rate;

        return $this->total_amount;
    }
}
