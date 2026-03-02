<?php

namespace App\Services;

use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RevolutService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $environment;
    protected string $baseUrl;
    protected string $authUrl;

    public function __construct()
    {
        $this->clientId = config('services.revolut.client_id');
        $this->clientSecret = config('services.revolut.client_secret');
        $this->environment = config('services.revolut.environment', 'sandbox');

        $this->baseUrl = $this->environment === 'production'
            ? 'https://b2b.revolut.com/api/1.0'
            : 'https://sandbox-b2b.revolut.com/api/1.0';

        $this->authUrl = $this->environment === 'production'
            ? 'https://business.revolut.com/app-confirm'
            : 'https://sandbox-business.revolut.com/app-confirm';
    }

    /**
     * Generate the OAuth authorization URL to redirect the user to Revolut
     */
    public function getAuthorizationUrl(string $state): string
    {
        $redirectUri = config('services.revolut.redirect_uri');

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "{$this->authUrl}?{$params}";
    }

    /**
     * Exchange an authorization code for access and refresh tokens
     */
    public function exchangeAuthorizationCode(string $code): array
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->post("{$this->baseUrl}/auth/token", [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => config('services.revolut.redirect_uri'),
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to exchange authorization code: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Revolut authorization code exchange failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Refresh an access token using a refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->post("{$this->baseUrl}/auth/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to refresh access token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Revolut token refresh failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a valid access token for a connection, refreshing if necessary
     */
    public function getValidAccessToken(BankConnection $connection): string
    {
        // Check if the access token is expired (with 60-second buffer)
        if ($connection->revolut_token_expires_at && now()->addSeconds(60)->isAfter($connection->revolut_token_expires_at)) {
            if (!$connection->revolut_refresh_token) {
                throw new Exception('Access token expired and no refresh token available');
            }

            $tokenData = $this->refreshAccessToken($connection->revolut_refresh_token);

            $connection->update([
                'revolut_access_token' => $tokenData['access_token'],
                'revolut_refresh_token' => $tokenData['refresh_token'] ?? $connection->revolut_refresh_token,
                'revolut_token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 2400),
            ]);
        }

        return $connection->revolut_access_token;
    }

    /**
     * Get all accounts for a connection
     */
    public function getAccounts(BankConnection $connection): array
    {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/accounts");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get accounts: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Revolut get accounts failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get details for a specific account
     */
    public function getAccount(BankConnection $connection, string $accountId): array
    {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/accounts/{$accountId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get account: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Revolut get account failed', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transactions for a connection within a date range
     *
     * @param BankConnection $connection
     * @param string|null $from ISO 8601 date string (e.g. '2024-01-01')
     * @param string|null $to ISO 8601 date string (e.g. '2024-01-31')
     * @param int $count Maximum number of transactions (default 100, max 1000)
     */
    public function getTransactions(BankConnection $connection, ?string $from = null, ?string $to = null, int $count = 100): array
    {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $params = ['count' => min($count, 1000)];

            if ($from) {
                $params['from'] = $from;
            }

            if ($to) {
                $params['to'] = $to;
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 200, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                           ($exception instanceof \Illuminate\Http\Client\RequestException &&
                            $exception->response->status() >= 500);
                })
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/transactions", $params);

            if ($response->successful()) {
                $transactions = $response->json();

                $connection->update(['last_synced_at' => now()]);

                return $transactions;
            }

            throw new Exception('Failed to get transactions: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Revolut get transactions failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify the webhook signature from Revolut
     *
     * @param string $bodyJson The raw JSON body of the webhook request
     * @param string $signature The signature from the 'Revolut-Signature' header
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(string $bodyJson, string $signature): bool
    {
        $signingSecret = config('services.revolut.webhook_secret');

        if (empty($signingSecret)) {
            Log::warning('Revolut webhook signing secret not configured - rejecting webhook');
            return false;
        }

        if (empty($signature)) {
            Log::warning('Revolut webhook signature missing');
            return false;
        }

        $computedSignature = 'v1=' . hash_hmac('sha256', $bodyJson, $signingSecret);

        return hash_equals($computedSignature, $signature);
    }
}
