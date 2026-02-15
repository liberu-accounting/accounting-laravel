<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BankConnection;
use App\Models\User;
use App\Services\PlaidService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class BankConnectionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function bank_connection_can_be_created()
    {
        $connection = BankConnection::create([
            'user_id' => $this->user->id,
            'bank_id' => 'test_bank_001',
            'institution_name' => 'Test Bank',
            'credentials' => ['username' => 'test'],
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('bank_connections', [
            'id' => $connection->id,
            'bank_id' => 'test_bank_001',
            'institution_name' => 'Test Bank',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function bank_connection_has_user_relationship()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $connection->user);
        $this->assertEquals($this->user->id, $connection->user_id);
    }

    /** @test */
    public function bank_connection_credentials_are_encrypted()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'credentials' => ['password' => 'secret123'],
        ]);

        // Check that credentials are encrypted in the database
        $rawConnection = \DB::table('bank_connections')->find($connection->id);
        $this->assertNotEquals(['password' => 'secret123'], $rawConnection->credentials);
        
        // Check that credentials are decrypted when accessed through the model
        $this->assertEquals(['password' => 'secret123'], $connection->credentials);
    }

    /** @test */
    public function bank_connection_plaid_access_token_is_encrypted()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => 'access-sandbox-token-123',
        ]);

        // Check that token is encrypted in the database
        $rawConnection = \DB::table('bank_connections')->find($connection->id);
        $this->assertNotEquals('access-sandbox-token-123', $rawConnection->plaid_access_token);
        
        // Check that token is decrypted when accessed through the model
        $this->assertEquals('access-sandbox-token-123', $connection->plaid_access_token);
    }

    /** @test */
    public function bank_connection_has_transactions_relationship()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $connection->transactions);
    }

    /** @test */
    public function bank_connection_can_track_plaid_sync_status()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_item_id' => 'item-test-123',
            'plaid_cursor' => null,
            'last_synced_at' => null,
        ]);

        $this->assertNull($connection->plaid_cursor);
        $this->assertNull($connection->last_synced_at);

        // Simulate a sync
        $connection->update([
            'plaid_cursor' => 'cursor-abc-123',
            'last_synced_at' => now(),
        ]);

        $this->assertNotNull($connection->plaid_cursor);
        $this->assertNotNull($connection->last_synced_at);
    }

    /** @test */
    public function bank_connection_supports_different_statuses()
    {
        $statuses = ['active', 'inactive', 'error', 'pending'];

        foreach ($statuses as $status) {
            $connection = BankConnection::factory()->create([
                'user_id' => $this->user->id,
                'status' => $status,
            ]);

            $this->assertEquals($status, $connection->status);
        }
    }

    /** @test */
    public function bank_connection_can_store_plaid_metadata()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'plaid_access_token' => 'access-sandbox-token',
            'plaid_item_id' => 'item-sandbox-123',
            'plaid_institution_id' => 'ins_109508',
            'institution_name' => 'Chase Bank',
        ]);

        $this->assertEquals('item-sandbox-123', $connection->plaid_item_id);
        $this->assertEquals('ins_109508', $connection->plaid_institution_id);
        $this->assertEquals('Chase Bank', $connection->institution_name);
    }

    /** @test */
    public function bank_connection_can_be_disconnected()
    {
        $connection = BankConnection::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'plaid_access_token' => 'access-token',
            'plaid_item_id' => 'item-123',
            'plaid_institution_id' => 'ins-123',
        ]);

        // Simulate disconnection
        $connection->update([
            'status' => 'inactive',
            'plaid_access_token' => null,
            'plaid_item_id' => null,
            'plaid_institution_id' => null,
        ]);

        $connection->refresh();
        
        $this->assertEquals('inactive', $connection->status);
        $this->assertNull($connection->plaid_access_token);
        $this->assertNull($connection->plaid_item_id);
        $this->assertNull($connection->plaid_institution_id);
    }
}
