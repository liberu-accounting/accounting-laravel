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

    public function getKeyMetrics()
    {
        $startDate = now()->startOfMonth();
        $endDate = now();
        $previousStartDate = now()->subMonth()->startOfMonth();
        $previousEndDate = now()->subMonth()->endOfMonth();

        // Get current month revenue
        $revenue = Transaction::whereHas('account', function ($query) {
            $query->where('account_type', 'revenue');
        })
        ->whereBetween('transaction_date', [$startDate, $endDate])
        ->sum('amount');

        // Get previous month revenue for comparison
        $previousRevenue = Transaction::whereHas('account', function ($query) {
            $query->where('account_type', 'revenue');
        })
        ->whereBetween('transaction_date', [$previousStartDate, $previousEndDate])
        ->sum('amount');

        // Get current month expenses
        $expenses = Transaction::whereHas('account', function ($query) {
            $query->where('account_type', 'expense');
        })
        ->whereBetween('transaction_date', [$startDate, $endDate])
        ->sum('amount');

        // Get previous month expenses for comparison
        $previousExpenses = Transaction::whereHas('account', function ($query) {
            $query->where('account_type', 'expense');
        })
        ->whereBetween('transaction_date', [$previousStartDate, $previousEndDate])
        ->sum('amount');

        // Calculate net income
        $netIncome = $revenue - $expenses;
        $previousNetIncome = $previousRevenue - $previousExpenses;

        // Generate chart data (last 7 days)
        $revenueChart = $this->generateChartData('revenue', 7);
        $expensesChart = $this->generateChartData('expense', 7);
        $netIncomeChart = array_map(function ($i) use ($revenueChart, $expensesChart) {
            return $revenueChart[$i] - $expensesChart[$i];
        }, range(0, 6));

        return [
            'revenue' => $revenue,
            'previousRevenue' => $previousRevenue,
            'revenueChange' => $previousRevenue > 0 ? (($revenue - $previousRevenue) / $previousRevenue) * 100 : 0,
            'expenses' => $expenses,
            'previousExpenses' => $previousExpenses,
            'expensesChange' => $previousExpenses > 0 ? (($expenses - $previousExpenses) / $previousExpenses) * 100 : 0,
            'netIncome' => $netIncome,
            'previousNetIncome' => $previousNetIncome,
            'netIncomeChange' => $previousNetIncome > 0 ? (($netIncome - $previousNetIncome) / $previousNetIncome) * 100 : 0,
            'revenueChart' => $revenueChart,
            'expensesChart' => $expensesChart,
            'netIncomeChart' => $netIncomeChart
        ];
    }

    private function generateChartData($accountType, $days)
    {
        $data = [];
        $startDate = now()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $currentDate = (clone $startDate)->addDays($i);

            $amount = Transaction::whereHas('account', function ($query) use ($accountType) {
                $query->where('account_type', $accountType);
            })
            ->whereDate('transaction_date', $currentDate)
            ->sum('amount');

            $data[] = $amount;
        }

        return $data;
    }

}