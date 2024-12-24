<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $primaryKey = "invoice_id";

    protected $fillable = [
        "customer_id",
        "invoice_date",
        "due_date",
        "total_amount",
        "tax_amount",
        "tax_rate_id",
        "payment_status",
        "late_fee_percentage",
        "grace_period_days",
        "late_fee_amount"
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'invoice_date' => 'datetime',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'invoice_id');
    }

    public function calculateTax()
    {
        if (!$this->taxRate) {
            return 0;
        }

        $taxAmount = $this->total_amount * ($this->taxRate->rate / 100);
        $this->tax_amount = $taxAmount;
        return $taxAmount;
    }

    public function calculateLateFee()
    {
        if ($this->payment_status === 'paid' || !$this->due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($this->due_date)->addDays($this->grace_period_days);
        $today = Carbon::now();

        if ($today->lte($dueDate)) {
            return 0;
        }

        $lateFee = $this->total_amount * ($this->late_fee_percentage / 100);
        $this->late_fee_amount = $lateFee;
        $this->save();

        return $lateFee;
    }

    public function isOverdue()
    {
        if (!$this->due_date || $this->payment_status === 'paid') {
            return false;
        }
        
        return Carbon::now()->gt(Carbon::parse($this->due_date)->addDays($this->grace_period_days));
    }

    public function getTotalWithTax()
    {
        return $this->total_amount + $this->tax_amount;
    }

    public function getTotalWithTaxAndLateFees()
    {
        return $this->getTotalWithTax() + $this->late_fee_amount;
    }

    public function calculateTotalFromTimeEntries()
    {
        $this->total_amount = $this->timeEntries->sum('total_amount');
        return $this->total_amount;
    }
}
