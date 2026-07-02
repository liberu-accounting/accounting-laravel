<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\FinancialStatementService;
use App\Services\TeamManagementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: financial statements were built from unscoped Account queries, so
 * one team's P&L / balance sheet mixed in every other team's accounts and totals.
 * Scoping is by the acting user's current team.
 */
class FinancialStatementTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $start;

    private Carbon $end;

    protected function setUp(): void
    {
        parent::setUp();
        $this->start = Carbon::parse('2024-01-01');
        $this->end = Carbon::parse('2024-12-31');
    }

    /**
     * A team with a revenue account (credited $revenue), an expense account
     * (debited $expense) and an asset account holding $assetOpening.
     *
     * @return array{user: User, revenue: Account, expense: Account, asset: Account}
     */
    private function teamWithBooks(float $revenue, float $expense, float $assetOpening): array
    {
        $user = User::factory()->create();
        $teamId = app(TeamManagementService::class)->createPersonalTeamForUser($user)->getKey();

        $rev = Account::factory()->create(['team_id' => $teamId, 'account_type' => 'revenue', 'normal_balance' => 'credit', 'opening_balance' => 0]);
        $exp = Account::factory()->create(['team_id' => $teamId, 'account_type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0]);
        $asset = Account::factory()->create(['team_id' => $teamId, 'account_type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => $assetOpening]);

        $this->postLine($rev, credit: $revenue);
        $this->postLine($exp, debit: $expense);

        return ['user' => $user->fresh(), 'revenue' => $rev, 'expense' => $exp, 'asset' => $asset];
    }

    private function postLine(Account $account, float $debit = 0, float $credit = 0): void
    {
        $entry = JournalEntry::factory()->posted()->create(['entry_date' => '2024-06-15']);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
        ]);
    }

    public function test_profit_and_loss_only_includes_the_acting_teams_accounts(): void
    {
        $a = $this->teamWithBooks(revenue: 1000, expense: 400, assetOpening: 1500);
        $b = $this->teamWithBooks(revenue: 5000, expense: 2000, assetOpening: 9999);

        $this->actingAs($a['user']);
        $pl = app(FinancialStatementService::class)->profitAndLoss($this->start, $this->end);

        $revenueIds = collect($pl['revenue']['accounts'])->pluck('id');
        $this->assertTrue($revenueIds->contains($a['revenue']->id), 'own revenue account missing');
        $this->assertFalse($revenueIds->contains($b['revenue']->id), "LEAK: another tenant's revenue account in P&L");

        // Totals must reflect only team A (1000 / 400), never the mixed 6000 / 2400.
        $this->assertEqualsWithDelta(1000, $pl['revenue']['total'], 0.01);
        $this->assertEqualsWithDelta(400, $pl['expenses']['total'], 0.01);
        $this->assertEqualsWithDelta(600, $pl['net_income'], 0.01);
    }

    public function test_balance_sheet_only_includes_the_acting_teams_accounts(): void
    {
        $a = $this->teamWithBooks(revenue: 1000, expense: 400, assetOpening: 1500);
        $b = $this->teamWithBooks(revenue: 5000, expense: 2000, assetOpening: 9999);

        $this->actingAs($a['user']);
        $bs = app(FinancialStatementService::class)->balanceSheet($this->end);

        $assetIds = collect($bs['assets']['accounts'])->pluck('id');
        $this->assertTrue($assetIds->contains($a['asset']->id), 'own asset account missing');
        $this->assertFalse($assetIds->contains($b['asset']->id), "LEAK: another tenant's asset account in balance sheet");
        $this->assertEqualsWithDelta(1500, $bs['assets']['total'], 0.01);
    }
}
