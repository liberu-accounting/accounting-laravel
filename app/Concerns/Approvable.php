<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Events\ApprovableApproved;
use App\Events\ApprovableRejected;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRule;
use App\Models\ApprovalStep;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

trait Approvable
{
    abstract public function approvalAmount(): float;

    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function submitForApproval(): void
    {
        $teamId = (int) ($this->team_id ?? auth()->user()?->current_team_id);
        $type = class_basename($this);
        $rule = ApprovalRule::matchFor($type, $this->approvalAmount(), $teamId);

        if (! $rule instanceof ApprovalRule) {
            $this->markApproved();

            return;
        }

        DB::transaction(function () use ($rule, $teamId): void {
            $request = ApprovalRequest::create([
                'team_id' => $teamId,
                'approvable_type' => $this->getMorphClass(),
                'approvable_id' => $this->getKey(),
                'rule_id' => $rule->getKey(),
                'status' => ApprovalRequest::STATUS_PENDING,
                'current_step' => 1,
            ]);

            foreach ($rule->steps as $i => $role) {
                ApprovalStep::create([
                    'approval_request_id' => $request->getKey(),
                    'position' => $i + 1,
                    'role' => $role,
                    'status' => ApprovalStep::STATUS_PENDING,
                    'deadline_at' => $i === 0 && $rule->deadline_days ? now()->addDays($rule->deadline_days) : null,
                ]);
            }

            $this->forceFill(['approval_status' => 'pending'])->save();

            $firstStep = $request->steps()->first();

            if ($firstStep instanceof ApprovalStep) {
                ApprovalRequestedNotification::dispatchToRole($firstStep, $teamId);
            }
        });
    }

    public function markApproved(): void
    {
        $this->forceFill([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->save();

        event(new ApprovableApproved($this));
    }

    public function markRejected(?string $reason): void
    {
        $this->forceFill([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ])->save();

        event(new ApprovableRejected($this, $reason));
    }
}
