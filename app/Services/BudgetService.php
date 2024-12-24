

<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Account;
use Carbon\Carbon;

class BudgetService
{
    public function getBudgetComparison($startDate, $endDate)
    {
        $budgets = Budget::whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->get();

        return $budgets->map(function ($budget) {
            $account = $budget->account;
            $actualAmount = $this->calculateActualAmount($account, $budget);
            $variance = $actualAmount - $budget->planned_amount;
            $percentageUsed = $this->calculatePercentageUsed($actualAmount, $budget->planned_amount);

            return [
                'account_name' => $account->name,
                'planned_amount' => $budget->planned_amount,
                'actual_amount' => $actualAmount,
                'forecast_amount' => $budget->forecast_amount,
                'variance' => $variance,
                'percentage_used' => round($percentageUsed, 2),
                'start_date' => $budget->start_date,
                'end_date' => $budget->end_date
            ];
        });
    }

    public function generateForecast(Budget $budget)
    {
        $account = $budget->account;
        $historicalData = $this->getHistoricalData($account);
        
        // Simple moving average forecast
        $forecastAmount = $this->calculateMovingAverage($historicalData);
        
        $budget->forecast_amount = $forecastAmount;
        $budget->forecast_method = 'moving_average';
        $budget->save();
        
        return $forecastAmount;
    }

    private function getHistoricalData($account)
    {
        return $account->transactions()
            ->select('transaction_date', 'amount')
            ->orderBy('transaction_date', 'desc')
            ->limit(12)
            ->get();
    }

    private function calculateMovingAverage($historicalData)
    {
        return $historicalData->avg('amount');
    }

    private function calculateActualAmount($account, $budget)
    {
        return $account->debitTransactions()
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->sum('amount') -
            $account->creditTransactions()
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->sum('amount');
    }

    private function calculatePercentageUsed($actualAmount, $plannedAmount)
    {
        return $plannedAmount != 0 ? ($actualAmount / $plannedAmount) * 100 : 0;
    }
}