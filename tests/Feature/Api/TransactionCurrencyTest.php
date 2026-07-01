<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TeamManagementService;
use App\Services\TransactionSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_captures_transaction_currency_and_rate(): void
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();

        $account = Account::factory()->create(['user_id' => $user->id, 'team_id' => $user->current_team_id]);
        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false]);

        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'account_id' => $account->id,
            'amount' => 1000,
            'transaction_date' => '2026-06-01',
            'description' => 'Foreign sale',
            'currency_id' => $eur->currency_id,
            'exchange_rate' => 1.20,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'team_id' => $user->current_team_id,
            'currency_id' => $eur->currency_id,
            'exchange_rate' => 1.20,
        ]);
    }

    public function test_settlement_posts_fx_gain(): void
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();
        $this->actingAs($user);

        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false]);
        $cash = Account::factory()->create(['account_type' => 'asset', 'normal_balance' => 'debit']);
        $fxGain = Account::factory()->create(['account_type' => 'revenue', 'normal_balance' => 'credit']);
        $fxLoss = Account::factory()->create(['account_type' => 'expense', 'normal_balance' => 'debit']);

        // 1000 EUR booked at 1.20, stamped with the acting user's team.
        $tx = Transaction::create([
            'account_id' => $cash->id,
            'team_id' => $user->current_team_id,
            'amount' => 1000,
            'transaction_date' => '2026-06-01',
            'description' => 'Foreign invoice',
            'currency_id' => $eur->currency_id,
            'exchange_rate' => 1.20,
        ]);

        // Settled at 1.25 → +50 FX gain.
        $entry = app(TransactionSettlementService::class)->settle($tx, 1.25, $cash, $fxGain, $fxLoss);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(50.0, $entry->total_debits);
        $this->assertEquals(50.0, $entry->lines()->where('account_id', $fxGain->id)->sum('credit_amount'));
    }
}
