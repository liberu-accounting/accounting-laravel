<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankStatement;
use App\Models\ReconciliationRule;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class ReconciliationService
{
    /**
     * Run reconciliation and flip the statement's reconciled flag based on the
     * outcome: reconciled only when the balance discrepancy is zero. Returns the
     * reconcile() result with a `reconciled` boolean added.
     */
    public function reconcileStatement(BankStatement $bankStatement): array
    {
        $result = $this->reconcile($bankStatement);

        $reconciled = (int) $result['unmatched_transactions'] === 0
            && abs((float) $result['balance_discrepancy']) < 0.01;
        $bankStatement->update(['reconciled' => $reconciled]);

        $result['reconciled'] = $reconciled;

        return $result;
    }

    public function reconcile(BankStatement $bankStatement): array
    {
        $transactions = Transaction::where('account_id', $bankStatement->account_id)
            ->whereBetween('transaction_date', [
                $bankStatement->statement_date->startOfMonth(),
                $bankStatement->statement_date->endOfMonth(),
            ])
            ->get();

        $totalCredits = 0;
        $totalDebits = 0;
        $matchedTransactions = collect();
        $unmatchedTransactions = collect();
        $discrepancies = collect();

        // Service layer: IsTenantModel is inert here, so scope rules to the
        // statement's team explicitly. Ordered by priority (lower first).
        $rules = ReconciliationRule::query()
            ->where('is_active', true)
            ->where('team_id', $bankStatement->team_id)
            ->orderBy('priority')
            ->get();

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction->amount;
            if ($amount > 0) {
                $totalCredits += $amount;
            } else {
                $totalDebits += abs($amount);
            }

            // User-defined rules take precedence; fall back to the built-in heuristic.
            $matched = $this->applyRules($rules, $transaction)
                || $this->findMatch($transaction, $bankStatement);

            if ($matched) {
                $matchedTransactions->push($transaction);
                $transaction->update(['reconciled' => true]);
            } else {
                $unmatchedTransactions->push($transaction);
                $discrepancies->push([
                    'type' => 'unmatched_transaction',
                    'date' => $transaction->transaction_date,
                    'amount' => $transaction->amount,
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
                'actual' => $totalCredits - $totalDebits,
            ]);
        }

        return [
            'matched_transactions' => $matchedTransactions->count(),
            'unmatched_transactions' => $unmatchedTransactions->count(),
            'discrepancies' => $discrepancies,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'balance_discrepancy' => $balanceDiscrepancy,
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
                $transaction->transaction_date->addDays(2),
            ])
            ->where('amount', $transaction->amount)
            ->exists();

        return $fuzzyMatch;
    }

    /**
     * First active rule whose condition matches assigns its account to the
     * transaction (posting/debit side, so the bank account_id used by
     * reconcile() is untouched) and reports a match.
     *
     * @param  Collection<int, ReconciliationRule>  $rules
     */
    private function applyRules(Collection $rules, Transaction $transaction): bool
    {
        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $transaction)) {
                if ($rule->action_account_id !== null) {
                    $transaction->debit_account_id = $rule->action_account_id;
                }

                return true;
            }
        }

        return false;
    }

    private function ruleMatches(ReconciliationRule $rule, Transaction $transaction): bool
    {
        $field = match ($rule->match_field) {
            'amount' => (string) $transaction->amount,
            'reference' => (string) $transaction->external_id,
            default => (string) $transaction->description, // description
        };

        return match ($rule->match_operator) {
            'contains' => $rule->match_value !== ''
                && stripos($field, (string) $rule->match_value) !== false,
            'equals' => $rule->match_field === 'amount'
                ? abs((float) $transaction->amount - (float) $rule->match_value) < 0.001
                : $field === (string) $rule->match_value,
            'between' => (float) $transaction->amount >= (float) $rule->match_value
                && (float) $transaction->amount <= (float) $rule->match_value_secondary,
            default => false,
        };
    }
}
