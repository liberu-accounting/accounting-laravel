<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class GeneralLedgerService
{
    protected $exchangeRateService;
    
    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function getAccountBalances($startDate, $endDate, Currency $displayCurrency = null)
    {
        if (!$displayCurrency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        return Account::with(['transactions' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($account) use ($displayCurrency) {
            $balance = $account->getBalanceInCurrency($displayCurrency);
            
            return [
                'account_id' => $account->account_id,
                'account_name' => $account->account_name,
                'balance' => $balance,
                'currency' => $displayCurrency->code,
            ];
        });
    }

    public function getTrialBalance($date, Currency $displayCurrency = null)
    {
        if (!$displayCurrency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        return Account::with(['transactions' => function ($query) use ($date) {
            $query->where('transaction_date', '<=', $date);
        }])
        ->get()
        ->map(function ($account) use ($displayCurrency) {
            $balance = $account->getBalanceInCurrency($displayCurrency);
            
            return [
                'account_id' => $account->account_id,
                'account_name' => $account->account_name,
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
                'currency' => $displayCurrency->code,
            ];
        });
    }

    public function getBudgetComparison($startDate, $endDate, Currency $displayCurrency = null)
    {
        if (!$displayCurrency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        $budgetService = new BudgetService();
        return $budgetService->getBudgetComparison($startDate, $endDate, $displayCurrency);
    }
}