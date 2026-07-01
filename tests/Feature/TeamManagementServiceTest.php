<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-T Phase 0: team provisioning (TeamManagementService was missing → registration
 * fataled and no personal teams existed, blocking a deterministic team_id backfill).
 */
class TeamManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_personal_team_and_selects_it(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace', 'current_team_id' => null]);

        $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user->refresh();

        $this->assertTrue((bool) $team->personal_team);
        $this->assertSame($user->getKey(), $team->user_id, 'user owns the team');
        $this->assertSame($team->getKey(), $user->current_team_id, 'personal team is selected');
        $this->assertSame(1, $user->ownedTeams()->where('personal_team', true)->count());
    }

    public function test_provisioning_is_idempotent(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);
        $svc = app(TeamManagementService::class);

        $svc->createPersonalTeamForUser($user);
        $svc->assignUserToDefaultTeam($user);        // second entry point
        $svc->createPersonalTeamForUser($user);      // repeat

        // Never a second personal team.
        $this->assertSame(1, $user->fresh()->ownedTeams()->where('personal_team', true)->count());
    }
}
