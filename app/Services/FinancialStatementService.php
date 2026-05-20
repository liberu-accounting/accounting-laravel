<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialStatementService
{
    /**
     * Generate Profit & Loss (Income Statement) for a given period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function profitAndLoss(Carbon $startDate, Carbon $endDate): array
    {
        // Get revenue accounts (income)
        $revenueAccounts = Account::where('type', 'revenue')
            ->orderBy('code')
            ->get();
        
        // Get expense accounts
        $expenseAccounts = Account::where('type', 'expense')
            ->orderBy('code')
            ->get();
        
        // Get cost of goods sold accounts
        $cogsAccounts = Account::where('type', 'cost_of_goods_sold')
            ->orWhere('name', 'like', '%cost of goods%')
            ->orderBy('code')
            ->get();

        // Calculate balances for each account
        $revenue = $this->calculateAccountsBalance($revenueAccounts, $startDate, $endDate);
        $cogs = $this->calculateAccountsBalance($cogsAccounts, $startDate, $endDate);
        $expenses = $this->calculateAccountsBalance($expenseAccounts, $startDate, $endDate);

        // Calculate totals
        $totalRevenue = $revenue->sum('balance');
        $totalCogs = abs($cogs->sum('balance'));
        $grossProfit = $totalRevenue - $totalCogs;
        $totalExpenses = abs($expenses->sum('balance'));
        $netIncome = $grossProfit - $totalExpenses;

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'revenue' => [
                'accounts' => $revenue,
                'total' => $totalRevenue,
            ],
            'cost_of_goods_sold' => [
                'accounts' => $cogs,
                'total' => $totalCogs,
            ],
            'gross_profit' => $grossProfit,
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
        ];
    }

    /**
     * Generate Balance Sheet for a given date
     *
     * @param Carbon $asOfDate
     * @return array
     */
    public function balanceSheet(Carbon $asOfDate): array
    {
        // Get asset accounts
        $assetAccounts = Account::whereIn('type', ['asset', 'bank', 'current_asset', 'fixed_asset', 'other_asset'])
            ->orderBy('code')
            ->get();
        
        // Get liability accounts
        $liabilityAccounts = Account::whereIn('type', ['liability', 'current_liability', 'long_term_liability'])
            ->orderBy('code')
            ->get();
        
        // Get equity accounts
        $equityAccounts = Account::where('type', 'equity')
            ->orderBy('code')
            ->get();

        // Calculate balances as of date
        $assets = $this->calculateAccountsBalance($assetAccounts, null, $asOfDate);
        $liabilities = $this->calculateAccountsBalance($liabilityAccounts, null, $asOfDate);
        $equity = $this->calculateAccountsBalance($equityAccounts, null, $asOfDate);

        // Calculate retained earnings (net income for the period)
        $retainedEarnings = $this->calculateRetainedEarnings($asOfDate);

        // Calculate totals
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = abs($liabilities->sum('balance'));
        $totalEquity = $equity->sum('balance') + $retainedEarnings;

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
        ];
    }

    /**
     * Generate Cash Flow Statement for a given period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function cashFlowStatement(Carbon $startDate, Carbon $endDate): array
    {
        // Get net income from P&L
        $profitLoss = $this->profitAndLoss($startDate, $endDate);
        $netIncome = $profitLoss['net_income'];

        // Operating Activities
        $operatingActivities = $this->calculateOperatingCashFlow($startDate, $endDate, $netIncome);
        
        // Investing Activities
        $investingActivities = $this->calculateInvestingCashFlow($startDate, $endDate);
        
        // Financing Activities
        $financingActivities = $this->calculateFinancingCashFlow($startDate, $endDate);

        // Calculate net change in cash
        $netCashFlow = $operatingActivities['net_cash_from_operations'] 
                      + $investingActivities['net_cash_from_investing']
                      + $financingActivities['net_cash_from_financing'];

        // Get beginning and ending cash balances
        $beginningCash = $this->getCashBalance($startDate->copy()->subDay());
        $endingCash = $this->getCashBalance($endDate);

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_change_in_cash' => $netCashFlow,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
        ];
    }

    /**
     * Calculate account balances for given accounts and date range
     *
     * @param \Illuminate\Support\Collection $accounts
     * @param Carbon|null $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    protected function calculateAccountsBalance($accounts, $startDate, $endDate)
    {
        return $accounts->map(function ($account) use ($startDate, $endDate) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $balance,
            ];
        })->filter(function ($account) {
            // Only show accounts with non-zero balances
            return abs($account['balance']) > 0.01;
        });
    }

    /**
     * Get account balance for a specific period
     *
     * @param int $accountId
     * @param Carbon|null $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function getAccountBalance(int $accountId, $startDate, Carbon $endDate): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        $query = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')
                  ->where('entry_date', '<=', $endDate);
                
                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }
            });

        $debits = $query->sum('debit');
        $credits = $query->sum('credit');

        // Calculate balance based on account type
        // Assets and Expenses: Debit increases, Credit decreases
        // Liabilities, Equity, Revenue: Credit increases, Debit decreases
        $normalBalanceIsDebit = in_array($account->type, ['asset', 'expense', 'bank', 'current_asset', 'fixed_asset', 'other_asset', 'cost_of_goods_sold']);
        
        $balance = $normalBalanceIsDebit ? ($debits - $credits) : ($credits - $debits);

        // Add opening balance if no start date (balance sheet)
        if (!$startDate && $account->opening_balance) {
            $balance += $account->opening_balance;
        }

        return $balance;
    }

    /**
     * Calculate retained earnings as of a specific date
     *
     * @param Carbon $asOfDate
     * @return float
     */
    protected function calculateRetainedEarnings(Carbon $asOfDate): float
    {
        // Get net income from the beginning of time to the as-of date
        $startOfTime = Carbon::parse('2000-01-01'); // Or your company's start date
        $profitLoss = $this->profitAndLoss($startOfTime, $asOfDate);
        
        return $profitLoss['net_income'];
    }

    /**
     * Calculate operating cash flow
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param float $netIncome
     * @return array
     */
    protected function calculateOperatingCashFlow(Carbon $startDate, Carbon $endDate, float $netIncome): array
    {
        // Simplified version - in a real implementation, you'd add back non-cash expenses
        // and adjust for changes in working capital
        
        $adjustments = [
            'depreciation' => 0, // Would need to calculate from depreciation entries
            'accounts_receivable_change' => 0,
            'accounts_payable_change' => 0,
            'inventory_change' => 0,
        ];

        $netCashFromOperations = $netIncome + array_sum($adjustments);

        return [
            'net_income' => $netIncome,
            'adjustments' => $adjustments,
            'net_cash_from_operations' => $netCashFromOperations,
        ];
    }

    /**
     * Calculate investing cash flow
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function calculateInvestingCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        // Get cash flows from investing activities (purchase/sale of fixed assets, investments)
        $fixedAssetPurchases = 0; // Would calculate from asset acquisition transactions
        $fixedAssetSales = 0;

        return [
            'fixed_asset_purchases' => $fixedAssetPurchases,
            'fixed_asset_sales' => $fixedAssetSales,
            'net_cash_from_investing' => $fixedAssetSales - $fixedAssetPurchases,
        ];
    }

    /**
     * Calculate financing cash flow
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function calculateFinancingCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        // Get cash flows from financing activities (loans, equity, dividends)
        $loanProceeds = 0; // Would calculate from loan transactions
        $loanRepayments = 0;
        $ownerContributions = 0;
        $ownerDraws = 0;

        return [
            'loan_proceeds' => $loanProceeds,
            'loan_repayments' => $loanRepayments,
            'owner_contributions' => $ownerContributions,
            'owner_draws' => $ownerDraws,
            'net_cash_from_financing' => $loanProceeds - $loanRepayments + $ownerContributions - $ownerDraws,
        ];
    }

    /**
     * Get cash balance as of a specific date
     *
     * @param Carbon $asOfDate
     * @return float
     */
    protected function getCashBalance(Carbon $asOfDate): float
    {
        $cashAccounts = Account::whereIn('type', ['bank', 'cash'])
            ->orWhere('name', 'like', '%cash%')
            ->get();

        return $cashAccounts->sum(function ($account) use ($asOfDate) {
            return $this->getAccountBalance($account->id, null, $asOfDate);
        });
    }
}
