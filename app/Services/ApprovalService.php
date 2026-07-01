<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApprovalDeniedException;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function canAct(ApprovalStep $step, User $user): bool
    {
        if (! in_array($step->status, [ApprovalStep::STATUS_PENDING, ApprovalStep::STATUS_ESCALATED], true)) {
            return false;
        }

        if ($user->hasRole($step->role)) {
            return true;
        }

        $fallback = $step->request->rule->fallback_role;

        return $step->status === ApprovalStep::STATUS_ESCALATED && $fallback && $user->hasRole($fallback);
    }

    public function approve(ApprovalStep $step, User $user, ?string $reason = null): void
    {
        $this->guard($step, $user);

        DB::transaction(function () use ($step, $user, $reason): void {
            $step->forceFill(['status' => ApprovalStep::STATUS_APPROVED, 'decided_by' => $user->getKey(), 'decided_at' => now(), 'reason' => $reason])->save();
            $request = $step->request;
            $next = $request->steps()->where('position', '>', $step->position)->orderBy('position')->first();

            if ($next instanceof ApprovalStep) {
                $rule = $request->rule;
                $next->forceFill(['deadline_at' => $rule->deadline_days ? now()->addDays($rule->deadline_days) : null])->save();
                $request->forceFill(['current_step' => $next->position])->save();
                ApprovalRequestedNotification::dispatchToRole($next, (int) $request->team_id);

                return;
            }

            $request->forceFill(['status' => ApprovalRequest::STATUS_APPROVED])->save();
            $request->approvable->markApproved();
        });
    }

    public function reject(ApprovalStep $step, User $user, string $reason): void
    {
        $this->guard($step, $user);

        DB::transaction(function () use ($step, $user, $reason): void {
            $step->forceFill(['status' => ApprovalStep::STATUS_REJECTED, 'decided_by' => $user->getKey(), 'decided_at' => now(), 'reason' => $reason])->save();
            $request = $step->request;
            $request->forceFill(['status' => ApprovalRequest::STATUS_REJECTED])->save();
            $request->approvable->markRejected($reason);
        });
    }

    private function guard(ApprovalStep $step, User $user): void
    {
        if (! $this->canAct($step, $user)) {
            throw new ApprovalDeniedException('User may not act on this approval step.');
        }
    }
}
