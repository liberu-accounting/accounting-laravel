<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HmrcAuthService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $serverToken;

    public function __construct()
    {
        $environment = config('hmrc.environment');
        $this->baseUrl = config("hmrc.endpoints.{$environment}");
        $this->clientId = config('hmrc.client_id');
        $this->clientSecret = config('hmrc.client_secret');
        $this->serverToken = config('hmrc.server_token');
    }

    /**
     * Get OAuth authorization URL for user to grant access.
     */
    public function getAuthorizationUrl(string $scope, string $state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));
        
        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
            'redirect_uri' => config('hmrc.callback_url'),
        ]);

        return $this->baseUrl . '/oauth/authorize?' . $query;
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        try {
            $response = Http::asForm()
                ->post($this->baseUrl . '/oauth/token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('hmrc.callback_url'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache the access token
                $this->cacheAccessToken($data['access_token'], $data['expires_in']);
                
                if (isset($data['refresh_token'])) {
                    $this->cacheRefreshToken($data['refresh_token']);
                }

                return $data;
            }

            $this->logError('Token exchange failed', $response->json());
            throw new \Exception('Failed to exchange authorization code for token');

        } catch (\Exception $e) {
            $this->logError('Token exchange error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refreshAccessToken(string $refreshToken = null): array
    {
        $refreshToken = $refreshToken ?? $this->getCachedRefreshToken();
        
        if (!$refreshToken) {
            throw new \Exception('No refresh token available');
        }

        try {
            $response = Http::asForm()
                ->post($this->baseUrl . '/oauth/token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->cacheAccessToken($data['access_token'], $data['expires_in']);
                
                if (isset($data['refresh_token'])) {
                    $this->cacheRefreshToken($data['refresh_token']);
                }

                return $data;
            }

            $this->logError('Token refresh failed', $response->json());
            throw new \Exception('Failed to refresh access token');

        } catch (\Exception $e) {
            $this->logError('Token refresh error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get valid access token (refreshes if expired).
     */
    public function getAccessToken(): string
    {
        $token = $this->getCachedAccessToken();
        
        if (!$token) {
            // Try to refresh the token
            $data = $this->refreshAccessToken();
            return $data['access_token'];
        }

        return $token;
    }

    /**
     * Check if user has authorized the application.
     */
    public function isAuthorized(): bool
    {
        return Cache::has('hmrc_access_token') || Cache::has('hmrc_refresh_token');
    }

    /**
     * Cache access token.
     */
    private function cacheAccessToken(string $token, int $expiresIn): void
    {
        // Cache for slightly less than expiration time to be safe
        $ttl = $expiresIn - 60;
        Cache::put('hmrc_access_token', $token, $ttl);
    }

    /**
     * Cache refresh token.
     */
    private function cacheRefreshToken(string $token): void
    {
        // Refresh tokens typically last 18 months
        Cache::put('hmrc_refresh_token', $token, 60 * 60 * 24 * 540); // 540 days
    }

    /**
     * Get cached access token.
     */
    private function getCachedAccessToken(): ?string
    {
        return Cache::get('hmrc_access_token');
    }

    /**
     * Get cached refresh token.
     */
    private function getCachedRefreshToken(): ?string
    {
        return Cache::get('hmrc_refresh_token');
    }

    /**
     * Revoke all tokens and clear cache.
     */
    public function logout(): void
    {
        Cache::forget('hmrc_access_token');
        Cache::forget('hmrc_refresh_token');
    }

    /**
     * Log error if logging is enabled.
     */
    private function logError(string $message, array $context = []): void
    {
        if (config('hmrc.logging.enabled')) {
            Log::channel(config('hmrc.logging.channel'))->error($message, $context);
        }
    }
}
