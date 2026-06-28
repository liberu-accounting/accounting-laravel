<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\FxRevaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FxRevaluationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): FxRevaluationService
    {
        return app(FxRevaluationService::class);
    }

    public function test_gain_loss_calculation(): void
    {
        // 1000 units, booked at 1.20, settled at 1.25 → +50 gain
        $this->assertSame(50.0, $this->service()->gainLoss(1000, 1.20, 1.25));
        // settled lower → loss
        $this->assertSame(-50.0, $this->service()->gainLoss(1000, 1.25, 1.20));
    }

    public function test_gain_posts_balanced_entry(): void
    {
        $this->actingAs(User::factory()->create());
        [$counter, $gain, $loss] = $this->accounts();

        $entry = $this->service()->postSettlement(1000, 1.20, 1.25, $counter, $gain, $loss);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(50.0, $entry->total_debits);
        $this->assertEquals(50.0, $entry->lines()->where('account_id', $counter->id)->sum('debit_amount'));
        $this->assertEquals(50.0, $entry->lines()->where('account_id', $gain->id)->sum('credit_amount'));
    }

    public function test_loss_posts_balanced_entry(): void
    {
        $this->actingAs(User::factory()->create());
        [$counter, $gain, $loss] = $this->accounts();

        $entry = $this->service()->postSettlement(1000, 1.25, 1.20, $counter, $gain, $loss);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(50.0, $entry->lines()->where('account_id', $loss->id)->sum('debit_amount'));
        $this->assertEquals(50.0, $entry->lines()->where('account_id', $counter->id)->sum('credit_amount'));
    }

    public function test_no_difference_posts_nothing(): void
    {
        $this->actingAs(User::factory()->create());
        [$counter, $gain, $loss] = $this->accounts();

        $this->assertNull($this->service()->postSettlement(1000, 1.20, 1.20, $counter, $gain, $loss));
    }

    private function accounts(): array
    {
        return [
            Account::factory()->create(['account_type' => 'asset', 'normal_balance' => 'debit']),
            Account::factory()->create(['account_type' => 'revenue', 'normal_balance' => 'credit']),
            Account::factory()->create(['account_type' => 'expense', 'normal_balance' => 'debit']),
        ];
    }
}
