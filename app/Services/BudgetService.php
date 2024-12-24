

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
            $actualAmount = $account->debitTransactions()
                ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
                ->sum('amount') -
                $account->creditTransactions()
                ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
                ->sum('amount');

            $variance = $actualAmount - $budget->planned_amount;
            $percentageUsed = $budget->planned_amount != 0 ? 
                ($actualAmount / $budget->planned_amount) * 100 : 0;

            return [
                'account_name' => $account->name,
                'planned_amount' => $budget->planned_amount,
                'actual_amount' => $actualAmount,
                'variance' => $variance,
                'percentage_used' => round($percentageUsed, 2),
                'start_date' => $budget->start_date,
                'end_date' => $budget->end_date
            ];
        });
    }
}