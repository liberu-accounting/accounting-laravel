<?php

namespace Tests\Unit\Services;

use App\Models\BankConnection;
use App\Services\WiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WiseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WiseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.wise.client_id', 'test_client_id');
        Config::set('services.wise.client_secret', 'test_client_secret');
        Config::set('services.wise.environment', 'sandbox');
        Config::set('services.wise.redirect_uri', 'https://example.com/wise/callback');
        Config::set('services.wise.webhook_public_key', '');

        $this->service = new WiseService();
    }

    public function test_get_authorization_url_returns_correct_url()
    {
        $url = $this->service->getAuthorizationUrl('random_state_string');

        $this->assertStringContainsString('sandbox.transferwise.tech/oauth/v2/authorize', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=random_state_string', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('scope=', $url);
    }

    public function test_get_authorization_url_uses_production_url_when_configured()
    {
        Config::set('services.wise.environment', 'production');
        $service = new WiseService();

        $url = $service->getAuthorizationUrl('state');

        $this->assertStringContainsString('wise.com/oauth/v2/authorize', $url);
        $this->assertStringNotContainsString('sandbox', $url);
    }

    public function test_exchange_authorization_code_returns_tokens()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/oauth/v2/token' => Http::response([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $result = $this->service->exchangeAuthorizationCode('test_auth_code');

        $this->assertEquals('test_access_token', $result['access_token']);
        $this->assertEquals('test_refresh_token', $result['refresh_token']);
        $this->assertEquals(3600, $result['expires_in']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'oauth/v2/token')
                && $request['grant_type'] === 'authorization_code'
                && $request['code'] === 'test_auth_code';
        });
    }

    public function test_exchange_authorization_code_throws_on_failure()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/oauth/v2/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->service->exchangeAuthorizationCode('invalid_code');
    }

    public function test_refresh_access_token_returns_new_tokens()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/oauth/v2/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
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
            'api.sandbox.transferwise.tech/oauth/v2/token' => Http::response([
                'error' => 'invalid_token',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->service->refreshAccessToken('invalid_refresh_token');
    }

    public function test_get_valid_access_token_returns_current_token_when_not_expired()
    {
        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_access_token',
            'wise_refresh_token' => 'refresh_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $token = $this->service->getValidAccessToken($connection);

        $this->assertEquals('valid_access_token', $token);
    }

    public function test_get_valid_access_token_refreshes_when_expired()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/oauth/v2/token' => Http::response([
                'access_token' => 'refreshed_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'expired_access_token',
            'wise_refresh_token' => 'old_refresh_token',
            'wise_token_expires_at' => now()->subMinute(),
        ]);

        $token = $this->service->getValidAccessToken($connection);

        $this->assertEquals('refreshed_access_token', $token);

        $connection->refresh();
        $this->assertEquals('refreshed_access_token', $connection->wise_access_token);
    }

    public function test_get_valid_access_token_throws_when_no_refresh_token_and_expired()
    {
        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'expired_token',
            'wise_refresh_token' => null,
            'wise_token_expires_at' => now()->subMinute(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access token expired and no refresh token available');

        $this->service->getValidAccessToken($connection);
    }

    public function test_get_profiles_returns_profile_list()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v2/profiles' => Http::response([
                [
                    'id' => 12345,
                    'type' => 'personal',
                    'details' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                    ],
                ],
                [
                    'id' => 67890,
                    'type' => 'business',
                    'details' => [
                        'name' => 'Acme Ltd',
                    ],
                ],
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $profiles = $this->service->getProfiles($connection);

        $this->assertIsArray($profiles);
        $this->assertCount(2, $profiles);
        $this->assertEquals(12345, $profiles[0]['id']);
        $this->assertEquals('personal', $profiles[0]['type']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v2/profiles')
                && $request->hasHeader('Authorization', 'Bearer valid_token');
        });
    }

    public function test_get_profiles_throws_on_failure()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v2/profiles' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'invalid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $this->expectException(\Exception::class);
        $this->service->getProfiles($connection);
    }

    public function test_get_balances_returns_balance_list()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v4/profiles/12345/balances*' => Http::response([
                [
                    'id' => 1001,
                    'currency' => 'GBP',
                    'amount' => [
                        'value' => 5000.00,
                        'currency' => 'GBP',
                    ],
                    'type' => 'STANDARD',
                ],
                [
                    'id' => 1002,
                    'currency' => 'USD',
                    'amount' => [
                        'value' => 2000.00,
                        'currency' => 'USD',
                    ],
                    'type' => 'STANDARD',
                ],
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $balances = $this->service->getBalances($connection, 12345);

        $this->assertIsArray($balances);
        $this->assertCount(2, $balances);
        $this->assertEquals('GBP', $balances[0]['currency']);
        $this->assertEquals(5000.00, $balances[0]['amount']['value']);
    }

    public function test_get_balances_throws_on_failure()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v4/profiles/12345/balances*' => Http::response([
                'message' => 'Forbidden',
            ], 403),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'invalid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $this->expectException(\Exception::class);
        $this->service->getBalances($connection, 12345);
    }

    public function test_get_transfers_with_date_range()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v1/transfers*' => Http::response([
                [
                    'id' => 9001,
                    'status' => 'outgoing_payment_sent',
                    'reference' => 'Invoice payment',
                    'sourceValue' => 500.00,
                    'sourceCurrency' => 'GBP',
                    'created' => '2026-01-15T10:30:00Z',
                ],
            ], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $transfers = $this->service->getTransfers($connection, 12345, '2026-01-01', '2026-01-31');

        $this->assertIsArray($transfers);
        $this->assertCount(1, $transfers);
        $this->assertEquals(9001, $transfers[0]['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/transfers')
                && str_contains($request->url(), 'createdDateStart=2026-01-01')
                && str_contains($request->url(), 'createdDateEnd=2026-01-31');
        });
    }

    public function test_get_transfers_limit_capped_at_100()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v1/transfers*' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $this->service->getTransfers($connection, 12345, null, null, 5000);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'limit=100');
        });
    }

    public function test_get_transfers_updates_last_synced_at()
    {
        Http::fake([
            'api.sandbox.transferwise.tech/v1/transfers*' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
            'last_synced_at' => null,
        ]);

        $this->service->getTransfers($connection, 12345);

        $connection->refresh();
        $this->assertNotNull($connection->last_synced_at);
    }

    public function test_service_uses_production_base_url_when_configured()
    {
        Config::set('services.wise.environment', 'production');
        $service = new WiseService();

        Http::fake([
            'api.transferwise.com/v2/profiles' => Http::response([], 200),
        ]);

        $connection = BankConnection::factory()->create([
            'bank_id' => 'wise',
            'wise_access_token' => 'valid_token',
            'wise_token_expires_at' => now()->addHour(),
        ]);

        $service->getProfiles($connection);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.transferwise.com');
        });
    }

    public function test_verify_webhook_signature_with_missing_public_key()
    {
        $bodyJson = '{"event_type":"transfers#state-change"}';
        $signature = base64_encode('fake_signature');

        $result = $this->service->verifyWebhookSignature($bodyJson, $signature, '');

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_with_empty_signature()
    {
        $bodyJson = '{"event_type":"transfers#state-change"}';

        $result = $this->service->verifyWebhookSignature($bodyJson, '', 'some_public_key');

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_with_invalid_base64_signature()
    {
        $bodyJson = '{"event_type":"transfers#state-change"}';

        $result = $this->service->verifyWebhookSignature($bodyJson, '!!!invalid_base64!!!', 'some_public_key');

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_with_invalid_signature()
    {
        // Generate a real RSA key pair for testing
        $keyPair = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $details['key'];

        $bodyJson = '{"event_type":"transfers#state-change"}';

        // Sign with a different private key (i.e., invalid signature)
        $wrongKeyPair = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_sign($bodyJson, $rawSignature, $wrongKeyPair, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($rawSignature);

        $result = $this->service->verifyWebhookSignature($bodyJson, $signature, $publicKeyPem);

        $this->assertFalse($result);
    }

    public function test_verify_webhook_signature_with_valid_signature()
    {
        // Generate a real RSA key pair for testing
        $keyPair = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $details['key'];

        $bodyJson = '{"event_type":"transfers#state-change","data":{"resource":{"id":9001}}}';

        openssl_sign($bodyJson, $rawSignature, $keyPair, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($rawSignature);

        $result = $this->service->verifyWebhookSignature($bodyJson, $signature, $publicKeyPem);

        $this->assertTrue($result);
    }
}
