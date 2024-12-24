

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ExpenseApprovalNotification;

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
        'is_recurring',
        'recurrence_frequency',
        'recurrence_start',
        'recurrence_end',
        'last_generated'
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'is_indirect' => 'boolean',
        'allocation_percentage' => 'decimal:2',
        'is_recurring' => 'boolean',
        'recurrence_start' => 'date',
        'recurrence_end' => 'date',
        'last_generated' => 'date'
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
        if ($this->is_indirect) {
            return $this->amount * ($this->allocation_percentage / 100);
        }
        return $this->amount;
    }

    public function generateRecurring()
    {
        if (!$this->is_recurring || !$this->shouldGenerateNew()) {
            return;
        }

        $newExpense = $this->replicate();
        $newExpense->date = $this->getNextDate();
        $newExpense->approval_status = 'pending';
        $newExpense->approved_by = null;
        $newExpense->approved_at = null;
        $newExpense->save();

        $this->last_generated = now();
        $this->save();
    }

    private function shouldGenerateNew(): bool
    {
        if ($this->recurrence_end && $this->recurrence_end < now()) {
            return false;
        }

        $lastDate = $this->last_generated ?? $this->recurrence_start;
        return $this->getNextDate()->lte(now());
    }

    private function getNextDate(): Carbon
    {
        $lastDate = $this->last_generated ?? $this->recurrence_start;
        
        return match($this->recurrence_frequency) {
            'daily' => $lastDate->addDay(),
            'weekly' => $lastDate->addWeek(),
            'monthly' => $lastDate->addMonth(),
            'yearly' => $lastDate->addYear(),
            default => $lastDate
        };
    }
}