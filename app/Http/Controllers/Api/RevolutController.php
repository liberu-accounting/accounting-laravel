<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccountBalance;
use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use App\Services\RevolutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class RevolutController extends Controller
{
    protected RevolutService $revolutService;

    public function __construct(RevolutService $revolutService)
    {
        $this->revolutService = $revolutService;
    }

    /**
     * Redirect the user to Revolut's OAuth authorization page
     */
    public function redirectToRevolut(Request $request): JsonResponse
    {
        try {
            $state = Str::random(40);
            $request->session()->put('revolut_oauth_state', $state);

            $authorizationUrl = $this->revolutService->getAuthorizationUrl($state);

            return response()->json([
                'success' => true,
                'authorization_url' => $authorizationUrl,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate Revolut authorization URL', [
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
     * Handle the OAuth callback from Revolut and store the connection
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
        $sessionState = $request->session()->pull('revolut_oauth_state');
        if (!$sessionState || !hash_equals($sessionState, $state ?? '')) {
            Log::warning('Revolut OAuth state mismatch', [
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
            $tokenData = $this->revolutService->exchangeAuthorizationCode($code);

            $connection = BankConnection::create([
                'user_id' => $user->id,
                'bank_id' => 'revolut',
                'institution_name' => 'Revolut Business',
                'revolut_access_token' => $tokenData['access_token'],
                'revolut_refresh_token' => $tokenData['refresh_token'] ?? null,
                'revolut_token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Revolut Business account connected successfully',
                'connection' => [
                    'id' => $connection->id,
                    'institution_name' => $connection->institution_name,
                    'status' => $connection->status,
                    'created_at' => $connection->created_at,
                ],
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to store Revolut connection', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Revolut account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all Revolut connections for the authenticated user
     */
    public function listConnections(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $connections = BankConnection::where('user_id', $user->id)
                ->where('bank_id', 'revolut')
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
            Log::error('Failed to list Revolut connections', [
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
     * Get accounts and balances for a Revolut connection
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
                $accounts = $this->revolutService->getAccounts($connection);
                $syncedAccounts = [];

                foreach ($accounts as $account) {
                    $balance = BankAccountBalance::updateOrCreate(
                        [
                            'bank_connection_id' => $connection->id,
                            'plaid_account_id' => $account['id'],
                        ],
                        [
                            'account_name' => $account['name'],
                            'account_type' => $account['type'] ?? 'current',
                            'account_subtype' => null,
                            'current_balance' => $account['balance'] ?? null,
                            'available_balance' => $account['balance'] ?? null,
                            'iso_currency_code' => $account['currency'] ?? null,
                            'last_updated_at' => now(),
                        ]
                    );

                    $syncedAccounts[] = [
                        'id' => $balance->id,
                        'revolut_account_id' => $account['id'],
                        'account_name' => $balance->account_name,
                        'account_type' => $balance->account_type,
                        'current_balance' => $balance->current_balance,
                        'currency' => $balance->iso_currency_code,
                    ];
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
            Log::error('Failed to get Revolut accounts', [
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
     * Sync transactions for a Revolut connection
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
            $from = $connection->last_synced_at
                ? $connection->last_synced_at->toIso8601String()
                : now()->subDays(90)->toIso8601String();

            DB::beginTransaction();
            try {
                $transactions = $this->revolutService->getTransactions($connection, $from);

                $processedCount = 0;
                foreach ($transactions as $revolutTransaction) {
                    $this->processRevolutTransaction($revolutTransaction, $connection);
                    $processedCount++;
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
            Log::error('Failed to sync Revolut transactions', [
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
     * Remove a Revolut connection
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
                'message' => 'Revolut connection removed successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to remove Revolut connection', [
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
     * Send a single payment via Revolut Business
     */
    public function sendPayment(Request $request, BankConnection $connection): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|string',
            'receiver' => 'required|array',
            'receiver.counterparty_id' => 'required_without:receiver.account_id|string|nullable',
            'receiver.account_id' => 'required_without:receiver.counterparty_id|string|nullable',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'reference' => 'required|string|max:255',
        ]);

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

            $paymentData = $request->only(['account_id', 'receiver', 'amount', 'currency', 'reference']);

            $result = $this->revolutService->sendPayment($connection, $paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Payment sent successfully',
                'payment' => $result,
            ], 201);
        } catch (Exception $e) {
            Log::error('Failed to send Revolut payment', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send bulk payments via Revolut Business
     */
    public function sendBulkPayment(Request $request, BankConnection $connection): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'schedule_for' => 'nullable|date_format:Y-m-d',
            'payments' => 'required|array|min:1',
            'payments.*.account_id' => 'required|string',
            'payments.*.receiver' => 'required|array',
            'payments.*.receiver.counterparty_id' => 'required_without:payments.*.receiver.account_id|string|nullable',
            'payments.*.receiver.account_id' => 'required_without:payments.*.receiver.counterparty_id|string|nullable',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.currency' => 'required|string|size:3',
            'payments.*.reference' => 'required|string|max:255',
        ]);

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

            $result = $this->revolutService->sendBulkPayment(
                $connection,
                $request->input('title'),
                $request->input('payments'),
                $request->input('schedule_for'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk payment submitted successfully',
                'payment_draft' => $result,
            ], 201);
        } catch (Exception $e) {
            Log::error('Failed to send Revolut bulk payment', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a Revolut transaction and store in the database
     */
    protected function processRevolutTransaction(array $revolutTransaction, BankConnection $connection): void
    {
        $transactionId = $revolutTransaction['id'];
        $amount = $revolutTransaction['legs'][0]['amount'] ?? 0;
        $currency = $revolutTransaction['legs'][0]['currency'] ?? null;
        $completedAt = $revolutTransaction['completed_at'] ?? $revolutTransaction['created_at'] ?? null;

        $transaction = Transaction::updateOrCreate(
            [
                'external_id' => $transactionId,
                'bank_connection_id' => $connection->id,
            ],
            [
                'transaction_date' => $completedAt ? date('Y-m-d', strtotime($completedAt)) : now()->toDateString(),
                'amount' => abs($amount),
                'type' => $amount < 0 ? 'debit' : 'credit',
                'description' => $revolutTransaction['reference'] ?? $revolutTransaction['merchant']['name'] ?? 'Revolut transaction',
                'category' => $this->categorizeRevolutTransaction($revolutTransaction),
                'status' => $this->mapRevolutState($revolutTransaction['state'] ?? 'completed'),
            ]
        );

        BankFeedTransaction::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'bank_connection_id' => $connection->id,
            ],
            [
                'raw_data' => $revolutTransaction,
            ]
        );
    }

    /**
     * Map Revolut transaction state to internal status
     */
    protected function mapRevolutState(string $state): string
    {
        return match ($state) {
            'pending' => 'pending',
            'completed' => 'posted',
            'declined' => 'declined',
            'failed' => 'failed',
            'reverted' => 'cancelled',
            default => 'posted',
        };
    }

    /**
     * Categorize a Revolut transaction based on its type/merchant info
     */
    protected function categorizeRevolutTransaction(array $revolutTransaction): string
    {
        $type = strtolower($revolutTransaction['type'] ?? '');

        $typeMap = [
            'transfer' => 'transfer',
            'card_payment' => 'payment',
            'card_refund' => 'refund',
            'atm' => 'cash',
            'fee' => 'fee',
            'topup' => 'topup',
            'fx' => 'exchange',
        ];

        if (isset($typeMap[$type])) {
            return $typeMap[$type];
        }

        $merchantCategory = $revolutTransaction['merchant']['category'] ?? null;
        if ($merchantCategory) {
            return strtolower($merchantCategory);
        }

        return 'uncategorized';
    }
}
