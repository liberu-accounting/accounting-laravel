<?php

namespace App\Services;

use App\Models\BankConnection;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PlaidService
{
    protected string $clientId;
    protected string $secret;
    protected string $environment;
    protected string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.plaid.client_id');
        $this->secret = config('services.plaid.secret');
        $this->environment = config('services.plaid.environment', 'sandbox');
        
        // Set base URL based on environment
        $this->baseUrl = match($this->environment) {
            'production' => 'https://production.plaid.com',
            'development' => 'https://development.plaid.com',
            default => 'https://sandbox.plaid.com',
        };
    }

    /**
     * Handle Plaid API errors and determine if they're retryable
     */
    protected function handlePlaidError(array $errorData, string $context): void
    {
        $errorCode = $errorData['error_code'] ?? 'UNKNOWN_ERROR';
        $errorMessage = $errorData['error_message'] ?? 'Unknown error occurred';
        $errorType = $errorData['error_type'] ?? 'UNKNOWN';

        Log::error("Plaid API error in {$context}", [
            'error_code' => $errorCode,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
        ]);

        // Determine if error requires user action
        $userActionRequired = in_array($errorCode, [
            'ITEM_LOGIN_REQUIRED',
            'INVALID_CREDENTIALS',
            'INVALID_MFA',
            'ITEM_LOCKED',
        ]);

        if ($userActionRequired) {
            throw new Exception("User action required: {$errorMessage} (Code: {$errorCode})");
        }

        throw new Exception("Plaid API error: {$errorMessage} (Code: {$errorCode})");
    }

    /**
     * Create a link token for Plaid Link initialization
     * 
     * @param int $userId User ID for Plaid identification
     * @param string|null $language Language code (default: 'en')
     * @param string|null $accessToken Existing access token for update mode (re-authentication)
     * @return array Link token data including token and expiration
     */
    public function createLinkToken(int $userId, ?string $language = 'en', ?string $accessToken = null): array
    {
        try {
            $payload = [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'user' => [
                    'client_user_id' => (string) $userId,
                ],
                'client_name' => config('app.name'),
                'products' => ['transactions'],
                'country_codes' => ['US', 'CA', 'GB'],
                'language' => $language,
            ];

            // Add OAuth redirect URI if configured
            $oauthRedirectUri = config('services.plaid.oauth_redirect_uri');
            if ($oauthRedirectUri) {
                $payload['redirect_uri'] = $oauthRedirectUri;
            }

            // If access token is provided, this is update mode for re-authentication
            if ($accessToken) {
                $payload['access_token'] = $accessToken;
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->retry(3, 100)
                ->post("{$this->baseUrl}/link/token/create", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to create link token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid link token creation failed', [
                'user_id' => $userId,
                'update_mode' => $accessToken !== null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Exchange a public token for an access token
     */
    public function exchangePublicToken(string $publicToken): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/public_token/exchange", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'public_token' => $publicToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to exchange public token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid public token exchange failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get institution details
     */
    public function getInstitution(string $institutionId): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/institutions/get_by_id", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'institution_id' => $institutionId,
                'country_codes' => ['US', 'CA', 'GB'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get institution: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid get institution failed', [
                'institution_id' => $institutionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync transactions from Plaid
     */
    public function syncTransactions(BankConnection $connection): array
    {
        try {
            // Note: plaid_access_token is automatically decrypted by Laravel's encrypted cast
            $payload = [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $connection->plaid_access_token,
            ];

            // Add cursor if we have it for incremental sync
            if ($connection->plaid_cursor) {
                $payload['cursor'] = $connection->plaid_cursor;
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 200, function ($exception, $request) {
                    // Only retry on 5xx errors or network issues, not 4xx
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                           ($exception instanceof \Illuminate\Http\Client\RequestException && 
                            $exception->response->status() >= 500);
                })
                ->post("{$this->baseUrl}/transactions/sync", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update cursor for next sync
                if (isset($data['next_cursor'])) {
                    $connection->update([
                        'plaid_cursor' => $data['next_cursor'],
                        'last_synced_at' => now(),
                    ]);
                }

                return $data;
            }

            // Handle specific Plaid errors
            if ($response->status() === 400 && isset($response->json()['error_code'])) {
                $this->handlePlaidError($response->json(), 'syncTransactions');
            }

            throw new Exception('Failed to sync transactions: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid transaction sync failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get account information
     * 
     * @param string $accessToken The access token (should already be decrypted if from model)
     */
    public function getAccounts(string $accessToken): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/accounts/get", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get accounts: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid get accounts failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get account balances with real-time balance information
     * 
     * @param string $accessToken The access token (should already be decrypted if from model)
     * @param array|null $accountIds Optional array of specific account IDs to get balances for
     */
    public function getBalances(string $accessToken, ?array $accountIds = null): array
    {
        try {
            $payload = [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $accessToken,
            ];

            // Optionally filter to specific accounts
            if ($accountIds !== null && !empty($accountIds)) {
                $payload['options'] = [
                    'account_ids' => $accountIds,
                ];
            }

            $response = Http::post("{$this->baseUrl}/accounts/balance/get", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get balances: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid get balances failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove a Plaid item (disconnect bank)
     * 
     * @param string $accessToken The access token (should already be decrypted if from model)
     */
    public function removeItem(string $accessToken): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/remove", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                return true;
            }

            throw new Exception('Failed to remove item: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid item removal failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook signature
     * 
     * @param string $bodyJson The raw JSON body of the webhook request
     * @param array $headers The headers from the webhook request
     * @return bool True if signature is valid, false otherwise
     */
    public function verifyWebhookSignature(string $bodyJson, array $headers): bool
    {
        $verificationKey = config('services.plaid.webhook_verification_key');
        
        // If no verification key is configured, log warning and reject
        if (empty($verificationKey)) {
            Log::warning('Plaid webhook verification key not configured - rejecting webhook');
            return false;
        }
        
        // Get signature from headers (Plaid sends it as 'Plaid-Verification' header)
        $signature = $headers['plaid-verification'] ?? $headers['Plaid-Verification'] ?? null;
        
        if (empty($signature)) {
            Log::warning('Plaid webhook signature missing from headers');
            return false;
        }
        
        // Compute HMAC-SHA256 signature
        $computedSignature = hash_hmac('sha256', $bodyJson, $verificationKey, true);
        $computedSignatureBase64 = base64_encode($computedSignature);
        
        // Use hash_equals for timing-safe comparison
        return hash_equals($computedSignatureBase64, $signature);
    }
}
