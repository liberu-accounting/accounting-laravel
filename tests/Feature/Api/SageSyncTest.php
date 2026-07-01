<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SageConnection;
use App\Models\User;
use App\Services\SageService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SageSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($this->user);
        $this->user = $this->user->fresh();

        config()->set('services.sage', [
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect_uri' => 'https://app.test/api/sage/callback',
            'authorization_url' => 'https://www.sageone.com/oauth2/auth/central',
            'token_url' => 'https://oauth.accounting.sage.com/token',
            'businesses_url' => 'https://api.accounting.sage.com/v3.1/businesses',
            'api_base_url' => 'https://api.accounting.sage.com/v3.1',
        ]);
    }

    private function connection(): SageConnection
    {
        return SageConnection::create([
            'user_id' => $this->user->id,
            'team_id' => $this->user->current_team_id,
            'business_id' => 'biz-123',
            'access_token' => 'at',
            'refresh_token' => 'rt',
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    private function service(): SageService
    {
        return app(SageService::class);
    }

    public function test_connect_returns_authorization_url(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/sage/connect');

        $response->assertOk()->assertJsonStructure(['authorization_url']);
        $this->assertStringContainsString('sageone.com/oauth2/auth/central', $response->json('authorization_url'));
        $this->assertStringContainsString('client_id=test_client', $response->json('authorization_url'));
    }

    public function test_callback_exchanges_code_and_stores_connection(): void
    {
        Http::fake([
            'oauth.accounting.sage.com/token' => Http::response(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 3600], 200),
            'api.accounting.sage.com/v3.1/businesses' => Http::response(['$items' => [['id' => 'biz-xyz']]], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/sage/callback?code=authcode');

        $response->assertOk();
        $this->assertDatabaseHas('sage_connections', ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id, 'business_id' => 'biz-xyz', 'status' => 'active']);
    }

    public function test_push_invoice_stores_remote_id(): void
    {
        Http::fake(['*/v3.1/sales_invoices*' => Http::response(['id' => 'sage-1'], 200)]);

        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'total_amount' => 150]);

        $this->service()->pushInvoice($invoice, $this->connection());

        $this->assertSame('sage-1', $invoice->fresh()->sage_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v3.1/sales_invoices') && $r->method() === 'POST');
    }

    public function test_pull_invoices_creates_local_invoices(): void
    {
        Http::fake(['*/v3.1/sales_invoices*' => Http::response(['$items' => [
            ['id' => 'sage-9', 'displayed_as' => 'INV-S9', 'total_amount' => 320.50, 'date' => '2026-06-01', 'contact_name' => 'Globex'],
        ]], 200)]);

        $count = $this->service()->pullInvoices($this->connection());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('invoices', ['sage_id' => 'sage-9']);
        $this->assertDatabaseHas('customers', ['customer_name' => 'Globex']);
    }
}
