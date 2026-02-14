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
     * Create a link token for Plaid Link initialization
     */
    public function createLinkToken(int $userId, ?string $language = 'en'): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/link/token/create", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'user' => [
                    'client_user_id' => (string) $userId,
                ],
                'client_name' => config('app.name'),
                'products' => ['transactions'],
                'country_codes' => ['US', 'CA', 'GB'],
                'language' => $language,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to create link token: ' . $response->body());
        } catch (Exception $e) {
            Log::error('Plaid link token creation failed', [
                'user_id' => $userId,
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

            $response = Http::post("{$this->baseUrl}/transactions/sync", $payload);

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
     */
    public function verifyWebhookSignature(string $bodyJson, array $headers): bool
    {
        // Plaid webhook verification logic
        // This would use HMAC verification with the webhook verification key
        // For now, returning true as this requires additional configuration
        return true;
    }
}
