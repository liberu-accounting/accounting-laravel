<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApprovalStep;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Widens overdue, still-pending approval steps to the rule's fallback role.
 * NEVER approves or rejects anything — escalation only expands who may act.
 */
class EscalateApprovalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        ApprovalStep::query()
            ->where('status', ApprovalStep::STATUS_PENDING)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->whereNull('escalated_at')
            ->each(fn (ApprovalStep $step) => $this->escalate($step));
    }

    private function escalate(ApprovalStep $step): void
    {
        $step->forceFill([
            'status' => ApprovalStep::STATUS_ESCALATED,
            'escalated_at' => now(),
        ])->save();

        $request = $step->request;
        $teamId = (int) $request->team_id;
        ApprovalRequestedNotification::dispatchToRole($step, $teamId);

        $fallbackRole = $request->rule->fallback_role;

        if ($fallbackRole) {
            ApprovalRequestedNotification::dispatchToRole(
                (clone $step)->forceFill(['role' => $fallbackRole]),
                $teamId
            );
        }
    }
}
