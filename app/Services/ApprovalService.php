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
        $request = $step->request;

        // Request must still be open — blocks acting on a rejected/approved request
        // (e.g. resurrecting a leftover step after a sibling was rejected).
        if ($request->status !== ApprovalRequest::STATUS_PENDING) {
            return false;
        }

        // Only the current step is actionable — enforces sequential order.
        // (An escalated step is still the current step, so this holds for it.)
        if ($step->position !== $request->current_step) {
            return false;
        }

        // Team boundary: Shield roles are global here, so authority is scoped by the
        // acting user's current team matching the request's team. No cross-team approval.
        if ((int) $request->team_id !== (int) $user->current_team_id) {
            return false;
        }

        if (! in_array($step->status, [ApprovalStep::STATUS_PENDING, ApprovalStep::STATUS_ESCALATED], true)) {
            return false;
        }

        if ($user->hasRole($step->role)) {
            return true;
        }

        $fallback = $request->rule->fallback_role;

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

            // Defense in depth: leave no leftover actionable steps behind that could
            // flip the document back to 'approved' after a rejection.
            $request->steps()
                ->whereKeyNot($step->getKey())
                ->whereIn('status', [ApprovalStep::STATUS_PENDING, ApprovalStep::STATUS_ESCALATED])
                ->update(['status' => ApprovalStep::STATUS_REJECTED]);

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
