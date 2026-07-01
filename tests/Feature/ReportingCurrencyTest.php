<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\GeneralLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_renders_in_reporting_currency(): void
    {
        $usd = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]);
        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false]);
        ExchangeRate::create(['from_currency_id' => $usd->currency_id, 'to_currency_id' => $eur->currency_id, 'rate' => 0.90, 'date' => now()->toDateString()]);

        // GL reports are tenant-scoped (P0-1): act as the account owner.
        $user = User::factory()->create();
        $this->actingAs($user);

        // Account holds 1000 in the default currency (no explicit currency = default).
        Account::factory()->create(['user_id' => $user->id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 1000]);

        $rows = app(GeneralLedgerService::class)->getTrialBalance(now(), $eur);

        $row = $rows->first();
        $this->assertSame('EUR', $row['currency']);
        $this->assertEqualsWithDelta(900.0, $row['debit'], 0.01); // 1000 USD × 0.90
    }
}
