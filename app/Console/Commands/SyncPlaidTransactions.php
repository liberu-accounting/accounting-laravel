<?php

namespace App\Console\Commands;

use App\Jobs\SyncPlaidTransactionsJob;
use App\Models\BankConnection;
use Illuminate\Console\Command;

class SyncPlaidTransactions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plaid:sync-transactions
                            {--connection= : Sync a specific connection ID}
                            {--all : Sync all active connections}';

    /**
     * The console command description.
     */
    protected $description = 'Sync transactions from Plaid for bank connections';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionId = $this->option('connection');
        $syncAll = $this->option('all');

        if (!$connectionId && !$syncAll) {
            $this->error('Please specify either --connection=ID or --all');
            return self::FAILURE;
        }

        if ($connectionId) {
            return $this->syncConnection($connectionId);
        }

        return $this->syncAllConnections();
    }

    /**
     * Sync a specific connection
     */
    protected function syncConnection(int $connectionId): int
    {
        $connection = BankConnection::find($connectionId);

        if (!$connection) {
            $this->error("Connection {$connectionId} not found");
            return self::FAILURE;
        }

        if ($connection->status !== 'active') {
            $this->warn("Connection {$connectionId} is not active (status: {$connection->status})");
            return self::FAILURE;
        }

        $this->info("Dispatching sync job for connection {$connectionId} ({$connection->institution_name})");
        SyncPlaidTransactionsJob::dispatch($connectionId);

        $this->info('Sync job dispatched successfully');
        return self::SUCCESS;
    }

    /**
     * Sync all active connections
     */
    protected function syncAllConnections(): int
    {
        $connections = BankConnection::where('status', 'active')
            ->whereNotNull('plaid_access_token')
            ->get();

        if ($connections->isEmpty()) {
            $this->info('No active Plaid connections found');
            return self::SUCCESS;
        }

        $this->info("Found {$connections->count()} active connections");

        $bar = $this->output->createProgressBar($connections->count());
        $bar->start();

        foreach ($connections as $connection) {
            SyncPlaidTransactionsJob::dispatch($connection->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All sync jobs dispatched successfully');

        return self::SUCCESS;
    }
}
