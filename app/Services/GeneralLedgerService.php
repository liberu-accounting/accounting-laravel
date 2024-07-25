<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class GeneralLedgerService
{
    public function getAccountBalances($startDate, $endDate)
    {
        return Account::with(['debitTransactions' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }, 'creditTransactions' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($account) {
            $debitSum = $account->debitTransactions->sum('amount');
            $creditSum = $account->creditTransactions->sum('amount');
            $balance = $debitSum - $creditSum;
            
            return [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
        });
    }

    public function getTrialBalance($date)
    {
        return Account::with(['debitTransactions' => function ($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }, 'creditTransactions' => function ($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }])
        ->get()
        ->map(function ($account) {
            $debitSum = $account->debitTransactions->sum('amount');
            $creditSum = $account->creditTransactions->sum('amount');
            $balance = $debitSum - $creditSum;
            
            return [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
            ];
        });
    }
}