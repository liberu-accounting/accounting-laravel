

<?php

namespace App\Filament\Widgets;

use App\Services\GeneralLedgerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialDashboard extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $ledgerService = new GeneralLedgerService();
        $metrics = $ledgerService->getKeyMetrics();

        return [
            Stat::make('Total Revenue', '$' . number_format($metrics['revenue'], 2))
                ->description('Total revenue this month')
                ->chart($metrics['revenueChart'])
                ->color('success'),

            Stat::make('Total Expenses', '$' . number_format($metrics['expenses'], 2))
                ->description('Total expenses this month')
                ->chart($metrics['expensesChart'])
                ->color('danger'),

            Stat::make('Net Income', '$' . number_format($metrics['netIncome'], 2))
                ->description('Net income this month')
                ->chart($metrics['netIncomeChart'])
                ->color($metrics['netIncome'] >= 0 ? 'success' : 'danger'),
        ];
    }
}