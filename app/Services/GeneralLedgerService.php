<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;

class GeneralLedgerService
{
    public function __construct(protected ExchangeRateService $exchangeRateService) {}

    public function getAccountBalances($startDate, $endDate, ?Currency $displayCurrency = null)
    {
        if (! $displayCurrency instanceof Currency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        return Account::with(['transactions' => function ($query) use ($startDate, $endDate): void {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }])
            ->get()
            ->map(function ($account) use ($displayCurrency): array {
                $balance = $displayCurrency instanceof Currency
                    ? $account->getBalanceInCurrency($displayCurrency)
                    : (float) $account->balance;

                return [
                    'account_id' => $account->getKey(),
                    'account_name' => $account->account_name,
                    'balance' => $balance,
                    'currency' => $displayCurrency?->code,
                ];
            });
    }

    /**
     * The configured reporting currency (config accounting.reporting_currency,
     * by ISO code), or the default currency when unset.
     */
    public function reportingCurrency(): ?Currency
    {
        $code = config('accounting.reporting_currency');

        if ($code) {
            $currency = Currency::where('code', $code)->first();
            if ($currency) {
                return $currency;
            }
        }

        return Currency::where('is_default', true)->first();
    }

    public function getTrialBalance($date, ?Currency $displayCurrency = null)
    {
        if (! $displayCurrency instanceof Currency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        return Account::with(['transactions' => function ($query) use ($date): void {
            $query->where('transaction_date', '<=', $date);
        }])
            ->get()
            ->map(function ($account) use ($displayCurrency): array {
                $balance = $displayCurrency instanceof Currency
                    ? $account->getBalanceInCurrency($displayCurrency)
                    : (float) $account->balance;

                return [
                    'account_id' => $account->getKey(),
                    'account_name' => $account->account_name,
                    'debit' => $balance > 0 ? $balance : 0,
                    'credit' => $balance < 0 ? abs((float) $balance) : 0,
                    'currency' => $displayCurrency?->code,
                ];
            });
    }

    public function getBudgetComparison($startDate, $endDate, ?Currency $displayCurrency = null)
    {
        if (! $displayCurrency instanceof Currency) {
            $displayCurrency = Currency::where('is_default', true)->first();
        }

        $budgetService = new BudgetService;

        return $budgetService->getBudgetComparison($startDate, $endDate);
    }

    public function getKeyMetrics(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now();
        $previousStartDate = now()->subMonth()->startOfMonth();
        $previousEndDate = now()->subMonth()->endOfMonth();

        // Get current month revenue
        $revenue = Transaction::whereHas('account', function ($query): void {
            $query->where('account_type', 'revenue');
        })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        // Get previous month revenue for comparison
        $previousRevenue = Transaction::whereHas('account', function ($query): void {
            $query->where('account_type', 'revenue');
        })
            ->whereBetween('transaction_date', [$previousStartDate, $previousEndDate])
            ->sum('amount');

        // Get current month expenses
        $expenses = Transaction::whereHas('account', function ($query): void {
            $query->where('account_type', 'expense');
        })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        // Get previous month expenses for comparison
        $previousExpenses = Transaction::whereHas('account', function ($query): void {
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
        $netIncomeChart = array_map(fn (int $i): int|float => $revenueChart[$i] - $expensesChart[$i], range(0, 6));

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
            'netIncomeChart' => $netIncomeChart,
        ];
    }

    /**
     * @return mixed[]
     */
    private function generateChartData(string $accountType, int $days): array
    {
        $data = [];
        $startDate = now()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $currentDate = (clone $startDate)->addDays($i);

            $amount = Transaction::whereHas('account', function ($query) use ($accountType): void {
                $query->where('account_type', $accountType);
            })
                ->whereDate('transaction_date', $currentDate)
                ->sum('amount');

            $data[] = $amount;
        }

        return $data;
    }
}
