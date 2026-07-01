<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Models\ApprovalRule;
use App\Models\Bill;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovableTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_below_threshold_auto_approves_no_request(): void
    {
        Notification::fake();
        $team = $this->actingTeam();
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager'], 'is_active' => true]);

        // Bill's real amount column is `total_amount` (the plan's draft used `total`, which
        // doesn't exist on this model — verified against the bills migration).
        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 100, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $this->assertSame('approved', $bill->fresh()->approval_status);
        $this->assertNull($bill->approvalRequest()->first());
    }

    public function test_at_threshold_creates_request_and_pending_steps(): void
    {
        Notification::fake();
        $team = $this->actingTeam();
        // dispatchToRole() (Task 4) queries Spatie roles, so the steps' roles must exist.
        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('finance_director', 'web');
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager', 'finance_director'], 'deadline_days' => 3, 'is_active' => true]);

        $bill = Bill::factory()->create(['team_id' => $team, 'total_amount' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $req = $bill->approvalRequest()->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $bill->fresh()->approval_status);
        $this->assertCount(2, $req->steps);
        $this->assertNotNull($req->steps->first()->deadline_at, 'step 1 has a deadline');
    }

    private function actingTeam(): int
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();
        $this->actingAs($user);

        return (int) $user->current_team_id;
    }
}
