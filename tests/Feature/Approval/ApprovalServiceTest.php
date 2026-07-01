<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Exceptions\ApprovalDeniedException;
use App\Models\ApprovalRule;
use App\Models\ApprovalStep;
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

    public function test_cannot_approve_out_of_order(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $fd = $this->userWithRoleOnTeam('finance_director', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $svc = app(ApprovalService::class);
        $step2 = $bill->approvalRequest()->first()->steps()->where('position', 2)->first();

        $this->assertFalse($svc->canAct($step2, $fd), 'step 2 is not actionable while current_step is 1');

        $threw = false;
        try {
            $svc->approve($step2, $fd);
        } catch (ApprovalDeniedException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'approving step 2 out of order throws');
        $this->assertSame('pending', $bill->fresh()->approval_status, 'document stays pending');
    }

    public function test_rejected_request_cannot_be_resurrected(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $fd = $this->userWithRoleOnTeam('finance_director', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $svc = app(ApprovalService::class);
        $req = $bill->approvalRequest()->first();
        $step1 = $req->steps()->where('position', 1)->first();
        $step2 = $req->steps()->where('position', 2)->first();

        $svc->reject($step1, $manager, 'over budget');
        $this->assertSame('rejected', $bill->fresh()->approval_status);

        // Defect 6: sibling step is defensively rejected, not left pending.
        $this->assertSame(ApprovalStep::STATUS_REJECTED, $step2->fresh()->status, 'leftover step is rejected too');

        // Defect 1: even a leftover step is no longer actionable.
        $this->assertFalse($svc->canAct($step2->fresh(), $fd), 'rejected request exposes no actionable step');

        $threw = false;
        try {
            $svc->approve($step2->fresh(), $fd);
        } catch (ApprovalDeniedException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'approving a leftover step after rejection throws');
        $this->assertSame('rejected', $bill->fresh()->approval_status, 'document stays rejected (no resurrection)');
    }

    public function test_cannot_act_on_another_teams_step(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        // Outsider holds the same global Shield role but on their own (different) team.
        [$outsiderTeam, $outsider] = $this->userWithRole('manager');
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $step = $bill->approvalRequest()->first()->steps()->first();

        $svc = app(ApprovalService::class);
        $this->assertNotSame($team, $outsiderTeam, 'outsider is on a different team');
        $this->assertTrue($outsider->hasRole('manager'), 'outsider genuinely holds the role');
        $this->assertFalse($svc->canAct($step, $outsider), 'holds role but wrong team');

        $threw = false;
        try {
            $svc->approve($step, $outsider);
        } catch (ApprovalDeniedException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'cross-team approve throws');
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
