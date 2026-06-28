<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $team = Team::forceCreate(['user_id' => $this->user->id, 'name' => 'Test', 'personal_team' => true]);
        $this->user->forceFill(['current_team_id' => $team->id])->save();
        $this->teamId = $team->id;
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/estimates')->assertUnauthorized();
    }

    public function test_store_creates_estimate(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/estimates', [
            'customer_id' => $customer->id,
            'estimate_date' => '2026-06-01',
            'total_amount' => 900.00,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('estimates', ['customer_id' => $customer->id, 'total_amount' => 900.00]);
    }

    public function test_show_returns_estimate(): void
    {
        $estimate = Estimate::factory()->create(['team_id' => $this->teamId]);

        $this->actingAs($this->user)->getJson("/api/estimates/{$estimate->estimate_id}")->assertOk();
    }
}
