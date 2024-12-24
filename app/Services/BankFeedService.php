

<?php

namespace App\Services;

use App\Models\BankConnection;
use App\Models\BankFeedTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankFeedService
{
    protected $apiConfig;
    
    public function __construct()
    {
        $this->apiConfig = config('services.bank_feeds');
    }

    public function connectBank(array $credentials, string $bankId): BankConnection
    {
        $encryptedCredentials = encrypt($credentials);
        
        return BankConnection::create([
            'bank_id' => $bankId,
            'credentials' => $encryptedCredentials,
            'status' => 'active'
        ]);
    }

    public function importTransactions(BankConnection $connection)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiConfig['key']
            ])->get($this->apiConfig['url'] . '/transactions', [
                'bank_id' => $connection->bank_id,
                'credentials' => decrypt($connection->credentials)
            ]);

            if ($response->successful()) {
                $transactions = $response->json()['transactions'];
                
                foreach ($transactions as $transaction) {
                    $this->processTransaction($transaction, $connection);
                }
            }
        } catch (\Exception $e) {
            Log::error('Bank feed import failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processTransaction($transactionData, BankConnection $connection)
    {
        $transaction = Transaction::updateOrCreate(
            [
                'external_id' => $transactionData['id'],
                'bank_connection_id' => $connection->id
            ],
            [
                'transaction_date' => $transactionData['date'],
                'amount' => $transactionData['amount'],
                'description' => $transactionData['description'],
                'category' => $this->categorizeTransaction($transactionData),
                'reconciled' => false
            ]
        );

        BankFeedTransaction::create([
            'transaction_id' => $transaction->id,
            'bank_connection_id' => $connection->id,
            'raw_data' => json_encode($transactionData)
        ]);

        return $transaction;
    }

    protected function categorizeTransaction($transactionData): string
    {
        $description = strtolower($transactionData['description']);
        
        $categories = [
            'salary' => ['payroll', 'salary', 'wage'],
            'utilities' => ['electric', 'water', 'gas', 'internet'],
            'food' => ['restaurant', 'grocery', 'food'],
            'rent' => ['rent', 'lease', 'housing'],
            'transportation' => ['fuel', 'gas', 'parking', 'transit'],
            'entertainment' => ['movie', 'theatre', 'concert', 'streaming'],
            'shopping' => ['amazon', 'walmart', 'target', 'store'],
            'healthcare' => ['medical', 'doctor', 'pharmacy', 'hospital']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    return $category;
                }
            }
        }

        return 'uncategorized';
    }
}