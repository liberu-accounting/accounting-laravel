<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccountBalance;
use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use App\Services\PlaidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class PlaidController extends Controller
{
    protected PlaidService $plaidService;

    public function __construct(PlaidService $plaidService)
    {
        $this->plaidService = $plaidService;
    }

    /**
     * Create a link token for Plaid Link initialization
     * Supports both initial connection and update mode for re-authentication
     */
    public function createLinkToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $language = $request->input('language', 'en');
            $connectionId = $request->input('connection_id');

            $accessToken = null;

            // If connection_id is provided, this is update mode for re-authentication
            if ($connectionId) {
                $connection = BankConnection::find($connectionId);

                // Verify ownership
                if (!$connection || $connection->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection not found or unauthorized',
                    ], 404);
                }

                $accessToken = $connection->plaid_access_token;
            }

            $tokenData = $this->plaidService->createLinkToken($user->id, $language, $accessToken);

            return response()->json([
                'success' => true,
                'link_token' => $tokenData['link_token'],
                'expiration' => $tokenData['expiration'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create Plaid link token', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create link token: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new bank connection after Plaid Link success
     */
    public function storeConnection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_token' => 'required|string',
            'institution_id' => 'required|string',
            'institution_name' => 'required|string',
            'accounts' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = $request->user();

            // Exchange public token for access token
            $tokenData = $this->plaidService->exchangePublicToken($request->public_token);

            // Create bank connection
            $connection = BankConnection::create([
                'user_id' => $user->id,
                'bank_id' => $request->institution_id,
                'institution_name' => $request->institution_name,
                'plaid_access_token' => $tokenData['access_token'],
                'plaid_item_id' => $tokenData['item_id'],
                'plaid_institution_id' => $request->institution_id,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank connection created successfully',
                'connection' => [
                    'id' => $connection->id,
                    'institution_name' => $connection->institution_name,
                    'status' => $connection->status,
                    'created_at' => $connection->created_at,
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to store Plaid connection', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store connection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all bank connections for the authenticated user
     */
    public function listConnections(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $connections = BankConnection::where('user_id', $user->id)
                ->select([
                    'id',
                    'institution_name',
                    'bank_id',
                    'status',
                    'last_synced_at',
                    'created_at',
                    'updated_at',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'connections' => $connections,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to list bank connections', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve connections',
            ], 500);
        }
    }

    /**
     * Sync transactions from Plaid for a specific connection
     */
    public function syncTransactions(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            // Verify ownership
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Check connection status
            if ($connection->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active',
                ], 400);
            }

            DB::beginTransaction();
            try {
                // Sync transactions from Plaid
                $transactionData = $this->plaidService->syncTransactions($connection);

                $added = $transactionData['added'] ?? [];
                $modified = $transactionData['modified'] ?? [];
                $removed = $transactionData['removed'] ?? [];

                $processedCount = 0;

                // Process added transactions
                foreach ($added as $plaidTransaction) {
                    $this->processPlaidTransaction($plaidTransaction, $connection);
                    $processedCount++;
                }

                // Process modified transactions
                foreach ($modified as $plaidTransaction) {
                    $this->processPlaidTransaction($plaidTransaction, $connection, true);
                    $processedCount++;
                }

                // Process removed transactions (mark as deleted)
                foreach ($removed as $removedTransaction) {
                    $this->removeTransaction($removedTransaction['transaction_id'], $connection);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transactions synced successfully',
                    'summary' => [
                        'added' => count($added),
                        'modified' => count($modified),
                        'removed' => count($removed),
                        'total_processed' => $processedCount,
                    ],
                    'last_synced_at' => $connection->fresh()->last_synced_at,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to sync transactions', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a bank connection
     */
    public function removeConnection(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            // Verify ownership
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            DB::beginTransaction();
            try {
                // Remove from Plaid
                if ($connection->plaid_access_token) {
                    $this->plaidService->removeItem($connection->plaid_access_token);
                }

                // Soft delete or hard delete based on preference
                $connection->update(['status' => 'disconnected']);
                // Or: $connection->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Bank connection removed successfully',
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to remove bank connection', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove connection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get account balances for a connection
     */
    public function getBalances(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            // Verify ownership
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Check connection status
            if ($connection->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active',
                ], 400);
            }

            DB::beginTransaction();
            try {
                // Get balances from Plaid
                $balanceData = $this->plaidService->getBalances($connection->plaid_access_token);
                
                $accounts = $balanceData['accounts'] ?? [];
                $syncedAccounts = [];

                foreach ($accounts as $account) {
                    $balance = BankAccountBalance::updateOrCreate(
                        [
                            'bank_connection_id' => $connection->id,
                            'plaid_account_id' => $account['account_id'],
                        ],
                        [
                            'account_name' => $account['name'],
                            'account_type' => $account['type'],
                            'account_subtype' => $account['subtype'] ?? null,
                            'current_balance' => $account['balances']['current'] ?? null,
                            'available_balance' => $account['balances']['available'] ?? null,
                            'limit_amount' => $account['balances']['limit'] ?? null,
                            'iso_currency_code' => $account['balances']['iso_currency_code'] ?? null,
                            'unofficial_currency_code' => $account['balances']['unofficial_currency_code'] ?? null,
                            'last_updated_at' => now(),
                        ]
                    );

                    $syncedAccounts[] = [
                        'id' => $balance->id,
                        'account_name' => $balance->account_name,
                        'account_type' => $balance->account_type,
                        'account_subtype' => $balance->account_subtype,
                        'current_balance' => $balance->current_balance,
                        'available_balance' => $balance->available_balance,
                        'currency' => $balance->iso_currency_code ?? $balance->unofficial_currency_code,
                    ];
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Balances synced successfully',
                    'accounts' => $syncedAccounts,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to get balances', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get balances: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a Plaid transaction and create/update in database
     */
    protected function processPlaidTransaction(array $plaidTransaction, BankConnection $connection, bool $isUpdate = false): void
    {
        $transactionId = $plaidTransaction['transaction_id'];
        
        // Find or create the transaction
        $transaction = Transaction::updateOrCreate(
            [
                'external_id' => $transactionId,
                'bank_connection_id' => $connection->id,
            ],
            [
                'transaction_date' => $plaidTransaction['date'] ?? $plaidTransaction['authorized_date'] ?? now(),
                'amount' => abs($plaidTransaction['amount']),
                // In Plaid: positive = debit (money out), negative = credit (money in)
                'type' => $plaidTransaction['amount'] > 0 ? 'debit' : 'credit',
                'description' => $plaidTransaction['name'] ?? 'Unknown',
                'category' => $this->categorizePlaidTransaction($plaidTransaction),
                'status' => $plaidTransaction['pending'] ? 'pending' : 'posted',
            ]
        );

        // Store raw Plaid data
        BankFeedTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'bank_connection_id' => $connection->id,
            ],
            [
                'raw_data' => $plaidTransaction,
            ]
        );
    }

    /**
     * Remove a transaction that was deleted in Plaid
     */
    protected function removeTransaction(string $externalId, BankConnection $connection): void
    {
        Transaction::where('external_id', $externalId)
            ->where('bank_connection_id', $connection->id)
            ->delete();
    }

    /**
     * Categorize a Plaid transaction based on its category array
     */
    protected function categorizePlaidTransaction(array $plaidTransaction): string
    {
        $categories = $plaidTransaction['category'] ?? [];
        
        if (empty($categories)) {
            return 'uncategorized';
        }

        // Use the most specific category (last one in the array)
        return strtolower(end($categories));
    }

    /**
     * Handle OAuth redirect from Plaid Link
     * This endpoint receives the OAuth state after user authentication at their bank
     */
    public function handleOAuthRedirect(Request $request): JsonResponse
    {
        try {
            $oauthStateId = $request->input('oauth_state_id');

            if (!$oauthStateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing OAuth state ID',
                ], 400);
            }

            // Log the OAuth redirect for debugging
            Log::info('Plaid OAuth redirect received', [
                'oauth_state_id' => $oauthStateId,
            ]);

            // Return success - the frontend will handle completing the Link flow
            return response()->json([
                'success' => true,
                'message' => 'OAuth redirect received successfully',
                'oauth_state_id' => $oauthStateId,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to handle Plaid OAuth redirect', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to handle OAuth redirect',
            ], 500);
        }
    }
}
