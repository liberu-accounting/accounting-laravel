<?php

namespace Tests\Unit\Services;

use App\Models\BankConnection;
use App\Services\PlaidService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaidServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlaidService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.plaid.client_id', 'test_client_id');
        Config::set('services.plaid.secret', 'test_secret');
        Config::set('services.plaid.environment', 'sandbox');
        
        $this->service = new PlaidService();
    }

    public function test_create_link_token_sends_correct_request()
    {
        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-sandbox-test-token',
                'expiration' => '2026-02-15T00:00:00Z',
            ], 200),
        ]);

        $result = $this->service->createLinkToken(123, 'en');

        $this->assertEquals('link-sandbox-test-token', $result['link_token']);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.plaid.com/link/token/create'
                && $request['client_id'] === 'test_client_id'
                && $request['user']['client_user_id'] === '123'
                && $request['language'] === 'en';
        });
    }

    public function test_exchange_public_token_returns_access_token()
    {
        Http::fake([
            'sandbox.plaid.com/item/public_token/exchange' => Http::response([
                'access_token' => 'access-sandbox-test-token',
                'item_id' => 'item-test-123',
            ], 200),
        ]);

        $result = $this->service->exchangePublicToken('public-test-token');

        $this->assertEquals('access-sandbox-test-token', $result['access_token']);
        $this->assertEquals('item-test-123', $result['item_id']);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.plaid.com/item/public_token/exchange'
                && $request['public_token'] === 'public-test-token';
        });
    }

    public function test_get_institution_returns_institution_data()
    {
        Http::fake([
            'sandbox.plaid.com/institutions/get_by_id' => Http::response([
                'institution' => [
                    'institution_id' => 'ins_123',
                    'name' => 'Test Bank',
                    'products' => ['transactions'],
                ],
            ], 200),
        ]);

        $result = $this->service->getInstitution('ins_123');

        $this->assertArrayHasKey('institution', $result);
        $this->assertEquals('ins_123', $result['institution']['institution_id']);
    }

    public function test_sync_transactions_without_cursor()
    {
        $connection = BankConnection::factory()->create([
            'plaid_access_token' => encrypt('access-test-token'),
            'plaid_cursor' => null,
        ]);

        Http::fake([
            'sandbox.plaid.com/transactions/sync' => Http::response([
                'added' => [
                    [
                        'transaction_id' => 'tx_123',
                        'amount' => 25.50,
                        'date' => '2026-02-14',
                    ],
                ],
                'modified' => [],
                'removed' => [],
                'next_cursor' => 'new_cursor_123',
            ], 200),
        ]);

        $result = $this->service->syncTransactions($connection);

        $this->assertCount(1, $result['added']);
        $this->assertEquals('new_cursor_123', $result['next_cursor']);
        
        // Verify cursor was saved
        $connection->refresh();
        $this->assertEquals('new_cursor_123', $connection->plaid_cursor);
        $this->assertNotNull($connection->last_synced_at);
    }

    public function test_sync_transactions_with_existing_cursor()
    {
        $connection = BankConnection::factory()->create([
            'plaid_access_token' => encrypt('access-test-token'),
            'plaid_cursor' => 'existing_cursor',
        ]);

        Http::fake([
            'sandbox.plaid.com/transactions/sync' => Http::response([
                'added' => [],
                'modified' => [],
                'removed' => [],
                'next_cursor' => 'updated_cursor',
            ], 200),
        ]);

        $result = $this->service->syncTransactions($connection);

        Http::assertSent(function ($request) {
            return $request['cursor'] === 'existing_cursor';
        });

        $connection->refresh();
        $this->assertEquals('updated_cursor', $connection->plaid_cursor);
    }

    public function test_get_accounts_returns_account_list()
    {
        Http::fake([
            'sandbox.plaid.com/accounts/get' => Http::response([
                'accounts' => [
                    [
                        'account_id' => 'acc_123',
                        'name' => 'Checking',
                        'type' => 'depository',
                        'subtype' => 'checking',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getAccounts('access-test-token');

        $this->assertArrayHasKey('accounts', $result);
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals('Checking', $result['accounts'][0]['name']);
    }

    public function test_remove_item_calls_plaid_api()
    {
        Http::fake([
            'sandbox.plaid.com/item/remove' => Http::response([
                'removed' => true,
            ], 200),
        ]);

        $result = $this->service->removeItem('access-test-token');

        $this->assertTrue($result);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.plaid.com/item/remove'
                && $request['access_token'] === 'access-test-token';
        });
    }

    public function test_service_uses_correct_base_url_for_development()
    {
        Config::set('services.plaid.environment', 'development');
        $service = new PlaidService();

        Http::fake([
            'development.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-dev-token',
            ], 200),
        ]);

        $service->createLinkToken(123);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'development.plaid.com');
        });
    }

    public function test_service_uses_correct_base_url_for_production()
    {
        Config::set('services.plaid.environment', 'production');
        $service = new PlaidService();

        Http::fake([
            'production.plaid.com/link/token/create' => Http::response([
                'link_token' => 'link-prod-token',
            ], 200),
        ]);

        $service->createLinkToken(123);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'production.plaid.com');
        });
    }

    public function test_create_link_token_throws_exception_on_failure()
    {
        Http::fake([
            'sandbox.plaid.com/link/token/create' => Http::response([
                'error_code' => 'INVALID_CREDENTIALS',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->service->createLinkToken(123);
    }

    public function test_exchange_public_token_throws_exception_on_failure()
    {
        Http::fake([
            'sandbox.plaid.com/item/public_token/exchange' => Http::response([
                'error_code' => 'INVALID_PUBLIC_TOKEN',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->service->exchangePublicToken('invalid-token');
    }

    public function test_get_balances_returns_account_balances()
    {
        Http::fake([
            'sandbox.plaid.com/accounts/balance/get' => Http::response([
                'accounts' => [
                    [
                        'account_id' => 'acc_123',
                        'name' => 'Checking',
                        'balances' => [
                            'current' => 1250.50,
                            'available' => 1200.00,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getBalances('access-test-token');

        $this->assertArrayHasKey('accounts', $result);
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals('Checking', $result['accounts'][0]['name']);
        $this->assertEquals(1250.50, $result['accounts'][0]['balances']['current']);
    }

    public function test_get_balances_with_account_ids_filter()
    {
        Http::fake([
            'sandbox.plaid.com/accounts/balance/get' => Http::response([
                'accounts' => [
                    [
                        'account_id' => 'acc_123',
                        'name' => 'Checking',
                    ],
                ],
            ], 200),
        ]);

        $this->service->getBalances('access-test-token', ['acc_123', 'acc_456']);

        Http::assertSent(function ($request) {
            return isset($request['options']['account_ids'])
                && $request['options']['account_ids'] === ['acc_123', 'acc_456'];
        });
    }

    public function test_verify_webhook_signature_with_valid_signature()
    {
        Config::set('services.plaid.webhook_verification_key', 'test_secret_key');
        
        $bodyJson = '{"webhook_type":"TRANSACTIONS","webhook_code":"SYNC_UPDATES_AVAILABLE"}';
        $signature = base64_encode(hash_hmac('sha256', $bodyJson, 'test_secret_key', true));
        
        $result = $this->service->verifyWebhookSignature($bodyJson, [
            'Plaid-Verification' => $signature,
        ]);

        $this->assertTrue($result);
    }

    public function test_verify_webhook_signature_with_invalid_signature()
    {
        Config::set('services.plaid.webhook_verification_key', 'test_secret_key');
        
        $bodyJson = '{"webhook_type":"TRANSACTIONS"}';
        
        $result = $this->service->verifyWebhookSignature($bodyJson, [
            'Plaid-Verification' => 'invalid_signature',
        ]);

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_without_verification_key()
    {
        Config::set('services.plaid.webhook_verification_key', null);
        
        $bodyJson = '{"webhook_type":"TRANSACTIONS"}';
        
        $result = $this->service->verifyWebhookSignature($bodyJson, [
            'Plaid-Verification' => 'some_signature',
        ]);

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_without_header()
    {
        Config::set('services.plaid.webhook_verification_key', 'test_secret_key');
        
        $bodyJson = '{"webhook_type":"TRANSACTIONS"}';
        
        $result = $this->service->verifyWebhookSignature($bodyJson, []);

        $this->assertFalse($result);
    }
}
