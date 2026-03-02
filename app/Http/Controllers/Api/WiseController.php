<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccountBalance;
use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use App\Services\WiseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class WiseController extends Controller
{
    protected WiseService $wiseService;

    public function __construct(WiseService $wiseService)
    {
        $this->wiseService = $wiseService;
    }

    /**
     * Redirect the user to Wise's OAuth authorization page
     */
    public function redirectToWise(Request $request): JsonResponse
    {
        try {
            $state = Str::random(40);
            $request->session()->put('wise_oauth_state', $state);

            $authorizationUrl = $this->wiseService->getAuthorizationUrl($state);

            return response()->json([
                'success' => true,
                'authorization_url' => $authorizationUrl,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate Wise authorization URL', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate authorization URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle the OAuth callback from Wise and store the connection
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $code = $request->input('code');
        $state = $request->input('state');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code missing',
            ], 400);
        }

        // Verify OAuth state to prevent CSRF
        $sessionState = $request->session()->pull('wise_oauth_state');
        if (!$sessionState || !hash_equals($sessionState, $state ?? '')) {
            Log::warning('Wise OAuth state mismatch', [
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid OAuth state',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = $request->user();
            $tokenData = $this->wiseService->exchangeAuthorizationCode($code);

            $connection = BankConnection::create([
                'user_id' => $user->id,
                'bank_id' => 'wise',
                'institution_name' => 'Wise',
                'wise_access_token' => $tokenData['access_token'],
                'wise_refresh_token' => $tokenData['refresh_token'] ?? null,
                'wise_token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wise account connected successfully',
                'connection' => [
                    'id' => $connection->id,
                    'institution_name' => $connection->institution_name,
                    'status' => $connection->status,
                    'created_at' => $connection->created_at,
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to store Wise connection', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Wise account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all Wise connections for the authenticated user
     */
    public function listConnections(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $connections = BankConnection::where('user_id', $user->id)
                ->where('bank_id', 'wise')
                ->select([
                    'id',
                    'institution_name',
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
            Log::error('Failed to list Wise connections', [
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
     * Get profiles and balances for a Wise connection
     */
    public function getAccounts(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($connection->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active',
                ], 400);
            }

            DB::beginTransaction();
            try {
                $profiles = $this->wiseService->getProfiles($connection);
                $syncedAccounts = [];

                foreach ($profiles as $profile) {
                    $profileId = $profile['id'];
                    $balances = $this->wiseService->getBalances($connection, $profileId);

                    foreach ($balances as $balance) {
                        $accountBalance = BankAccountBalance::updateOrCreate(
                            [
                                'bank_connection_id' => $connection->id,
                                'plaid_account_id' => 'wise_' . $profileId . '_' . ($balance['id'] ?? $balance['currency']),
                            ],
                            [
                                'account_name' => ($profile['type'] ?? 'personal') . ' - ' . ($balance['currency'] ?? ''),
                                'account_type' => $profile['type'] ?? 'personal',
                                'account_subtype' => null,
                                'current_balance' => $balance['amount']['value'] ?? null,
                                'available_balance' => $balance['amount']['value'] ?? null,
                                'iso_currency_code' => $balance['currency'] ?? null,
                                'last_updated_at' => now(),
                            ]
                        );

                        $syncedAccounts[] = [
                            'id' => $accountBalance->id,
                            'wise_profile_id' => $profileId,
                            'wise_balance_id' => $balance['id'] ?? null,
                            'account_name' => $accountBalance->account_name,
                            'account_type' => $accountBalance->account_type,
                            'current_balance' => $accountBalance->current_balance,
                            'currency' => $accountBalance->iso_currency_code,
                        ];
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Accounts synced successfully',
                    'accounts' => $syncedAccounts,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to get Wise accounts', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync transfers for a Wise connection
     */
    public function syncTransactions(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($connection->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection is not active',
                ], 400);
            }

            // Determine date range: sync from last sync or last 90 days
            $createdDateStart = $connection->last_synced_at
                ? $connection->last_synced_at->toIso8601String()
                : now()->subDays(90)->toIso8601String();

            DB::beginTransaction();
            try {
                $profiles = $this->wiseService->getProfiles($connection);
                $processedCount = 0;

                foreach ($profiles as $profile) {
                    $profileId = $profile['id'];
                    $transfers = $this->wiseService->getTransfers(
                        $connection,
                        $profileId,
                        $createdDateStart
                    );

                    foreach ($transfers as $wiseTransfer) {
                        $this->processWiseTransfer($wiseTransfer, $connection);
                        $processedCount++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transactions synced successfully',
                    'summary' => [
                        'total_processed' => $processedCount,
                    ],
                    'last_synced_at' => $connection->fresh()->last_synced_at,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to sync Wise transactions', [
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
     * Remove a Wise connection
     */
    public function removeConnection(Request $request, BankConnection $connection): JsonResponse
    {
        try {
            if ($connection->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $connection->update(['status' => 'disconnected']);

            return response()->json([
                'success' => true,
                'message' => 'Wise connection removed successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to remove Wise connection', [
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
     * Process a Wise transfer and store in the database
     */
    protected function processWiseTransfer(array $wiseTransfer, BankConnection $connection): void
    {
        $transferId = $wiseTransfer['id'];
        $amount = $wiseTransfer['sourceValue'] ?? 0;
        $currency = $wiseTransfer['sourceCurrency'] ?? null;
        $createdAt = $wiseTransfer['created'] ?? null;

        $transaction = Transaction::updateOrCreate(
            [
                'external_id' => (string) $transferId,
                'bank_connection_id' => $connection->id,
            ],
            [
                'transaction_date' => $createdAt ? date('Y-m-d', strtotime($createdAt)) : now()->toDateString(),
                'amount' => abs($amount),
                'type' => 'debit',
                'description' => $wiseTransfer['reference'] ?? 'Wise transfer',
                'category' => 'transfer',
                'status' => $this->mapWiseStatus($wiseTransfer['status'] ?? 'completed'),
            ]
        );

        BankFeedTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'bank_connection_id' => $connection->id,
            ],
            [
                'raw_data' => $wiseTransfer,
            ]
        );
    }

    /**
     * Map Wise transfer status to internal status
     */
    protected function mapWiseStatus(string $status): string
    {
        return match ($status) {
            'incoming_payment_waiting', 'processing' => 'pending',
            'outgoing_payment_sent', 'funds_converted' => 'posted',
            'cancelled' => 'cancelled',
            'bounced_back', 'funds_refunded' => 'failed',
            default => 'posted',
        };
    }
}
