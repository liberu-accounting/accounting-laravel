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

        foreach ($transactions as $transaction) {
            if ($transaction->amount > 0) {
                $totalCredits += $transaction->amount;
            } else {
                $totalDebits += abs($transaction->amount);
            }

            $this->matchTransaction($transaction, $bankStatement);
        }

        $discrepancy = ($totalCredits - $totalDebits) - ($bankStatement->total_credits - $bankStatement->total_debits);

        return [
            'matched_transactions' => $transactions->where('reconciled', true)->count(),
            'unmatched_transactions' => $transactions->where('reconciled', false)->count(),
            'discrepancy' => $discrepancy,
        ];
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