<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcVatReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'hmrc_submission_id',
        'period_key',
        'period_from',
        'period_to',
        'due_date',
        'vat_due_sales',
        'vat_due_acquisitions',
        'total_vat_due',
        'vat_reclaimed',
        'net_vat_due',
        'total_value_sales',
        'total_value_purchases',
        'total_value_goods_supplied',
        'total_acquisitions',
        'finalised',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'due_date' => 'date',
        'vat_due_sales' => 'decimal:2',
        'vat_due_acquisitions' => 'decimal:2',
        'total_vat_due' => 'decimal:2',
        'vat_reclaimed' => 'decimal:2',
        'net_vat_due' => 'decimal:2',
        'total_value_sales' => 'decimal:2',
        'total_value_purchases' => 'decimal:2',
        'total_value_goods_supplied' => 'decimal:2',
        'total_acquisitions' => 'decimal:2',
        'finalised' => 'boolean',
    ];

    /**
     * Get the company that owns the VAT return.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the HMRC submission for this VAT return.
     */
    public function hmrcSubmission(): BelongsTo
    {
        return $this->belongsTo(HmrcSubmission::class);
    }

    /**
     * Calculate VAT return figures from invoices and expenses.
     */
    public function calculateFromTransactions(): void
    {
        // Box 1: VAT due on sales (output tax)
        $salesVat = Invoice::where('company_id', $this->company_id)
            ->whereBetween('invoice_date', [$this->period_from, $this->period_to])
            ->sum('tax_amount');

        // Box 4: VAT reclaimed on purchases (input tax)
        $purchasesVat = Expense::where('company_id', $this->company_id)
            ->whereBetween('expense_date', [$this->period_from, $this->period_to])
            ->sum('tax_amount');

        // Box 6: Total sales excluding VAT
        $totalSales = Invoice::where('company_id', $this->company_id)
            ->whereBetween('invoice_date', [$this->period_from, $this->period_to])
            ->sum('subtotal_amount');

        // Box 7: Total purchases excluding VAT
        $totalPurchases = Expense::where('company_id', $this->company_id)
            ->whereBetween('expense_date', [$this->period_from, $this->period_to])
            ->sum('amount');

        $this->vat_due_sales = $salesVat;
        $this->vat_reclaimed = $purchasesVat;
        $this->total_value_sales = $totalSales;
        $this->total_value_purchases = $totalPurchases;
        
        // Calculate totals
        $this->total_vat_due = $this->vat_due_sales + $this->vat_due_acquisitions;
        $this->net_vat_due = $this->total_vat_due - $this->vat_reclaimed;
        
        $this->save();
    }

    /**
     * Get the submission status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->hmrcSubmission) {
            return $this->hmrcSubmission->status;
        }
        
        return $this->finalised ? 'ready' : 'draft';
    }

    /**
     * Check if return can be edited.
     */
    public function isEditable(): bool
    {
        return !$this->finalised && (!$this->hmrcSubmission || $this->hmrcSubmission->isEditable());
    }
}
