<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Bill;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillApiTest extends TestCase
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
        $this->getJson('/api/bills')->assertUnauthorized();
    }

    public function test_store_creates_bill(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/bills', [
            'vendor_id' => $vendor->vendor_id,
            'bill_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total_amount' => 480.00,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('bills', ['vendor_id' => $vendor->vendor_id, 'total_amount' => 480.00]);
    }

    public function test_show_returns_bill(): void
    {
        $bill = Bill::factory()->create(['team_id' => $this->teamId]);

        $this->actingAs($this->user)->getJson("/api/bills/{$bill->bill_id}")->assertOk();
    }
}
