<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\User;
use App\Services\GeneralLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0-1 regression: GL reports must not leak another tenant's accounts.
 * Before the fix, Account::...->get() returned every user's accounts.
 */
class GeneralLedgerTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_balances_only_return_the_acting_users_accounts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $mine = Account::factory()->create(['user_id' => $userA->id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 100]);
        $theirs = Account::factory()->create(['user_id' => $userB->id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 999]);

        $this->actingAs($userA);
        $ids = app(GeneralLedgerService::class)->getAccountBalances(now()->subYear(), now())->pluck('account_id');

        $this->assertTrue($ids->contains($mine->getKey()), 'own account missing');
        $this->assertFalse($ids->contains($theirs->getKey()), 'LEAK: another tenant\'s account exposed');
    }

    public function test_trial_balance_only_returns_the_acting_users_accounts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $mine = Account::factory()->create(['user_id' => $userA->id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 100]);
        $theirs = Account::factory()->create(['user_id' => $userB->id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 999]);

        $this->actingAs($userA);
        $ids = app(GeneralLedgerService::class)->getTrialBalance(now())->pluck('account_id');

        $this->assertTrue($ids->contains($mine->getKey()));
        $this->assertFalse($ids->contains($theirs->getKey()), 'LEAK: another tenant\'s account in trial balance');
    }
}
