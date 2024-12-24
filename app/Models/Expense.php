

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ExpenseApprovalNotification;
use App\Services\ExchangeRateService;

class Expense extends Model
{
    protected $fillable = [
        'amount',
        'description',
        'date',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'project_id',
        'cost_center_id',
        'is_indirect',
        'allocation_percentage',
        'currency_id'
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'is_indirect' => 'boolean',
        'allocation_percentage' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getAmountInDefaultCurrency()
    {
        if (!$this->currency_id) {
            return $this->amount;
        }

        $defaultCurrency = Currency::where('is_default', true)->first();
        if ($this->currency_id === $defaultCurrency->currency_id) {
            return $this->amount;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate(
            $this->currency,
            $defaultCurrency
        );
        
        return $this->amount * $rate;
    }

    public function approve()
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->user->notify(new ExpenseApprovalNotification($this, 'approved'));
    }

    public function reject($reason)
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->user->notify(new ExpenseApprovalNotification($this, 'rejected'));
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function getAllocatedAmount()
    {
        $amount = $this->is_indirect ? 
            $this->amount * ($this->allocation_percentage / 100) : 
            $this->amount;

        return $this->getAmountInDefaultCurrency($amount);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($expense) {
            if (empty($expense->currency_id)) {
                $expense->currency_id = Currency::where('is_default', true)->first()->currency_id;
            }
        });
    }
}