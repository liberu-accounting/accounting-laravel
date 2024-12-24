

<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BankStatementImportService
{
    public function importFromCsv(string $filePath, BankStatement $bankStatement): Collection 
    {
        $transactions = collect();
        $handle = fopen($filePath, 'r');

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            try {
                $transaction = Transaction::create([
                    'transaction_date' => $this->parseDate($data[0]),
                    'description' => $data[1],
                    'amount' => $this->parseAmount($data[2]),
                    'account_id' => $bankStatement->account_id,
                    'bank_statement_id' => $bankStatement->id,
                    'reconciled' => false
                ]);
                
                $transactions->push($transaction);
            } catch (\Exception $e) {
                Log::error('Failed to import transaction: ' . $e->getMessage());
            }
        }

        fclose($handle);
        return $transactions;
    }

    public function importFromOfx(string $filePath, BankStatement $bankStatement): Collection
    {
        $transactions = collect();
        $ofx = simplexml_load_file($filePath);

        foreach ($ofx->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $txn) {
            try {
                $transaction = Transaction::create([
                    'transaction_date' => $this->parseDate((string)$txn->DTPOSTED),
                    'description' => (string)$txn->MEMO,
                    'amount' => $this->parseAmount((string)$txn->TRNAMT),
                    'account_id' => $bankStatement->account_id,
                    'bank_statement_id' => $bankStatement->id,
                    'reconciled' => false
                ]);
                
                $transactions->push($transaction);
            } catch (\Exception $e) {
                Log::error('Failed to import OFX transaction: ' . $e->getMessage());
            }
        }

        return $transactions;
    }

    private function parseDate(string $date): Carbon
    {
        return Carbon::parse($date);
    }

    private function parseAmount(string $amount): float
    {
        return (float) str_replace(['$', ','], '', $amount);
    }
}