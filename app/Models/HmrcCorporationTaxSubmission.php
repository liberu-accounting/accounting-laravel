<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcCorporationTaxSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'hmrc_submission_id',
        'accounting_period_start',
        'accounting_period_end',
        'turnover',
        'total_profits',
        'taxable_profits',
        'corporation_tax_charged',
        'marginal_relief',
        'total_tax_payable',
        'computation_data',
        'filing_due_date',
        'payment_due_date',
        'is_amended',
    ];

    protected $casts = [
        'turnover' => 'decimal:2',
        'total_profits' => 'decimal:2',
        'taxable_profits' => 'decimal:2',
        'corporation_tax_charged' => 'decimal:2',
        'marginal_relief' => 'decimal:2',
        'total_tax_payable' => 'decimal:2',
        'computation_data' => 'array',
        'filing_due_date' => 'date',
        'payment_due_date' => 'date',
        'is_amended' => 'boolean',
    ];

    /**
     * Get the company that owns the corporation tax submission.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the HMRC submission for this corporation tax submission.
     */
    public function hmrcSubmission(): BelongsTo
    {
        return $this->belongsTo(HmrcSubmission::class);
    }

    /**
     * Calculate corporation tax from financial data.
     */
    public function calculateFromFinancials(): void
    {
        // Get total revenue for the period
        $revenue = Invoice::where('company_id', $this->company_id)
            ->whereBetween('invoice_date', [$this->accounting_period_start, $this->accounting_period_end])
            ->sum('total_amount');

        // Get total expenses for the period
        $expenses = Expense::where('company_id', $this->company_id)
            ->whereBetween('expense_date', [$this->accounting_period_start, $this->accounting_period_end])
            ->sum('amount');

        $this->turnover = $revenue;
        $this->total_profits = $revenue - $expenses;
        
        // Apply tax computation (simplified - actual calculation would be more complex)
        $this->taxable_profits = $this->total_profits;
        
        // Corporation tax rate (19% for UK as of 2024, varies by profit level)
        $taxRate = $this->getTaxRate();
        $this->corporation_tax_charged = $this->taxable_profits * $taxRate;
        
        // Calculate marginal relief if applicable
        $this->marginal_relief = $this->calculateMarginalRelief();
        
        $this->total_tax_payable = max(0, $this->corporation_tax_charged - $this->marginal_relief);
        
        // Store detailed computation
        $this->computation_data = [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'tax_rate' => $taxRate,
            'calculated_at' => now()->toIso8601String(),
        ];
        
        $this->save();
    }

    /**
     * Get applicable corporation tax rate.
     */
    private function getTaxRate(): float
    {
        // UK Corporation Tax rates (as of 2024)
        // £50,000 or less: 19% (small profits rate)
        // £50,001 to £250,000: marginal relief applies
        // Over £250,000: 25% (main rate)
        
        if ($this->taxable_profits <= 50000) {
            return 0.19;
        } elseif ($this->taxable_profits > 250000) {
            return 0.25;
        }
        
        // Marginal relief band
        return 0.25;
    }

    /**
     * Calculate marginal relief.
     */
    private function calculateMarginalRelief(): float
    {
        if ($this->taxable_profits <= 50000 || $this->taxable_profits > 250000) {
            return 0;
        }
        
        // Marginal relief formula
        $fraction = 3 / 200;
        $upperLimit = 250000;
        $relief = ($upperLimit - $this->taxable_profits) * $fraction;
        
        return max(0, $relief);
    }

    /**
     * Get the submission status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->hmrcSubmission) {
            return $this->hmrcSubmission->status;
        }
        
        return 'draft';
    }

    /**
     * Check if submission can be edited.
     */
    public function isEditable(): bool
    {
        return !$this->hmrcSubmission || $this->hmrcSubmission->isEditable();
    }

    /**
     * Check if submission is overdue.
     */
    public function isOverdue(): bool
    {
        return now()->isAfter($this->filing_due_date) && 
               (!$this->hmrcSubmission || !$this->hmrcSubmission->isSubmitted());
    }
}
