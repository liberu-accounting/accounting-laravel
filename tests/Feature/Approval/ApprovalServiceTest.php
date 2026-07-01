<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Models\ApprovalRule;
use App\Models\Bill;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_chain_approval_marks_document_approved(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $fd = $this->userWithRoleOnTeam('finance_director', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);

        // Bill's real amount column is `total_amount` (the plan's draft used `total`, which
        // doesn't exist on this model — see Task 2 note).
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $svc = app(ApprovalService::class);

        $svc->approve($bill->approvalRequest()->first()->steps()->where('position', 1)->first(), $manager);
        $this->assertSame('pending', $bill->fresh()->approval_status, 'still pending after step 1');

        $svc->approve($bill->approvalRequest()->first()->steps()->where('position', 2)->first(), $fd);
        $this->assertSame('approved', $bill->fresh()->approval_status);
    }

    public function test_wrong_role_is_denied(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $intruder = $this->userWithRoleOnTeam('clerk', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $step = $bill->approvalRequest()->first()->steps()->first();

        $this->expectException(\App\Exceptions\ApprovalDeniedException::class);
        app(ApprovalService::class)->approve($step, $intruder);
    }

    public function test_reject_marks_document_rejected(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        app(ApprovalService::class)->reject($bill->approvalRequest()->first()->steps()->first(), $manager, 'over budget');
        $this->assertSame('rejected', $bill->fresh()->approval_status);
        $this->assertSame('over budget', $bill->fresh()->rejection_reason);
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
