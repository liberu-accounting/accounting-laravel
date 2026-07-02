<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the removal of the dead CostCenter model stub: Expense and Budget
 * must still save (they lost cost_center_id/costCenter), Budget::getActualAmount()
 * must still work without the cost_center branch, and no costCenter relation remains.
 */
class CostCenterRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_saves_without_cost_center(): void
    {
        $expense = Expense::create([
            'user_id' => User::factory()->create()->id,
            'description' => 'lunch',
            'amount' => 42.50,
            'date' => now()->toDateString(),
        ]);

        $this->assertTrue($expense->exists);
        $this->assertFalse(method_exists($expense, 'costCenter'));
    }

    public function test_budget_factory_saves_and_actual_amount_works(): void
    {
        $budget = Budget::factory()->create();

        $this->assertTrue($budget->exists);
        $this->assertFalse(method_exists($budget, 'costCenter'));
        // getActualAmount() lost its cost_center_id branch; it must still run.
        $this->assertSame(0.0, (float) $budget->getActualAmount());
    }
}
