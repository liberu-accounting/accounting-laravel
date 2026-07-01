<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($this->user);
        $this->user = $this->user->fresh();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/transactions');
        $response->assertUnauthorized();
    }

    public function test_list_returns_only_the_acting_teams_transactions(): void
    {
        $mine = Transaction::factory()->create(['team_id' => $this->user->current_team_id, 'description' => 'MINE-TX']);

        $other = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($other);
        Transaction::factory()->create(['team_id' => $other->fresh()->current_team_id, 'description' => 'THEIRS-TX']);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonFragment(['id' => $mine->transaction_id, 'description' => 'MINE-TX'])
            ->assertJsonMissing(['description' => 'THEIRS-TX']);
    }

    public function test_cannot_view_another_teams_transaction(): void
    {
        // Transaction owned by a second user's personal team.
        $other = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($other);
        $theirs = Transaction::factory()->create([
            'team_id' => $other->fresh()->current_team_id,
            'description' => 'THEIRS-TX',
        ]);

        // Cross-team access is forbidden (guard aborts before rendering).
        Sanctum::actingAs($this->user);
        $this->getJson('/api/transactions/'.$theirs->getKey())->assertForbidden();
    }

    public function test_user_endpoint_returns_authenticated_user(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user');
        $response->assertOk()->assertJsonFragment(['email' => $this->user->email]);
    }

    public function test_unauthenticated_user_endpoint_is_rejected(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertUnauthorized();
    }
}
