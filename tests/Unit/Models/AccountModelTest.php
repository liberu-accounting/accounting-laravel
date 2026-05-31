<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_normal_balance_set_automatically_for_asset(): void
    {
        $account = Account::factory()->create([
            'account_type'   => 'asset',
            'normal_balance' => null,
        ]);

        $this->assertEquals('debit', $account->fresh()->normal_balance);
    }

    public function test_account_normal_balance_set_automatically_for_liability(): void
    {
        $account = Account::factory()->create([
            'account_type'   => 'liability',
            'normal_balance' => null,
        ]);

        $this->assertEquals('credit', $account->fresh()->normal_balance);
    }

    public function test_account_normal_balance_set_automatically_for_expense(): void
    {
        $account = Account::factory()->create([
            'account_type'   => 'expense',
            'normal_balance' => null,
        ]);

        $this->assertEquals('debit', $account->fresh()->normal_balance);
    }

    public function test_account_normal_balance_set_automatically_for_revenue(): void
    {
        $account = Account::factory()->create([
            'account_type'   => 'revenue',
            'normal_balance' => null,
        ]);

        $this->assertEquals('credit', $account->fresh()->normal_balance);
    }

    public function test_account_is_active_by_default(): void
    {
        $account = Account::factory()->create();

        $this->assertTrue($account->is_active);
    }

    public function test_account_balance_cast_to_decimal(): void
    {
        $account = Account::factory()->create(['balance' => '1234.5678']);

        $this->assertIsFloat((float) $account->balance);
    }
}
