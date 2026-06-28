<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\XeroConnection;
use App\Services\XeroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XeroSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        config()->set('services.xero', [
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect_uri' => 'https://app.test/api/xero/callback',
            'authorization_url' => 'https://login.xero.com/identity/connect/authorize',
            'token_url' => 'https://identity.xero.com/connect/token',
            'connections_url' => 'https://api.xero.com/connections',
            'api_base_url' => 'https://api.xero.com/api.xro/2.0',
        ]);
    }

    private function connection(): XeroConnection
    {
        return XeroConnection::create([
            'user_id' => $this->user->id,
            'tenant_id' => 'tenant-123',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    private function service(): XeroService
    {
        return app(XeroService::class);
    }

    public function test_connect_returns_authorization_url(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/xero/connect');

        $response->assertOk()->assertJsonStructure(['authorization_url']);
        $this->assertStringContainsString('login.xero.com/identity/connect/authorize', $response->json('authorization_url'));
        $this->assertStringContainsString('client_id=test_client', $response->json('authorization_url'));
    }

    public function test_callback_exchanges_code_and_stores_connection(): void
    {
        Http::fake([
            'identity.xero.com/connect/token' => Http::response(['access_token' => 'at', 'refresh_token' => 'rt', 'expires_in' => 1800], 200),
            'api.xero.com/connections' => Http::response([['tenantId' => 'tenant-xyz']], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/xero/callback?code=authcode');

        $response->assertOk();
        $this->assertDatabaseHas('xero_connections', ['user_id' => $this->user->id, 'tenant_id' => 'tenant-xyz', 'status' => 'active']);
    }

    public function test_push_invoice_stores_remote_id(): void
    {
        Http::fake(['*/api.xro/2.0/Invoices*' => Http::response(['Invoices' => [['InvoiceID' => 'xero-guid-1']]], 200)]);

        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'total_amount' => 150]);

        $this->service()->pushInvoice($invoice, $this->connection());

        $this->assertSame('xero-guid-1', $invoice->fresh()->xero_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api.xro/2.0/Invoices') && $r->method() === 'POST');
    }

    public function test_pull_invoices_creates_local_invoices(): void
    {
        Http::fake(['*/api.xro/2.0/Invoices*' => Http::response([
            'Invoices' => [[
                'InvoiceID' => 'xero-guid-9',
                'InvoiceNumber' => 'INV-X9',
                'Total' => 320.50,
                'Date' => '2026-06-01',
                'Contact' => ['Name' => 'Globex'],
            ]],
        ], 200)]);

        $count = $this->service()->pullInvoices($this->connection());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('invoices', ['xero_id' => 'xero-guid-9', 'invoice_number' => 'INV-X9']);
        $this->assertDatabaseHas('customers', ['customer_name' => 'Globex']);
    }
}
