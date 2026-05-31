<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FinancialStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialStatementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialStatementService;
    }

    public function test_profit_and_loss_returns_expected_keys(): void
    {
        $result = $this->service->profitAndLoss(
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-12-31'),
        );

        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('gross_profit', $result);
        $this->assertArrayHasKey('net_income', $result);
    }

    public function test_profit_and_loss_period_reflects_input_dates(): void
    {
        $result = $this->service->profitAndLoss(
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-06-30'),
        );

        $this->assertEquals('2024-01-01', $result['period']['start_date']);
        $this->assertEquals('2024-06-30', $result['period']['end_date']);
    }

    public function test_balance_sheet_returns_expected_keys(): void
    {
        $result = $this->service->balanceSheet(Carbon::parse('2024-12-31'));

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
    }

    public function test_balance_sheet_net_assets_equals_equity(): void
    {
        $result = $this->service->balanceSheet(Carbon::parse('2024-12-31'));

        $netAssets = ($result['assets']['total'] ?? 0) - ($result['liabilities']['total'] ?? 0);
        $equity    = $result['equity']['total'] ?? 0;

        $this->assertEqualsWithDelta($netAssets, $equity, 0.01);
    }
}
