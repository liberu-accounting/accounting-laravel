<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Transaction;
use App\Services\ReconciliationService;
use Carbon\Carbon;
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

    // Case 1: a book txn on the account within the month that is not in the
    // statement's transactions() -> unmatched, with an 'unmatched_transaction'
    // discrepancy carrying the right type/date/amount. Balance nets to zero so
    // this is the only discrepancy.
    public function test_unmatched_transaction_produces_discrepancy_with_correct_shape(): void
    {
        $date = Carbon::parse('2024-06-15');
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => $date,
            'total_credits' => 100,
            'total_debits' => 0,
            'ending_balance' => 100,
            'reconciled' => false,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => null, // not part of the statement -> no match
            'transaction_date' => $date,
            'amount' => 100,
        ]);

        $result = $this->service()->reconcile($statement);

        $this->assertSame(0, $result['matched_transactions']);
        $this->assertSame(1, $result['unmatched_transactions']);

        $discrepancy = $result['discrepancies']->firstWhere('type', 'unmatched_transaction');
        $this->assertNotNull($discrepancy);
        $this->assertSame('unmatched_transaction', $discrepancy['type']);
        $this->assertEquals('2024-06-15', $discrepancy['date']->format('Y-m-d'));
        $this->assertEquals(100, (float) $discrepancy['amount']);
    }

    // Case 2: exact-date match fails but a statement line with the same amount
    // exists within +/-2 days -> fuzzy branch matches. The statement line lives
    // on a different account so reconcile() only pulls the book txn.
    public function test_find_match_uses_fuzzy_window_within_two_days(): void
    {
        $account = Account::factory()->create();
        $otherAccount = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
            'total_credits' => 250,
            'total_debits' => 0,
            'ending_balance' => 250,
        ]);

        // Statement-side line: in transactions() but on another account, so it
        // is not pulled as a book txn by reconcile().
        Transaction::factory()->create([
            'account_id' => $otherAccount->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => Carbon::parse('2024-06-15'),
            'amount' => 250,
        ]);

        // Book txn 2 days off with the same amount -> only the fuzzy arm can match.
        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => null,
            'transaction_date' => Carbon::parse('2024-06-13'),
            'amount' => 250,
        ]);

        $result = $this->service()->reconcile($statement);

        $this->assertSame(1, $result['matched_transactions']);
        $this->assertSame(0, $result['unmatched_transactions']);
    }

    // Case 3: findMatch() returns false when a statement line exists on the same
    // date but with a different amount (fuzzy also requires the exact amount).
    public function test_find_match_returns_false_when_amount_differs(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
            'total_credits' => 0,
            'total_debits' => 0,
            'ending_balance' => 0,
        ]);

        // Self-matching statement line (amount 111).
        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => Carbon::parse('2024-06-15'),
            'amount' => 111,
        ]);

        // Book txn, same date, amount no statement line has -> no exact, no fuzzy.
        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => null,
            'transaction_date' => Carbon::parse('2024-06-15'),
            'amount' => 999,
        ]);

        $result = $this->service()->reconcile($statement);

        $this->assertSame(1, $result['matched_transactions']);   // the 111 line
        $this->assertSame(1, $result['unmatched_transactions']); // the 999 book txn
        $unmatched = $result['discrepancies']->firstWhere('type', 'unmatched_transaction');
        $this->assertNotNull($unmatched);
        $this->assertEquals(999, (float) $unmatched['amount']);
    }

    // Case 4: balance_mismatch discrepancy shape. One matched txn keeps it the
    // only discrepancy: (300 - 0) - (5000 - 0) = -4700.
    public function test_balance_mismatch_discrepancy_has_correct_shape(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
            'total_credits' => 5000,
            'total_debits' => 0,
            'ending_balance' => 5000,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => Carbon::parse('2024-06-15'),
            'amount' => 300,
        ]);

        $result = $this->service()->reconcile($statement);

        $discrepancy = $result['discrepancies']->firstWhere('type', 'balance_mismatch');
        $this->assertNotNull($discrepancy);
        $this->assertSame('balance_mismatch', $discrepancy['type']);
        $this->assertEquals(-4700, (float) $discrepancy['amount']);
        $this->assertEquals(5000, (float) $discrepancy['expected']); // ending_balance
        $this->assertEquals(300, (float) $discrepancy['actual']);    // totalCredits - totalDebits
    }

    // Case 5: reconcileStatement() must stay unreconciled when the balance is
    // fine but there is an unmatched transaction.
    public function test_statement_not_reconciled_when_only_unmatched_but_balance_ok(): void
    {
        $date = Carbon::parse('2024-06-15');
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => $date,
            'total_credits' => 100,
            'total_debits' => 0,
            'ending_balance' => 100,
            'reconciled' => false,
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => null, // unmatched, but nets the balance to zero
            'transaction_date' => $date,
            'amount' => 100,
        ]);

        $result = $this->service()->reconcileStatement($statement);

        $this->assertFalse($result['reconciled']);
        $this->assertGreaterThan(0, $result['unmatched_transactions']);
        $this->assertEqualsWithDelta(0, (float) $result['balance_discrepancy'], 0.01);
        $this->assertFalse($statement->fresh()->reconciled);
    }

    // Case 6: amount == 0 falls into the debit else branch; a mixed set verifies
    // the credit/debit split.
    public function test_amount_zero_counts_as_debit_and_credit_debit_split(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
            'total_credits' => 300,
            'total_debits' => 200,
            'ending_balance' => 100,
        ]);

        foreach ([300, -200, 0] as $amount) {
            Transaction::factory()->create([
                'account_id' => $account->id,
                'bank_statement_id' => $statement->id,
                'transaction_date' => Carbon::parse('2024-06-15'),
                'amount' => $amount,
            ]);
        }

        $result = $this->service()->reconcile($statement);

        // 300 -> credit; -200 -> debit; 0 -> else (debit) branch, adds abs(0)=0.
        $this->assertEquals(300, (float) $result['total_credits']);
        $this->assertEquals(200, (float) $result['total_debits']);
    }

    // Case 7: a txn dated outside the statement month is not pulled by reconcile().
    public function test_transaction_outside_statement_month_is_excluded(): void
    {
        $account = Account::factory()->create();
        $statement = BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
            'total_credits' => 300,
            'total_debits' => 0,
            'ending_balance' => 300,
        ]);

        // In-month, matched.
        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => Carbon::parse('2024-06-15'),
            'amount' => 300,
        ]);

        // Next month -> must be excluded from reconcile().
        Transaction::factory()->create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => Carbon::parse('2024-07-05'),
            'amount' => 300,
        ]);

        $result = $this->service()->reconcile($statement);

        $this->assertSame(1, $result['matched_transactions'] + $result['unmatched_transactions']);
        $this->assertSame(1, $result['matched_transactions']);
        $this->assertSame(0, $result['unmatched_transactions']);
    }
}
