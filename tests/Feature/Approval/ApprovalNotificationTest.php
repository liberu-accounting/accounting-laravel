<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_to_role_notifies_only_the_role_holder_on_the_team(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = app(TeamManagementService::class)->createPersonalTeamForUser($owner);
        $teamId = $team->getKey();

        Role::findOrCreate('manager', 'web');

        $manager = User::factory()->create(['current_team_id' => $teamId]);
        $manager->assignRole('manager');

        $other = User::factory()->create(['current_team_id' => $teamId]);

        $request = ApprovalRequest::create([
            'team_id' => $teamId,
            'approvable_type' => 'Bill',
            'approvable_id' => 1,
            'rule_id' => \App\Models\ApprovalRule::create([
                'team_id' => $teamId,
                'approvable_type' => 'Bill',
                'min_amount' => 0,
                'steps' => ['manager'],
                'is_active' => true,
            ])->getKey(),
            'status' => ApprovalRequest::STATUS_PENDING,
            'current_step' => 1,
        ]);

        $step = ApprovalStep::create([
            'approval_request_id' => $request->getKey(),
            'position' => 1,
            'role' => 'manager',
            'status' => ApprovalStep::STATUS_PENDING,
        ]);

        // A manager on a SECOND team must never be notified for this team's step.
        $otherTeam = app(TeamManagementService::class)->createPersonalTeamForUser(User::factory()->create());
        $otherTeamManager = User::factory()->create(['current_team_id' => $otherTeam->getKey()]);
        $otherTeamManager->assignRole('manager');

        ApprovalRequestedNotification::dispatchToRole($step, $teamId);

        Notification::assertSentTo($manager, ApprovalRequestedNotification::class);
        Notification::assertNotSentTo($other, ApprovalRequestedNotification::class);
        Notification::assertNotSentTo($otherTeamManager, ApprovalRequestedNotification::class);
    }
}
