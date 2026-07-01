<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ESCALATED = 'escalated';

    #[\Override]
    protected $fillable = [
        'approval_request_id',
        'position',
        'role',
        'status',
        'decided_by',
        'decided_at',
        'reason',
        'deadline_at',
        'escalated_at',
    ];

    #[\Override]
    protected $casts = [
        'position' => 'integer',
        'decided_at' => 'datetime',
        'deadline_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
