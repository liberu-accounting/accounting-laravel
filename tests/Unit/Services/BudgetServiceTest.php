<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BudgetService;
    }

    public function test_get_budget_comparison_returns_array(): void
    {
        $result = $this->service->getBudgetComparison('2024-01-01', '2024-12-31');

        $this->assertNotNull($result);
    }

    public function test_budget_comparison_includes_variance_key(): void
    {
        $account = Account::factory()->create();
        Budget::factory()->create([
            'account_id' => $account->id,
            'planned_amount' => 1000,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $result = $this->service->getBudgetComparison('2024-01-01', '2024-12-31');

        foreach ($result as $row) {
            $this->assertArrayHasKey('variance', $row);
            $this->assertArrayHasKey('percentage_used', $row);
            $this->assertArrayHasKey('planned_amount', $row);
        }
    }
}
