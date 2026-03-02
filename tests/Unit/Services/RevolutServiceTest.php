<?php

namespace Tests\Unit\Services;

use App\Models\BankConnection;
use App\Services\RevolutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RevolutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RevolutService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.revolut.client_id', 'test_client_id');
        Config::set('services.revolut.client_secret', 'test_client_secret');
        Config::set('services.revolut.environment', 'sandbox');
        Config::set('services.revolut.redirect_uri', 'https://example.com/revolut/callback');
        Config::set('services.revolut.webhook_secret', 'test_webhook_secret');

        $this->service = new RevolutService();
    }

    public function test_get_authorization_url_returns_correct_url()
    {
        $url = $this->service->getAuthorizationUrl('random_state_string');

        $this->assertStringContainsString('sandbox-business.revolut.com/app-confirm', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=random_state_string', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
    }

    public function test_get_authorization_url_uses_production_url_when_configured()
    {
        Config::set('services.revolut.environment', 'production');
        $service = new RevolutService();

        $url = $service->getAuthorizationUrl('state');

        $this->assertStringContainsString('business.revolut.com/app-confirm', $url);
        $this->assertStringNotContainsString('sandbox-', $url);
    }

    public function test_exchange_authorization_code_returns_tokens()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/auth/token' => Http::response([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'expires_in' => 2400,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $result = $this->service->exchangeAuthorizationCode('test_auth_code');

        $this->assertEquals('test_access_token', $result['access_token']);
        $this->assertEquals('test_refresh_token', $result['refresh_token']);
        $this->assertEquals(2400, $result['expires_in']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'auth/token')
                && $request['grant_type'] === 'authorization_code'
                && $request['code'] === 'test_auth_code'
                && $request['client_id'] === 'test_client_id';
        });
    }

    public function test_exchange_authorization_code_throws_on_failure()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/auth/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->service->exchangeAuthorizationCode('invalid_code');
    }

    public function test_refresh_access_token_returns_new_tokens()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/auth/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 2400,
            ], 200),
        ]);

        $result = $this->service->refreshAccessToken('old_refresh_token');

        $this->assertEquals('new_access_token', $result['access_token']);

        Http::assertSent(function ($request) {
            return $request['grant_type'] === 'refresh_token'
                && $request['refresh_token'] === 'old_refresh_token';
        });
    }

    public function test_refresh_access_token_throws_on_failure()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/auth/token' => Http::response([
                'error' => 'invalid_token',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->service->refreshAccessToken('invalid_refresh_token');
    }

    public function test_get_valid_access_token_returns_current_token_when_not_expired()
    {
        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_access_token'),
            'revolut_refresh_token' => encrypt('refresh_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $token = $this->service->getValidAccessToken($connection);

        $this->assertEquals('valid_access_token', $token);
    }

    public function test_get_valid_access_token_refreshes_when_expired()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/auth/token' => Http::response([
                'access_token' => 'refreshed_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 2400,
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('expired_access_token'),
            'revolut_refresh_token' => encrypt('old_refresh_token'),
            'revolut_token_expires_at' => now()->subMinute(),
        ]);

        $token = $this->service->getValidAccessToken($connection);

        $this->assertEquals('refreshed_access_token', $token);

        $connection->refresh();
        $this->assertEquals('refreshed_access_token', $connection->revolut_access_token);
    }

    public function test_get_valid_access_token_throws_when_no_refresh_token_and_expired()
    {
        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('expired_token'),
            'revolut_refresh_token' => null,
            'revolut_token_expires_at' => now()->subMinute(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access token expired and no refresh token available');

        $this->service->getValidAccessToken($connection);
    }

    public function test_get_accounts_returns_account_list()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/accounts' => Http::response([
                [
                    'id' => 'acc_001',
                    'name' => 'GBP Business Account',
                    'balance' => 10000.00,
                    'currency' => 'GBP',
                    'type' => 'current',
                    'state' => 'active',
                ],
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $accounts = $this->service->getAccounts($connection);

        $this->assertIsArray($accounts);
        $this->assertCount(1, $accounts);
        $this->assertEquals('acc_001', $accounts[0]['id']);
        $this->assertEquals('GBP Business Account', $accounts[0]['name']);
        $this->assertEquals(10000.00, $accounts[0]['balance']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/accounts')
                && $request->hasHeader('Authorization', 'Bearer valid_token');
        });
    }

    public function test_get_accounts_throws_on_failure()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/accounts' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('invalid_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $this->expectException(\Exception::class);
        $this->service->getAccounts($connection);
    }

    public function test_get_transactions_with_date_range()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/transactions*' => Http::response([
                [
                    'id' => 'tx_001',
                    'type' => 'transfer',
                    'state' => 'completed',
                    'reference' => 'Invoice payment',
                    'legs' => [
                        [
                            'amount' => -500.00,
                            'currency' => 'GBP',
                        ],
                    ],
                    'completed_at' => '2026-01-15T10:30:00Z',
                ],
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $transactions = $this->service->getTransactions($connection, '2026-01-01', '2026-01-31');

        $this->assertIsArray($transactions);
        $this->assertCount(1, $transactions);
        $this->assertEquals('tx_001', $transactions[0]['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/transactions')
                && str_contains($request->url(), 'from=2026-01-01')
                && str_contains($request->url(), 'to=2026-01-31');
        });
    }

    public function test_get_transactions_count_capped_at_1000()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/transactions*' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $this->service->getTransactions($connection, null, null, 5000);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'count=1000');
        });
    }

    public function test_get_transactions_updates_last_synced_at()
    {
        Http::fake([
            'sandbox-b2b.revolut.com/api/1.0/transactions*' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_token'),
            'revolut_token_expires_at' => now()->addHour(),
            'last_synced_at' => null,
        ]);

        $this->service->getTransactions($connection);

        $connection->refresh();
        $this->assertNotNull($connection->last_synced_at);
    }

    public function test_service_uses_production_base_url_when_configured()
    {
        Config::set('services.revolut.environment', 'production');
        $service = new RevolutService();

        Http::fake([
            'b2b.revolut.com/api/1.0/accounts' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'revolut',
            'revolut_access_token' => encrypt('valid_token'),
            'revolut_token_expires_at' => now()->addHour(),
        ]);

        $service->getAccounts($connection);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'b2b.revolut.com');
        });
    }

    public function test_verify_webhook_signature_with_valid_signature()
    {
        $bodyJson = '{"event":"TransactionCreated","data":{"id":"tx_001"}}';
        $signature = 'v1=' . hash_hmac('sha256', $bodyJson, 'test_webhook_secret');

        $result = $this->service->verifyWebhookSignature($bodyJson, $signature);

        $this->assertTrue($result);
    }

    public function test_verify_webhook_signature_with_invalid_signature()
    {
        $bodyJson = '{"event":"TransactionCreated"}';

        $result = $this->service->verifyWebhookSignature($bodyJson, 'v1=invalidsignature');

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_without_secret_configured()
    {
        Config::set('services.revolut.webhook_secret', null);

        $bodyJson = '{"event":"TransactionCreated"}';
        $signature = 'v1=' . hash_hmac('sha256', $bodyJson, 'some_secret');

        $result = $this->service->verifyWebhookSignature($bodyJson, $signature);

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_with_empty_signature()
    {
        $bodyJson = '{"event":"TransactionCreated"}';

        $result = $this->service->verifyWebhookSignature($bodyJson, '');

        $this->assertFalse($result);
    }
}
