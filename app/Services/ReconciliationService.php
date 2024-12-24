<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\Transaction;
use Carbon\Carbon;

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
    
            $matched = $this->matchTransaction($transaction, $bankStatement);
            
            if ($matched) {
                $matchedTransactions->push($transaction);
            } else {
                $unmatchedTransactions->push($transaction);
                // Record discrepancy details
                $discrepancies->push([
                    'transaction' => $transaction,
                    'type' => 'unmatched_transaction',
                    'amount' => $transaction->amount,
                    'date' => $transaction->transaction_date
                ]);
            }
        }
    
        $balanceDiscrepancy = ($totalCredits - $totalDebits) - 
            ($bankStatement->total_credits - $bankStatement->total_debits);
    
        if (abs($balanceDiscrepancy) > 0.01) {
            $discrepancies->push([
                'type' => 'balance_mismatch',
                'amount' => $balanceDiscrepancy,
                'expected' => $bankStatement->total_credits - $bankStatement->total_debits,
                'actual' => $totalCredits - $totalDebits
            ]);
        }
    
        return [
            'matched_transactions' => $matchedTransactions,
            'unmatched_transactions' => $unmatchedTransactions,
            'discrepancies' => $discrepancies,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'bank_statement_credits' => $bankStatement->total_credits,
            'bank_statement_debits' => $bankStatement->total_debits,
            'balance_discrepancy' => $balanceDiscrepancy
        ];
    }

    private function matchTransaction(Transaction $transaction, BankStatement $bankStatement)
    {
        // Try exact match first
        $exactMatch = $bankStatement->transactions()
            ->where('transaction_date', $transaction->transaction_date)
            ->where('amount', $transaction->amount)
            ->exists();

        if ($exactMatch) {
            $transaction->update(['reconciled' => true]);
            return true;
        }

        // Try fuzzy match within 2 days and same amount
        $fuzzyMatch = $bankStatement->transactions()
            ->whereBetween('transaction_date', [
                $transaction->transaction_date->subDays(2),
                $transaction->transaction_date->addDays(2)
            ])
            ->where('amount', $transaction->amount)
            ->exists();

        $transaction->update(['reconciled' => $fuzzyMatch]);
        return $fuzzyMatch;
    }
}