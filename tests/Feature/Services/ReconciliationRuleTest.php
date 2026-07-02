<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\ReconciliationRule;
use App\Models\Transaction;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_auto_assigns_account_and_reconciles_matching_transaction(): void
    {
        $bankAccount = Account::factory()->create();
        $expenseAccount = Account::factory()->create();

        $bankStatement = BankStatement::factory()->create([
            'account_id' => $bankAccount->id,
            'statement_date' => now(),
            'total_credits' => 5000,
            'total_debits' => 0,
            'ending_balance' => 5000,
        ]);

        $matching = Transaction::factory()->create([
            'account_id' => $bankAccount->id,
            'bank_statement_id' => $bankStatement->id,
            'transaction_date' => now(),
            'amount' => 5000,
            'description' => 'ACME CORP SALARY PAYMENT',
        ]);

        ReconciliationRule::factory()->create([
            'team_id' => $bankStatement->team_id,
            'name' => 'Salary',
            'match_field' => 'description',
            'match_operator' => 'contains',
            'match_value' => 'SALARY',
            'action_account_id' => $expenseAccount->id,
            'priority' => 0,
            'is_active' => true,
        ]);

        $result = (new ReconciliationService)->reconcile($bankStatement);

        $matching->refresh();
        $this->assertTrue($matching->reconciled);
        // Only a rule assigns the posting account; the heuristic never does.
        $this->assertEquals($expenseAccount->id, $matching->debit_account_id);
        $this->assertEquals(1, $result['matched_transactions']);
    }

    public function test_non_matching_transaction_falls_back_to_heuristic(): void
    {
        $bankAccount = Account::factory()->create();
        $expenseAccount = Account::factory()->create();

        $bankStatement = BankStatement::factory()->create([
            'account_id' => $bankAccount->id,
            'statement_date' => now(),
            'total_credits' => 250,
            'total_debits' => 0,
            'ending_balance' => 250,
        ]);

        $other = Transaction::factory()->create([
            'account_id' => $bankAccount->id,
            'bank_statement_id' => $bankStatement->id,
            'transaction_date' => now(),
            'amount' => 250,
            'description' => 'OFFICE RENT',
        ]);

        ReconciliationRule::factory()->create([
            'team_id' => $bankStatement->team_id,
            'match_field' => 'description',
            'match_operator' => 'contains',
            'match_value' => 'SALARY',
            'action_account_id' => $expenseAccount->id,
        ]);

        (new ReconciliationService)->reconcile($bankStatement);

        $other->refresh();
        // Heuristic self-match still reconciles it, but no rule fired so no
        // account was assigned.
        $this->assertTrue($other->reconciled);
        $this->assertNull($other->debit_account_id);
    }
}
