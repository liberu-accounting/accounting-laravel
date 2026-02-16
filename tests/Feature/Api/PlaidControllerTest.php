<?php

namespace Tests\Feature\Api;

use App\Models\BankConnection;
use App\Models\User;
use App\Services\PlaidService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaidControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_create_link_token_returns_successful_response()
    {
        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-sandbox-test-token',
                'expiration' => '2026-02-15T00:00:00Z',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/create-link-token', [
                'language' => 'en',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'link_token',
                'expiration',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_create_link_token_handles_plaid_error()
    {
        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'error_code' => 'INVALID_REQUEST',
                'error_message' => 'Invalid credentials',
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/create-link-token');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_store_connection_creates_bank_connection()
    {
        Http::fake([
            'sandbox.plaid.com/item/public_token/exchange' => Http::response([
                'access_token' => 'access-sandbox-test-token',
                'item_id' => 'item-test-123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/store-connection', [
                'public_token' => 'public-sandbox-test-token',
                'institution_id' => 'ins_123',
                'institution_name' => 'Test Bank',
                'accounts' => [
                    ['id' => 'acc_123', 'name' => 'Checking'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'connection' => [
                    'id',
                    'institution_name',
                    'status',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bank_connections', [
            'user_id' => $this->user->id,
            'institution_name' => 'Test Bank',
            'plaid_item_id' => 'item-test-123',
            'status' => 'active',
        ]);
    }

    public function test_store_connection_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/store-connection', [
                'public_token' => 'test-token',
                // Missing required fields
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_list_connections_returns_user_connections()
    {
        BankConnection::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create connections for another user (should not be returned)
        $otherUser = User::factory()->create();
        BankConnection::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/plaid/connections');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'connections')
            ->assertJsonStructure([
                'success',
                'connections' => [
                    '*' => [
                        'id',
                        'institution_name',
                        'status',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_sync_transactions_syncs_from_plaid()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => encrypt('access-test-token'),
            'status' => 'active',
        ]);

        Http::fake([
            'sandbox.plaid.com/transactions/sync' => Http::response([
                'added' => [
                    [
                        'transaction_id' => 'tx_123',
                        'date' => '2026-02-14',
                        'name' => 'Test Transaction',
                        'amount' => 25.50,
                        'pending' => false,
                        'category' => ['Food and Drink', 'Restaurants'],
                    ],
                ],
                'modified' => [],
                'removed' => [],
                'next_cursor' => 'cursor_123',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/plaid/connections/{$connection->id}/sync");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'summary' => [
                    'added',
                    'modified',
                    'removed',
                    'total_processed',
                ],
                'last_synced_at',
            ])
            ->assertJson([
                'success' => true,
                'summary' => [
                    'added' => 1,
                    'modified' => 0,
                    'removed' => 0,
                ],
            ]);

        // Verify cursor was updated
        $this->assertDatabaseHas('bank_connections', [
            'id' => $connection->id,
            'plaid_cursor' => 'cursor_123',
        ]);
    }

    public function test_sync_transactions_prevents_unauthorized_access()
    {
        $otherUser = User::factory()->create();
        $connection = BankConnection::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/plaid/connections/{$connection->id}/sync");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_sync_transactions_rejects_inactive_connection()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'disconnected',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/plaid/connections/{$connection->id}/sync");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Connection is not active',
            ]);
    }

    public function test_remove_connection_disconnects_bank()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => encrypt('access-test-token'),
            'status' => 'active',
        ]);

        Http::fake([
            'sandbox.plaid.com/item/remove' => Http::response([
                'removed' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/plaid/connections/{$connection->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bank connection removed successfully',
            ]);

        $this->assertDatabaseHas('bank_connections', [
            'id' => $connection->id,
            'status' => 'disconnected',
        ]);
    }

    public function test_remove_connection_prevents_unauthorized_access()
    {
        $otherUser = User::factory()->create();
        $connection = BankConnection::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/plaid/connections/{$connection->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_unauthenticated_requests_are_rejected()
    {
        $response = $this->postJson('/api/plaid/create-link-token');
        $response->assertStatus(401);

        $response = $this->getJson('/api/plaid/connections');
        $response->assertStatus(401);
    }

    public function test_create_link_token_includes_oauth_redirect_uri_when_configured()
    {
        config(['services.plaid.oauth_redirect_uri' => 'https://example.com/api/plaid/oauth-redirect']);

        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-sandbox-oauth-token',
                'expiration' => '2026-02-15T00:00:00Z',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/create-link-token', [
                'language' => 'en',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify the HTTP request included redirect_uri
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);
            return isset($data['redirect_uri']) && 
                   $data['redirect_uri'] === 'https://example.com/api/plaid/oauth-redirect';
        });
    }

    public function test_create_link_token_supports_update_mode_for_reauthentication()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => encrypt('access-test-token'),
            'status' => 'requires_reauth',
        ]);

        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-sandbox-update-token',
                'expiration' => '2026-02-15T00:00:00Z',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/create-link-token', [
                'language' => 'en',
                'connection_id' => $connection->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify the HTTP request included access_token for update mode
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);
            return isset($data['access_token']) && 
                   $data['access_token'] === 'access-test-token';
        });
    }

    public function test_create_link_token_update_mode_rejects_unauthorized_connection()
    {
        $otherUser = User::factory()->create();
        $connection = BankConnection::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/plaid/create-link-token', [
                'connection_id' => $connection->id,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Connection not found or unauthorized',
            ]);
    }

    public function test_oauth_redirect_endpoint_handles_valid_state()
    {
        $response = $this->getJson('/api/plaid/oauth-redirect?oauth_state_id=test-oauth-state-123');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OAuth redirect received successfully',
                'oauth_state_id' => 'test-oauth-state-123',
            ]);
    }

    public function test_oauth_redirect_endpoint_requires_state_id()
    {
        $response = $this->getJson('/api/plaid/oauth-redirect');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Missing OAuth state ID',
            ]);
    }
}
