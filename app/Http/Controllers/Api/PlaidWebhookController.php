<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPlaidTransactionsJob;
use App\Models\BankConnection;
use App\Services\PlaidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class PlaidWebhookController extends Controller
{
    protected PlaidService $plaidService;

    public function __construct(PlaidService $plaidService)
    {
        $this->plaidService = $plaidService;
    }

    /**
     * Handle incoming Plaid webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Get raw request body for signature verification
            $rawBody = $request->getContent();
            
            // Verify webhook signature
            if (!$this->plaidService->verifyWebhookSignature($rawBody, $request->headers->all())) {
                Log::warning('Plaid webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            $payload = $request->all();
            $webhookType = $payload['webhook_type'] ?? null;
            $webhookCode = $payload['webhook_code'] ?? null;
            $itemId = $payload['item_id'] ?? null;

            Log::info('Plaid webhook received', [
                'type' => $webhookType,
                'code' => $webhookCode,
                'item_id' => $itemId,
            ]);

            // Route to appropriate handler based on webhook type
            match ($webhookType) {
                'TRANSACTIONS' => $this->handleTransactionsWebhook($payload),
                'ITEM' => $this->handleItemWebhook($payload),
                'AUTH' => $this->handleAuthWebhook($payload),
                'ASSETS' => $this->handleAssetsWebhook($payload),
                default => Log::info('Unhandled webhook type', ['type' => $webhookType]),
            };

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ]);
        } catch (Exception $e) {
            Log::error('Plaid webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Plaid from retrying
            // Log the error for manual investigation
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 200);
        }
    }

    /**
     * Handle TRANSACTIONS webhook events
     */
    protected function handleTransactionsWebhook(array $payload): void
    {
        $webhookCode = $payload['webhook_code'];
        $itemId = $payload['item_id'];

        $connection = BankConnection::where('plaid_item_id', $itemId)->first();
        
        if (!$connection) {
            Log::warning('Bank connection not found for webhook', [
                'item_id' => $itemId,
                'webhook_code' => $webhookCode,
            ]);
            return;
        }

        match ($webhookCode) {
            // New transactions available
            'SYNC_UPDATES_AVAILABLE' => $this->handleSyncUpdatesAvailable($connection),
            
            // Initial transactions ready
            'INITIAL_UPDATE' => $this->handleInitialUpdate($connection),
            
            // Historical update complete
            'HISTORICAL_UPDATE' => $this->handleHistoricalUpdate($connection),
            
            // Default transactions update (legacy)
            'DEFAULT_UPDATE' => $this->handleDefaultUpdate($connection),
            
            // Transactions removed
            'TRANSACTIONS_REMOVED' => $this->handleTransactionsRemoved($connection, $payload),
            
            default => Log::info('Unhandled transactions webhook code', ['code' => $webhookCode]),
        };
    }

    /**
     * Handle ITEM webhook events
     */
    protected function handleItemWebhook(array $payload): void
    {
        $webhookCode = $payload['webhook_code'];
        $itemId = $payload['item_id'];
        $error = $payload['error'] ?? null;

        $connection = BankConnection::where('plaid_item_id', $itemId)->first();
        
        if (!$connection) {
            Log::warning('Bank connection not found for item webhook', [
                'item_id' => $itemId,
                'webhook_code' => $webhookCode,
            ]);
            return;
        }

        match ($webhookCode) {
            // Item login required
            'ERROR' => $this->handleItemError($connection, $error),
            
            // Item webhook updated
            'WEBHOOK_UPDATE_ACKNOWLEDGED' => Log::info('Webhook update acknowledged', [
                'connection_id' => $connection->id,
            ]),
            
            // Pending expiration
            'PENDING_EXPIRATION' => $this->handlePendingExpiration($connection),
            
            // User permission revoked
            'USER_PERMISSION_REVOKED' => $this->handleUserPermissionRevoked($connection),
            
            default => Log::info('Unhandled item webhook code', ['code' => $webhookCode]),
        };
    }

    /**
     * Handle AUTH webhook events
     */
    protected function handleAuthWebhook(array $payload): void
    {
        $webhookCode = $payload['webhook_code'];
        
        Log::info('Auth webhook received', [
            'code' => $webhookCode,
            'payload' => $payload,
        ]);
    }

    /**
     * Handle ASSETS webhook events
     */
    protected function handleAssetsWebhook(array $payload): void
    {
        $webhookCode = $payload['webhook_code'];
        
        Log::info('Assets webhook received', [
            'code' => $webhookCode,
            'payload' => $payload,
        ]);
    }

    /**
     * Handle sync updates available
     */
    protected function handleSyncUpdatesAvailable(BankConnection $connection): void
    {
        Log::info('Sync updates available', ['connection_id' => $connection->id]);
        
        // Dispatch job to sync transactions
        SyncPlaidTransactionsJob::dispatch($connection->id);
    }

    /**
     * Handle initial update
     */
    protected function handleInitialUpdate(BankConnection $connection): void
    {
        Log::info('Initial update received', ['connection_id' => $connection->id]);
        
        // Dispatch job to sync transactions
        SyncPlaidTransactionsJob::dispatch($connection->id);
    }

    /**
     * Handle historical update
     */
    protected function handleHistoricalUpdate(BankConnection $connection): void
    {
        Log::info('Historical update complete', ['connection_id' => $connection->id]);
        
        // Dispatch job to sync transactions
        SyncPlaidTransactionsJob::dispatch($connection->id);
    }

    /**
     * Handle default update (legacy)
     */
    protected function handleDefaultUpdate(BankConnection $connection): void
    {
        Log::info('Default update received', ['connection_id' => $connection->id]);
        
        // Dispatch job to sync transactions
        SyncPlaidTransactionsJob::dispatch($connection->id);
    }

    /**
     * Handle transactions removed
     */
    protected function handleTransactionsRemoved(BankConnection $connection, array $payload): void
    {
        $removedTransactions = $payload['removed_transactions'] ?? [];
        
        Log::info('Transactions removed', [
            'connection_id' => $connection->id,
            'count' => count($removedTransactions),
        ]);
        
        // Handle removed transactions
        // For now, just log - will implement removal logic if needed
    }

    /**
     * Handle item error (e.g., login required)
     */
    protected function handleItemError(BankConnection $connection, ?array $error): void
    {
        if (!$error) {
            return;
        }

        $errorCode = $error['error_code'] ?? 'UNKNOWN';
        $errorMessage = $error['error_message'] ?? 'Unknown error';

        Log::warning('Item error received', [
            'connection_id' => $connection->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        // Update connection status based on error
        if (in_array($errorCode, ['ITEM_LOGIN_REQUIRED', 'INVALID_CREDENTIALS'])) {
            $connection->update([
                'status' => 'requires_reauth',
            ]);
        } elseif ($errorCode === 'ITEM_LOCKED') {
            $connection->update([
                'status' => 'locked',
            ]);
        }
    }

    /**
     * Handle pending expiration
     */
    protected function handlePendingExpiration(BankConnection $connection): void
    {
        Log::warning('Connection pending expiration', [
            'connection_id' => $connection->id,
        ]);

        // Notify user to re-authenticate
        // For now, just log
    }

    /**
     * Handle user permission revoked
     */
    protected function handleUserPermissionRevoked(BankConnection $connection): void
    {
        Log::warning('User permission revoked', [
            'connection_id' => $connection->id,
        ]);

        $connection->update([
            'status' => 'revoked',
        ]);
    }
}
