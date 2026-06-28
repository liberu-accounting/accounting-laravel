<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Transaction;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ReconciliationService
    {
        return app(ReconciliationService::class);
    }

    public function test_statement_marked_reconciled_when_balanced(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => now(),
            'total_credits' => 1000,
            'total_debits' => 500,
            'ending_balance' => 500,
            'reconciled' => false,
        ]);

        Transaction::factory()->count(3)->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => now(),
            'amount' => 300,
        ]);
        Transaction::factory()->count(2)->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => now(),
            'amount' => -200,
        ]);

        $result = $this->service()->reconcileStatement($statement);

        $this->assertTrue($result['reconciled']);
        $this->assertEqualsWithDelta(0, $result['balance_discrepancy'], 0.01);
        $this->assertTrue($statement->fresh()->reconciled);
    }

    public function test_statement_stays_open_when_discrepancy(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => now(),
            'total_credits' => 5000,   // does not match transactions
            'total_debits' => 0,
            'ending_balance' => 5000,
            'reconciled' => false,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => now(),
            'amount' => 300,
        ]);

        $result = $this->service()->reconcileStatement($statement);

        $this->assertFalse($result['reconciled']);
        $this->assertNotEqualsWithDelta(0, $result['balance_discrepancy'], 0.01);
        $this->assertFalse($statement->fresh()->reconciled);
    }
}
