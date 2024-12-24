<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class ReconciliationService
{
    public function reconcile(BankStatement $bankStatement)
    {
        $transactions = Transaction::where('account_id', $bankStatement->account_id)
            ->whereBetween('transaction_date', [
                $bankStatement->statement_date->startOfMonth(),
                $bankStatement->statement_date->endOfMonth()
            ])
            ->get();
    
        $totalCredits = 0;
        $totalDebits = 0;
        $matchedTransactions = collect();
        $unmatchedTransactions = collect();
        $discrepancies = collect();
    
        foreach ($transactions as $transaction) {
            if ($transaction->amount > 0) {
                $totalCredits += $transaction->amount;
            } else {
                $totalDebits += abs($transaction->amount);
            }
    
            $matched = $this->findMatch($transaction, $bankStatement);
            
            if ($matched) {
                $matchedTransactions->push($transaction);
                $transaction->update(['reconciled' => true]);
            } else {
                $unmatchedTransactions->push($transaction);
                $discrepancies->push([
                    'type' => 'unmatched_transaction',
                    'date' => $transaction->transaction_date,
                    'amount' => $transaction->amount
                ]);
            }
        }
    
        $balanceDiscrepancy = ($totalCredits - $totalDebits) - 
            ($bankStatement->total_credits - $bankStatement->total_debits);
    
        if ($balanceDiscrepancy != 0) {
            $discrepancies->push([
                'type' => 'balance_mismatch',
                'amount' => $balanceDiscrepancy,
                'expected' => $bankStatement->ending_balance,
                'actual' => $totalCredits - $totalDebits
            ]);
        }
    
        return [
            'matched_transactions' => $matchedTransactions,
            'unmatched_transactions' => $unmatchedTransactions,
            'discrepancies' => $discrepancies,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'balance_discrepancy' => $balanceDiscrepancy
        ];
    }

    private function findMatch(Transaction $transaction, BankStatement $bankStatement): bool
    {
        // Try exact match first
        $exactMatch = $bankStatement->transactions()
            ->where('transaction_date', $transaction->transaction_date)
            ->where('amount', $transaction->amount)
            ->exists();

        if ($exactMatch) {
            return true;
        }

        // Try fuzzy match within 2 days and exact amount
        $fuzzyMatch = $bankStatement->transactions()
            ->whereBetween('transaction_date', [
                $transaction->transaction_date->subDays(2),
                $transaction->transaction_date->addDays(2)
            ])
            ->where('amount', $transaction->amount)
            ->exists();

        return $fuzzyMatch;
    }
}