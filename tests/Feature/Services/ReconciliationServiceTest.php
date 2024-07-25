<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Transaction;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_service_matches_transactions()
    {
        $account = Account::factory()->create();
        $bankStatement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => now(),
            'total_credits' => 1000,
            'total_debits' => 500,
            'ending_balance' => 500,
        ]);

        Transaction::factory()->count(3)->create([
            'account_id' => $account->id,
            'transaction_date' => now(),
            'amount' => 300,
        ]);

        Transaction::factory()->count(2)->create([
            'account_id' => $account->id,
            'transaction_date' => now(),
            'amount' => -200,
        ]);

        $reconciliationService = new ReconciliationService();
        $result = $reconciliationService->reconcile($bankStatement);

        $this->assertEquals(5, $result['matched_transactions']);
        $this->assertEquals(0, $result['unmatched_transactions']);
        $this->assertEquals(0, $result['discrepancy']);
    }
}