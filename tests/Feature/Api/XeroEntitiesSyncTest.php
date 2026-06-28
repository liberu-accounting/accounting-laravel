<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\XeroConnection;
use App\Services\XeroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XeroEntitiesSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.xero', [
            'client_id' => 'c', 'client_secret' => 's', 'redirect_uri' => 'https://app.test/cb',
            'authorization_url' => 'https://login.xero.com/identity/connect/authorize',
            'token_url' => 'https://identity.xero.com/connect/token',
            'connections_url' => 'https://api.xero.com/connections',
            'api_base_url' => 'https://api.xero.com/api.xro/2.0',
        ]);
    }

    private function connection(): XeroConnection
    {
        return XeroConnection::create([
            'user_id' => User::factory()->create()->id,
            'tenant_id' => 'tenant-123',
            'access_token' => 'at',
            'refresh_token' => 'rt',
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    private function service(): XeroService
    {
        return app(XeroService::class);
    }

    public function test_push_account(): void
    {
        Http::fake(['*/api.xro/2.0/Accounts*' => Http::response(['Accounts' => [['AccountID' => 'xa-1']]], 200)]);
        $account = Account::factory()->create(['account_type' => 'revenue']);

        $this->service()->pushAccount($account, $this->connection());

        $this->assertSame('xa-1', $account->fresh()->xero_id);
    }

    public function test_pull_accounts(): void
    {
        Http::fake(['*/api.xro/2.0/Accounts*' => Http::response(['Accounts' => [
            ['AccountID' => 'xa-9', 'Code' => '4200', 'Name' => 'Sales', 'Class' => 'REVENUE'],
        ]], 200)]);

        $this->assertSame(1, $this->service()->pullAccounts($this->connection()));
        $this->assertDatabaseHas('accounts', ['xero_id' => 'xa-9', 'account_type' => 'revenue']);
    }

    public function test_push_bill(): void
    {
        Http::fake(['*/api.xro/2.0/Invoices*' => Http::response(['Invoices' => [['InvoiceID' => 'xb-1']]], 200)]);
        $bill = Bill::factory()->create(['vendor_id' => Vendor::factory()->create()->vendor_id, 'total_amount' => 480]);

        $this->service()->pushBill($bill, $this->connection());

        $this->assertSame('xb-1', $bill->fresh()->xero_id);
    }

    public function test_pull_bills(): void
    {
        Http::fake(['*/api.xro/2.0/Invoices*' => Http::response(['Invoices' => [
            ['InvoiceID' => 'xb-9', 'Total' => 250, 'Date' => '2026-06-01', 'Contact' => ['Name' => 'Acme Supplies']],
        ]], 200)]);

        $this->assertSame(1, $this->service()->pullBills($this->connection()));
        $this->assertDatabaseHas('bills', ['xero_id' => 'xb-9']);
        $this->assertDatabaseHas('vendors', ['name' => 'Acme Supplies']);
    }

    public function test_push_payment(): void
    {
        Http::fake(['*/api.xro/2.0/Payments*' => Http::response(['Payments' => [['PaymentID' => 'xp-1']]], 200)]);
        $invoice = Invoice::factory()->create(['xero_id' => 'xinv-1']);
        $payment = Payment::create(['invoice_id' => $invoice->id, 'payment_amount' => 150, 'payment_date' => '2026-06-10']);

        $this->service()->pushPayment($payment, $this->connection());

        $this->assertSame('xp-1', $payment->fresh()->xero_id);
    }

    public function test_pull_payments(): void
    {
        $invoice = Invoice::factory()->create(['xero_id' => '500']);
        Http::fake(['*/api.xro/2.0/Payments*' => Http::response(['Payments' => [
            ['PaymentID' => 'xp-9', 'Amount' => 150, 'Date' => '2026-06-10', 'Invoice' => ['InvoiceID' => '500']],
        ]], 200)]);

        $this->assertSame(1, $this->service()->pullPayments($this->connection()));
        $this->assertDatabaseHas('payments', ['xero_id' => 'xp-9', 'invoice_id' => $invoice->id]);
    }
}
