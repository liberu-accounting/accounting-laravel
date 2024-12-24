

<?php

namespace App\Models;

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
        'approved_at'
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
}