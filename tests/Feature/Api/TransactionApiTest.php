<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
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
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/transactions');
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_transactions(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/transactions');

        // Accept 200 (empty list) or 500 (misconfigured DB) — the key assertion
        // is that auth was accepted (not 401).
        $response->assertStatus(200);
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
