<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/chart-of-accounts')->assertUnauthorized();
    }

    public function test_index_lists_own_accounts(): void
    {
        Account::factory()->create(['user_id' => $this->user->id, 'account_name' => 'Mine']);

        $response = $this->actingAs($this->user)->getJson('/api/chart-of-accounts');

        $response->assertOk()->assertJsonFragment(['account_name' => 'Mine']);
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
            'user_id' => $this->user->id,
        ]);
    }

    public function test_show_update_destroy(): void
    {
        $account = Account::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->getJson("/api/chart-of-accounts/{$account->id}")->assertOk();

        $this->actingAs($this->user)
            ->putJson("/api/chart-of-accounts/{$account->id}", ['account_name' => 'Renamed'])
            ->assertOk()->assertJsonFragment(['account_name' => 'Renamed']);

        $this->actingAs($this->user)->deleteJson("/api/chart-of-accounts/{$account->id}")->assertOk();
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
