<?php

namespace App\Jobs;

use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use App\Services\PlaidService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncPlaidTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $connectionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaidService): void
    {
        try {
            $connection = BankConnection::findOrFail($this->connectionId);

            // Check if connection is active
            if ($connection->status !== 'active') {
                Log::warning('Skipping sync for inactive connection', [
                    'connection_id' => $connection->id,
                    'status' => $connection->status,
                ]);
                return;
            }

            Log::info('Starting Plaid transaction sync', [
                'connection_id' => $connection->id,
            ]);

            DB::beginTransaction();
            try {
                // Sync transactions from Plaid
                $transactionData = $plaidService->syncTransactions($connection);

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

                Log::info('Plaid transaction sync completed', [
                    'connection_id' => $connection->id,
                    'added' => count($added),
                    'modified' => count($modified),
                    'removed' => count($removed),
                    'total_processed' => $processedCount,
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Plaid transaction sync job failed', [
                'connection_id' => $this->connectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if we should mark connection as needing re-auth
            if (str_contains($e->getMessage(), 'User action required')) {
                try {
                    $connection = BankConnection::find($this->connectionId);
                    if ($connection) {
                        $connection->update(['status' => 'requires_reauth']);
                    }
                } catch (Exception $updateError) {
                    Log::error('Failed to update connection status', [
                        'connection_id' => $this->connectionId,
                        'error' => $updateError->getMessage(),
                    ]);
                }
            }

            throw $e;
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
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Plaid transaction sync job permanently failed', [
            'connection_id' => $this->connectionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators or mark connection for review
        // This could dispatch an event or send a notification
    }
}
