<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\TaxRate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A compound rate must only stack non-compound rates from its OWN team.
 * Before the team_id scope, calculateTax() summed every team's active
 * non-compound rate into the document — a cross-tenant money leak.
 */
class TaxRateTeamScopingTest extends TestCase
{
    use RefreshDatabase;

    private function team(string $name): Team
    {
        return Team::forceCreate(['user_id' => User::factory()->create()->id, 'name' => $name, 'personal_team' => true]);
    }

    public function test_compound_stacking_ignores_other_teams_non_compound_rates(): void
    {
        $teamA = $this->team('A');
        $teamB = $this->team('B');

        $compound = TaxRate::factory()->create(['team_id' => $teamA->id, 'rate' => 10, 'is_compound' => true]);
        TaxRate::factory()->create(['team_id' => $teamA->id, 'rate' => 5]);   // own rate → stacked
        TaxRate::factory()->create(['team_id' => $teamB->id, 'rate' => 50]);  // other team → must NOT leak in

        $bill = Bill::factory()->make([
            'team_id' => $teamA->id,
            'tax_rate_id' => $compound->tax_rate_id,
            'subtotal_amount' => 100,
        ]);

        // Only team A's 5% stacks: (100 + 5) * 10% = 10.5.
        // The leak also stacked team B's 50%: (100 + 55) * 10% = 15.5.
        $this->assertEqualsWithDelta(10.5, $bill->calculateTax(), 0.0001);
    }
}
