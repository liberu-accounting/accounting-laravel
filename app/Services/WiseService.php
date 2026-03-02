<?php

namespace App\Services;

use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WiseService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $environment;
    protected string $baseUrl;
    protected string $authUrl;

    public function __construct()
    {
        $this->clientId = config('services.wise.client_id');
        $this->clientSecret = config('services.wise.client_secret');
        $this->environment = config('services.wise.environment', 'sandbox');

        $this->baseUrl = $this->environment === 'production'
            ? 'https://api.transferwise.com'
            : 'https://api.sandbox.transferwise.tech';

        $this->authUrl = $this->environment === 'production'
            ? 'https://wise.com/oauth/v2/authorize'
            : 'https://sandbox.transferwise.tech/oauth/v2/authorize';
    }

    /**
     * Generate the OAuth authorization URL to redirect the user to Wise
     */
    public function getAuthorizationUrl(string $state): string
    {
        $redirectUri = config('services.wise.redirect_uri');

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'transfers balances.read',
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
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/oauth/v2/token", [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.wise.redirect_uri'),
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to exchange authorization code: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Wise authorization code exchange failed', [
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
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/oauth/v2/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to refresh access token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Wise token refresh failed', [
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
        if ($connection->wise_token_expires_at && now()->addSeconds(60)->isAfter($connection->wise_token_expires_at)) {
            if (!$connection->wise_refresh_token) {
                throw new Exception('Access token expired and no refresh token available');
            }

            $tokenData = $this->refreshAccessToken($connection->wise_refresh_token);

            $connection->update([
                'wise_access_token' => $tokenData['access_token'],
                'wise_refresh_token' => $tokenData['refresh_token'] ?? $connection->wise_refresh_token,
                'wise_token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            ]);
        }

        return $connection->wise_access_token;
    }

    /**
     * Get all profiles (personal and business) for the authenticated user
     */
    public function getProfiles(BankConnection $connection): array
    {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/v2/profiles");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get profiles: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Wise get profiles failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get balances for a specific profile
     *
     * @param BankConnection $connection
     * @param int $profileId The Wise profile ID
     * @param string $type Balance type: STANDARD or SAVINGS
     */
    public function getBalances(BankConnection $connection, int $profileId, string $type = 'STANDARD'): array
    {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/v4/profiles/{$profileId}/balances", [
                    'types' => $type,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get balances: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Wise get balances failed', [
                'connection_id' => $connection->id,
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transfers for a specific profile within a date range
     *
     * @param BankConnection $connection
     * @param int $profileId The Wise profile ID
     * @param string|null $createdDateStart ISO 8601 date string
     * @param string|null $createdDateEnd ISO 8601 date string
     * @param int $limit Maximum number of transfers (default 20, max 100)
     * @param string $status Transfer status filter
     */
    public function getTransfers(
        BankConnection $connection,
        int $profileId,
        ?string $createdDateStart = null,
        ?string $createdDateEnd = null,
        int $limit = 20,
        string $status = 'outgoing_payment_sent'
    ): array {
        try {
            $accessToken = $this->getValidAccessToken($connection);

            $params = [
                'profile' => $profileId,
                'limit' => min($limit, 100),
                'status' => $status,
            ];

            if ($createdDateStart) {
                $params['createdDateStart'] = $createdDateStart;
            }

            if ($createdDateEnd) {
                $params['createdDateEnd'] = $createdDateEnd;
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 200, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                           ($exception instanceof \Illuminate\Http\Client\RequestException &&
                            $exception->response->status() >= 500);
                })
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/v1/transfers", $params);

            if ($response->successful()) {
                $transfers = $response->json();

                $connection->update(['last_synced_at' => now()]);

                return $transfers;
            }

            throw new Exception('Failed to get transfers: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Wise get transfers failed', [
                'connection_id' => $connection->id,
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify the webhook signature from Wise using RSA public key
     *
     * @param string $bodyJson The raw JSON body of the webhook request
     * @param string $signature The base64-encoded signature from the 'X-Signature-SHA256' header
     * @param string $publicKey The Wise RSA public key (PEM format)
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(string $bodyJson, string $signature, string $publicKey): bool
    {
        if (empty($publicKey)) {
            Log::warning('Wise webhook public key not configured - rejecting webhook');
            return false;
        }

        if (empty($signature)) {
            Log::warning('Wise webhook signature missing');
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            Log::warning('Wise webhook signature is not valid base64');
            return false;
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if ($publicKeyResource === false) {
            Log::error('Failed to load Wise webhook public key');
            return false;
        }

        $result = openssl_verify($bodyJson, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}
