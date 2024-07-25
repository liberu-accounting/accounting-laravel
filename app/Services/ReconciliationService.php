<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\Transaction;

class ReconciliationService
{
    public function reconcile(BankStatement $bankStatement)
    {
        $transactions = Transaction::where('account_id', $bankStatement->account_id)
            ->whereBetween('transaction_date', [$bankStatement->statement_date->startOfMonth(), $bankStatement->statement_date->endOfMonth()])
            ->get();
    
        $totalCredits = 0;
        $totalDebits = 0;
        $matchedTransactions = collect();
        $unmatchedTransactions = collect();
    
        foreach ($transactions as $transaction) {
            if ($transaction->amount > 0) {
                $totalCredits += $transaction->amount;
            } else {
                $totalDebits += abs($transaction->amount);
            }
    
            if ($this->matchTransaction($transaction, $bankStatement)) {
                $matchedTransactions->push($transaction);
            } else {
                $unmatchedTransactions->push($transaction);
            }
        }
    
        $discrepancy = ($totalCredits - $totalDebits) - ($bankStatement->total_credits - $bankStatement->total_debits);
    
        return [
            'matched_transactions' => $matchedTransactions,
            'unmatched_transactions' => $unmatchedTransactions,
            'discrepancy' => $discrepancy,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'bank_statement_credits' => $bankStatement->total_credits,
            'bank_statement_debits' => $bankStatement->total_debits,
        ];
    }
    
    private function matchTransaction(Transaction $transaction, BankStatement $bankStatement)
    {
        // Implement more sophisticated matching logic
        $matched = $bankStatement->transactions()
            ->where('transaction_date', $transaction->transaction_date)
            ->where('amount', $transaction->amount)
            ->exists();
    
        $transaction->update(['reconciled' => $matched]);
    
        return $matched;
    }

    private function matchTransaction(Transaction $transaction, BankStatement $bankStatement)
    {
        // Implement matching logic here
        // For example, match by date and amount
        $matched = $bankStatement->transactions()
            ->where('transaction_date', $transaction->transaction_date)
            ->where('amount', $transaction->amount)
            ->exists();

        $transaction->update(['reconciled' => $matched]);
    }
}