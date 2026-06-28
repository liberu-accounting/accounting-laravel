<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Bill;
use App\Models\QboConnection;
use App\Models\User;
use App\Models\Vendor;
use App\Services\QuickBooksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QboAccountBillSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        config()->set('services.qbo', [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'redirect_uri' => 'https://app.test/api/qbo/callback',
            'authorization_url' => 'https://appcenter.intuit.com/connect/oauth2',
            'token_url' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            'api_base_url' => 'https://sandbox-quickbooks.api.intuit.com',
            'webhook_verifier_token' => 'test_verifier',
        ]);
    }

    private function connection(): QboConnection
    {
        return QboConnection::create([
            'user_id' => $this->user->id,
            'realm_id' => '4620816365',
            'access_token' => 'access-test-token',
            'refresh_token' => 'refresh-test-token',
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    private function service(): QuickBooksService
    {
        return app(QuickBooksService::class);
    }

    public function test_push_account_stores_remote_id(): void
    {
        Http::fake(['*/v3/company/*/account*' => Http::response(['Account' => ['Id' => '55', 'SyncToken' => '0']], 200)]);

        $account = Account::factory()->create(['account_type' => 'revenue', 'normal_balance' => 'credit']);

        $this->service()->pushAccount($account, $this->connection());

        $this->assertSame('55', $account->fresh()->qbo_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v3/company/4620816365/account') && $r->method() === 'POST');
    }

    public function test_pull_accounts_creates_local_accounts(): void
    {
        Http::fake(['*/v3/company/*/query*' => Http::response([
            'QueryResponse' => ['Account' => [
                ['Id' => '88', 'Name' => 'Consulting Income', 'Classification' => 'Revenue', 'AcctNum' => '4200'],
            ]],
        ], 200)]);

        $count = $this->service()->pullAccounts($this->connection());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('accounts', ['qbo_id' => '88', 'account_name' => 'Consulting Income', 'account_type' => 'revenue']);
    }

    public function test_push_bill_stores_remote_id(): void
    {
        Http::fake(['*/v3/company/*/bill*' => Http::response(['Bill' => ['Id' => '70', 'SyncToken' => '0']], 200)]);

        $vendor = Vendor::factory()->create();
        $bill = Bill::factory()->create(['vendor_id' => $vendor->vendor_id, 'total_amount' => 480]);

        $this->service()->pushBill($bill, $this->connection());

        $this->assertSame('70', $bill->fresh()->qbo_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v3/company/4620816365/bill') && $r->method() === 'POST');
    }

    public function test_pull_bills_creates_local_bills(): void
    {
        Http::fake(['*/v3/company/*/query*' => Http::response([
            'QueryResponse' => ['Bill' => [
                ['Id' => '91', 'TotalAmt' => 250.00, 'TxnDate' => '2026-06-01', 'VendorRef' => ['value' => '3', 'name' => 'Acme Supplies']],
            ]],
        ], 200)]);

        $count = $this->service()->pullBills($this->connection());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('bills', ['qbo_id' => '91', 'total_amount' => 250.00]);
        $this->assertDatabaseHas('vendors', ['name' => 'Acme Supplies']);
    }
}
