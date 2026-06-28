<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
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
        $this->getJson('/api/invoices')->assertUnauthorized();
    }

    public function test_store_creates_invoice(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total_amount' => 250.00,
            'payment_status' => 'pending',
        ]);

        $response->assertCreated()->assertJsonFragment(['total_amount' => '250.00']);
        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id, 'total_amount' => 250.00]);
    }

    public function test_show_returns_invoice(): void
    {
        $invoice = Invoice::factory()->create(['team_id' => $this->teamId]);

        $this->actingAs($this->user)->getJson("/api/invoices/{$invoice->id}")->assertOk();
    }
}
