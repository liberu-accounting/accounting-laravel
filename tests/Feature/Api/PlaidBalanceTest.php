<?php

namespace Tests\Feature\Api;

use App\Models\BankAccountBalance;
use App\Models\BankConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaidBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BankConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => encrypt('access-test-token'),
            'status' => 'active',
        ]);
    }

    public function test_get_balances_syncs_account_balances()
    {
        Http::fake([
            'sandbox.plaid.com/accounts/balance/get' => Http::response([
                'accounts' => [
                    [
                        'account_id' => 'acc_123',
                        'name' => 'Checking Account',
                        'type' => 'depository',
                        'subtype' => 'checking',
                        'balances' => [
                            'current' => 1250.50,
                            'available' => 1200.00,
                            'iso_currency_code' => 'USD',
                        ],
                    ],
                    [
                        'account_id' => 'acc_456',
                        'name' => 'Credit Card',
                        'type' => 'credit',
                        'subtype' => 'credit card',
                        'balances' => [
                            'current' => -350.75,
                            'available' => 4649.25,
                            'limit' => 5000.00,
                            'iso_currency_code' => 'USD',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/plaid/connections/{$this->connection->id}/balances");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'accounts' => [
                    '*' => [
                        'id',
                        'account_name',
                        'account_type',
                        'account_subtype',
                        'current_balance',
                        'available_balance',
                        'currency',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify balances were stored in database
        $this->assertDatabaseHas('bank_account_balances', [
            'bank_connection_id' => $this->connection->id,
            'plaid_account_id' => 'acc_123',
            'account_name' => 'Checking Account',
            'current_balance' => 1250.50,
        ]);

        $this->assertDatabaseHas('bank_account_balances', [
            'bank_connection_id' => $this->connection->id,
            'plaid_account_id' => 'acc_456',
            'account_name' => 'Credit Card',
            'limit_amount' => 5000.00,
        ]);
    }

    public function test_get_balances_updates_existing_balances()
    {
        // Create existing balance
        $existingBalance = BankAccountBalance::create([
            'bank_connection_id' => $this->connection->id,
            'plaid_account_id' => 'acc_123',
            'account_name' => 'Checking Account',
            'account_type' => 'depository',
            'current_balance' => 1000.00,
            'available_balance' => 950.00,
            'iso_currency_code' => 'USD',
            'last_updated_at' => now()->subDay(),
        ]);

        Http::fake([
            'sandbox.plaid.com/accounts/balance/get' => Http::response([
                'accounts' => [
                    [
                        'account_id' => 'acc_123',
                        'name' => 'Checking Account',
                        'type' => 'depository',
                        'subtype' => 'checking',
                        'balances' => [
                            'current' => 1500.00,
                            'available' => 1450.00,
                            'iso_currency_code' => 'USD',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/plaid/connections/{$this->connection->id}/balances");

        // Verify balance was updated
        $existingBalance->refresh();
        $this->assertEquals(1500.00, $existingBalance->current_balance);
        $this->assertEquals(1450.00, $existingBalance->available_balance);
    }

    public function test_get_balances_prevents_unauthorized_access()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->getJson("/api/plaid/connections/{$this->connection->id}/balances");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_get_balances_rejects_inactive_connection()
    {
        $this->connection->update(['status' => 'disconnected']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/plaid/connections/{$this->connection->id}/balances");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Connection is not active',
            ]);
    }

    public function test_bank_connection_has_balances_relationship()
    {
        BankAccountBalance::create([
            'bank_connection_id' => $this->connection->id,
            'plaid_account_id' => 'acc_123',
            'account_name' => 'Test Account',
            'account_type' => 'depository',
            'current_balance' => 1000.00,
            'iso_currency_code' => 'USD',
        ]);

        $this->assertCount(1, $this->connection->balances);
        $this->assertEquals('Test Account', $this->connection->balances->first()->account_name);
    }
}
