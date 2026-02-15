<?php

namespace Tests\Feature\Api;

use App\Jobs\SyncPlaidTransactionsJob;
use App\Models\BankConnection;
use App\Models\User;
use App\Services\PlaidService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlaidWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BankConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.plaid.webhook_verification_key', 'test_verification_key');
        
        $this->user = User::factory()->create();
        $this->connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_item_id' => 'item_test_123',
            'status' => 'active',
        ]);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $payload = [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => 'item_test_123',
        ];

        $response = $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => 'invalid_signature',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid signature',
            ]);
    }

    public function test_webhook_accepts_valid_signature()
    {
        Queue::fake();

        $payload = [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => $this->connection->plaid_item_id,
        ];

        $bodyJson = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_verification_key', true));

        $response = $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => $signature,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook processed',
            ]);
    }

    public function test_webhook_dispatches_sync_job_for_transactions_update()
    {
        Queue::fake();

        $payload = [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => $this->connection->plaid_item_id,
        ];

        $bodyJson = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_verification_key', true));

        $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => $signature,
        ]);

        Queue::assertPushed(SyncPlaidTransactionsJob::class, function ($job) {
            return $job->connectionId === $this->connection->id;
        });
    }

    public function test_webhook_handles_item_error()
    {
        $payload = [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'ERROR',
            'item_id' => $this->connection->plaid_item_id,
            'error' => [
                'error_code' => 'ITEM_LOGIN_REQUIRED',
                'error_message' => 'User credentials are required',
            ],
        ];

        $bodyJson = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_verification_key', true));

        $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => $signature,
        ]);

        $this->assertDatabaseHas('bank_connections', [
            'id' => $this->connection->id,
            'status' => 'requires_reauth',
        ]);
    }

    public function test_webhook_handles_user_permission_revoked()
    {
        $payload = [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'USER_PERMISSION_REVOKED',
            'item_id' => $this->connection->plaid_item_id,
        ];

        $bodyJson = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_verification_key', true));

        $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => $signature,
        ]);

        $this->assertDatabaseHas('bank_connections', [
            'id' => $this->connection->id,
            'status' => 'revoked',
        ]);
    }

    public function test_webhook_handles_unknown_item_gracefully()
    {
        Queue::fake();

        $payload = [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => 'unknown_item_id',
        ];

        $bodyJson = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_verification_key', true));

        $response = $this->postJson('/api/webhooks/plaid', $payload, [
            'Plaid-Verification' => $signature,
        ]);

        $response->assertStatus(200);
        Queue::assertNothingPushed();
    }

    public function test_webhook_without_verification_key_configured_rejects()
    {
        Config::set('services.plaid.webhook_verification_key', null);

        $payload = [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => 'item_test_123',
        ];

        $response = $this->postJson('/api/webhooks/plaid', $payload);

        $response->assertStatus(401);
    }
}
