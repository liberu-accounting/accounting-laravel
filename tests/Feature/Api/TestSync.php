<?php

namespace Tests\Feature\Api;

use App\Models\BankConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestSync extends TestCase
{
    use RefreshDatabase;

    public function test_sync_debug()
    {
        $user = User::factory()->create();
        $connection = BankConnection::factory()->create([
            'user_id' => $user->id,
            'plaid_access_token' => 'access-test-token',
            'status' => 'active',
        ]);

        Http::fake([
            'sandbox.plaid.com/transactions/sync' => Http::response([
                'added' => [['transaction_id' => 'tx_123', 'date' => '2026-02-14', 'name' => 'Test', 'amount' => 25.50, 'pending' => false, 'category' => ['Food']]],
                'modified' => [],
                'removed' => [],
                'next_cursor' => 'cursor_123',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/api/plaid/connections/{$connection->id}/sync");
        
        // Show the response body 
        $this->fail('Response: ' . $response->getContent());
    }
}
