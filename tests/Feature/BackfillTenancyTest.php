<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-T Phase 1: idempotent tenancy backfill (teams:backfill-tenancy).
 */
class BackfillTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisions_teams_and_backfills_null_team_id(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);
        $nullRow = Account::factory()->create(['user_id' => $user->id, 'team_id' => null]);

        // A row already scoped to a different team must NOT be reassigned.
        $otherTeam = Team::forceCreate(['user_id' => $user->id, 'name' => 'Books', 'personal_team' => false]);
        $assignedRow = Account::factory()->create(['user_id' => $user->id, 'team_id' => $otherTeam->id]);

        $this->artisan('teams:backfill-tenancy')->assertSuccessful();

        $user->refresh();
        $personal = $user->ownedTeams()->where('personal_team', true)->first();
        $this->assertNotNull($personal, 'personal team provisioned');
        $this->assertSame($personal->id, $user->current_team_id, 'current team selected');
        $this->assertSame($personal->id, $nullRow->fresh()->team_id, 'NULL team_id backfilled from user');
        $this->assertSame($otherTeam->id, $assignedRow->fresh()->team_id, 'already-scoped row untouched');
    }

    public function test_dry_run_writes_nothing(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);
        $row = Account::factory()->create(['user_id' => $user->id, 'team_id' => null]);

        $this->artisan('teams:backfill-tenancy', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($row->fresh()->team_id, 'no write in dry-run');
        $this->assertFalse(
            $user->fresh()->ownedTeams()->where('personal_team', true)->exists(),
            'no team provisioned in dry-run',
        );
    }
}
