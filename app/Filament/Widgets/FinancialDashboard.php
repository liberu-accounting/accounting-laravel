<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\GeneralLedgerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialDashboard extends BaseWidget
{
    protected ?string $pollingInterval = '15s';

    protected static bool $isLazy = true;

    #[\Override]
    protected function getStats(): array
    {
        // Resolve via the container: GeneralLedgerService needs ExchangeRateService
        // injected, so `new` would fatal at runtime.
        /** @var array{revenue: float, expenses: float, netIncome: float, revenueChart: array<float>, expensesChart: array<float>, netIncomeChart: array<float>} $metrics */
        $metrics = app(GeneralLedgerService::class)->getKeyMetrics();

        return [
            Stat::make('Total Revenue', '$'.number_format($metrics['revenue'], 2))
                ->description('Total revenue this month')
                ->chart($metrics['revenueChart'])
                ->color('success'),

            Stat::make('Total Expenses', '$'.number_format($metrics['expenses'], 2))
                ->description('Total expenses this month')
                ->chart($metrics['expensesChart'])
                ->color('danger'),

            Stat::make('Net Income', '$'.number_format($metrics['netIncome'], 2))
                ->description('Net income this month')
                ->chart($metrics['netIncomeChart'])
                ->color($metrics['netIncome'] >= 0 ? 'success' : 'danger'),
        ];
    }
}
