<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'employee_id',
        'base_salary',
        'overtime_hours',
        'overtime_rate',
        'tax_deductions',
        'other_deductions',
        'net_salary',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'payment_status',
    ];

    #[\Override]
    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'base_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'tax_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculateNetSalary(): void
    {
        $grossSalary = $this->grossSalary();

        // Calculate tax deductions (example rate of 20%)
        $this->tax_deductions = $grossSalary * 0.20;

        $this->net_salary = $grossSalary - $this->tax_deductions - (float) $this->other_deductions;
    }

    public function grossSalary(): float
    {
        return (float) $this->base_salary + ((float) $this->overtime_hours * (float) $this->overtime_rate);
    }

    public function totalDeductions(): float
    {
        return (float) $this->tax_deductions + (float) $this->other_deductions;
    }
}
