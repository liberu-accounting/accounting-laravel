<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Filament\App\Pages\PendingApprovals;
use App\Models\ApprovalRule;
use App\Models\Bill;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PendingApprovalsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_pending_step_for_their_team_and_can_approve_it(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $step = $bill->approvalRequest()->first()->steps()->first();

        $this->actingAs($manager);
        $page = new PendingApprovals();

        $this->assertTrue($page->actionableStepsQuery()->pluck('id')->contains($step->id));

        app(ApprovalService::class)->approve($step, $manager);

        $this->assertSame('approved', $bill->fresh()->approval_status);
        $this->assertFalse($page->actionableStepsQuery()->pluck('id')->contains($step->id), 'approved step drops off the queue');
    }

    public function test_user_without_the_role_sees_nothing(): void
    {
        Notification::fake();
        [$team] = $this->userWithRole('manager');
        $intruder = $this->userWithRoleOnTeam('clerk', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $this->actingAs($intruder);
        $page = new PendingApprovals();

        $this->assertTrue($page->actionableStepsQuery()->get()->isEmpty());
    }

    /** @return array{0:int,1:User} */
    private function userWithRole(string $role): array
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return [(int) $user->current_team_id, $user];
    }

    private function userWithRoleOnTeam(string $role, int $team): User
    {
        $user = User::factory()->create(['current_team_id' => $team]);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
