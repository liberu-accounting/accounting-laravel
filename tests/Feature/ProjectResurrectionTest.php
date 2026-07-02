<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the resurrected Project feature: the projects table must exist and
 * match Project::$fillable (mass-assignment), and the project_id FK on related
 * tables must let the belongsTo/hasMany relations resolve in both directions.
 */
class ProjectResurrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_mass_assign_matches_fillable_schema(): void
    {
        // Every $fillable key — proves the table has a column for each.
        $project = Project::create([
            'name' => 'Apollo',
            'code' => 'PRJ-001',
            'description' => 'Moon shot',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'allocation_percentage' => 42.50,
        ]);

        $this->assertTrue($project->exists);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-001', 'name' => 'Apollo']);
        $this->assertSame('42.50', (string) $project->fresh()->allocation_percentage);
    }

    public function test_project_factory_creates(): void
    {
        $this->assertTrue(Project::factory()->create()->exists);
    }

    public function test_project_expense_relations_resolve_both_directions(): void
    {
        $project = Project::factory()->create();

        $expense = Expense::create([
            'user_id' => User::factory()->create()->id,
            'description' => 'materials',
            'amount' => 100.00,
            'date' => now()->toDateString(),
            'project_id' => $project->id,
        ]);

        // belongsTo: Expense -> Project
        $this->assertTrue($expense->project->is($project));

        // hasMany: Project -> Expense
        $this->assertTrue($project->expenses()->whereKey($expense->id)->exists());
    }
}
