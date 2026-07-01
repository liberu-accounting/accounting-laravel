<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $this->user = $user->fresh();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/chart-of-accounts')->assertUnauthorized();
    }

    public function test_index_lists_own_team_accounts(): void
    {
        Account::factory()->create(['team_id' => $this->user->current_team_id, 'account_name' => 'Mine']);

        $response = $this->actingAs($this->user)->getJson('/api/chart-of-accounts');

        $response->assertOk()->assertJsonFragment(['account_name' => 'Mine']);
    }

    public function test_index_does_not_leak_other_teams_accounts(): void
    {
        $other = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($other);
        Account::factory()->create(['team_id' => $other->fresh()->current_team_id, 'account_name' => 'Theirs']);

        $this->actingAs($this->user)->getJson('/api/chart-of-accounts')
            ->assertOk()->assertJsonMissing(['account_name' => 'Theirs']);
    }

    public function test_store_creates_account(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/chart-of-accounts', [
            'account_number' => 4100,
            'account_name' => 'Sales Revenue',
            'account_type' => 'revenue',
        ]);

        $response->assertCreated()->assertJsonFragment(['account_name' => 'Sales Revenue']);
        $this->assertDatabaseHas('accounts', [
            'account_number' => 4100,
            'team_id' => $this->user->current_team_id,
        ]);
    }

    public function test_account_number_unique_per_team_not_across_teams(): void
    {
        // Same team: a second account reusing the number is rejected.
        $this->actingAs($this->user)->postJson('/api/chart-of-accounts', [
            'account_number' => 5000, 'account_name' => 'First', 'account_type' => 'asset',
        ])->assertCreated();

        $this->actingAs($this->user)->postJson('/api/chart-of-accounts', [
            'account_number' => 5000, 'account_name' => 'Dup', 'account_type' => 'asset',
        ])->assertStatus(422);

        // Different team: same number is allowed (per-team uniqueness, not global).
        $other = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($other);

        $this->actingAs($other->fresh())->postJson('/api/chart-of-accounts', [
            'account_number' => 5000, 'account_name' => 'Other team', 'account_type' => 'asset',
        ])->assertCreated();
    }

    public function test_show_update_destroy(): void
    {
        $account = Account::factory()->create(['team_id' => $this->user->current_team_id]);

        $this->actingAs($this->user)->getJson("/api/chart-of-accounts/{$account->id}")->assertOk();

        $this->actingAs($this->user)
            ->putJson("/api/chart-of-accounts/{$account->id}", ['account_name' => 'Renamed'])
            ->assertOk()->assertJsonFragment(['account_name' => 'Renamed']);

        $this->actingAs($this->user)->deleteJson("/api/chart-of-accounts/{$account->id}")->assertOk();
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_cannot_access_another_teams_account(): void
    {
        $other = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($other);
        $theirs = Account::factory()->create(['team_id' => $other->fresh()->current_team_id]);

        $this->actingAs($this->user)->getJson("/api/chart-of-accounts/{$theirs->id}")->assertForbidden();
    }
}
