<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every Expense/Budget $fillable entry (except project_id, owned by the Project
 * feature) must map to a real column. is_indirect + allocation_percentage now
 * have columns (wired via ProjectReportService); Budget::category was a dead
 * cost-allocation stub and was removed from $fillable.
 */
class ExpenseBudgetFillableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_mass_assigns_cost_allocation_columns(): void
    {
        $expense = Expense::create([
            'user_id' => User::factory()->create()->id,
            'description' => 'shared server rent',
            'amount' => 100.00,
            'date' => now()->toDateString(),
            'is_indirect' => true,
            'allocation_percentage' => 25.00,
        ]);

        $fresh = $expense->fresh();

        $this->assertTrue($fresh->exists);
        $this->assertTrue($fresh->is_indirect);
        $this->assertSame('25.00', $fresh->allocation_percentage);
        // getAllocatedAmount() reads both columns: 100 * (25 / 100)
        $this->assertEqualsWithDelta(25.0, (float) $fresh->getAllocatedAmount(), 0.001);
    }

    public function test_budget_mass_assigns_honest_fillable_without_category(): void
    {
        $budget = Budget::factory()->create([
            'description' => 'Q1 marketing',
            'forecast_amount' => 5000,
            'is_approved' => true,
        ]);

        $this->assertTrue($budget->fresh()->exists);
        $this->assertNotContains('category', $budget->getFillable());
    }
}
