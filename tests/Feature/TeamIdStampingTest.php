<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-T Phase 2a: non-Filament creates stamp the tenant's team_id from the acting
 * user's current team. Additive — never changes read scoping, never forces a value
 * when there's no current team (the four core hooks are identical; Account is
 * representative). NB: team_id has no working DB default (the master migration's
 * `->default(1)` is chained after `->constrained()`, a no-op), so tenantless =>
 * NULL — the hook must not throw or invent a value there.
 */
class TeamIdStampingTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_id_stamped_from_current_team_on_create(): void
    {
        $user = User::factory()->create();
        $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $this->actingAs($user->fresh());

        $account = Account::factory()->create(['team_id' => null, 'user_id' => null]);

        $this->assertSame($team->getKey(), $account->fresh()->team_id, 'tenant stamped');
        $this->assertSame($user->getKey(), $account->fresh()->user_id, 'user_id still stamped');
    }

    public function test_no_current_team_leaves_team_id_null_without_error(): void
    {
        // No authenticated user → hook must not force a value or throw.
        $account = Account::factory()->create();

        $this->assertNull($account->fresh()->team_id);
    }
}
