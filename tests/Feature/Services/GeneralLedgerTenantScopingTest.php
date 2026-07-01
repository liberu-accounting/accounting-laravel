<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\User;
use App\Services\GeneralLedgerService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-1 / P0-T 2b regression: GL reports must not leak another tenant's accounts.
 * Scoping is by the acting user's current team (was user_id before P0-T).
 */
class GeneralLedgerTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Account, 2: Account} acting user, own account, other team's account */
    private function twoTenants(): array
    {
        $teams = app(TeamManagementService::class);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $teamA = $teams->createPersonalTeamForUser($userA);
        $teamB = $teams->createPersonalTeamForUser($userB);

        $mine = Account::factory()->create(['team_id' => $teamA->getKey(), 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 100]);
        $theirs = Account::factory()->create(['team_id' => $teamB->getKey(), 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 999]);

        return [$userA->fresh(), $mine, $theirs];
    }

    public function test_account_balances_only_return_the_acting_teams_accounts(): void
    {
        [$userA, $mine, $theirs] = $this->twoTenants();

        $this->actingAs($userA);
        $ids = app(GeneralLedgerService::class)->getAccountBalances(now()->subYear(), now())->pluck('account_id');

        $this->assertTrue($ids->contains($mine->getKey()), 'own account missing');
        $this->assertFalse($ids->contains($theirs->getKey()), 'LEAK: another tenant\'s account exposed');
    }

    public function test_trial_balance_only_returns_the_acting_teams_accounts(): void
    {
        [$userA, $mine, $theirs] = $this->twoTenants();

        $this->actingAs($userA);
        $ids = app(GeneralLedgerService::class)->getTrialBalance(now())->pluck('account_id');

        $this->assertTrue($ids->contains($mine->getKey()));
        $this->assertFalse($ids->contains($theirs->getKey()), 'LEAK: another tenant\'s account in trial balance');
    }
}
