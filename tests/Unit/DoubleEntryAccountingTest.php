<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleEntryAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $cashAccount;

    protected $revenueAccount;

    protected $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test accounts
        $this->cashAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 1010,
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'balance' => 1000.00,
            'opening_balance' => 1000.00,
            'is_active' => true,
            'allow_manual_entry' => true,
        ]);

        $this->revenueAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 4010,
            'account_name' => 'Sales Revenue',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'balance' => 0.00,
            'opening_balance' => 0.00,
            'is_active' => true,
            'allow_manual_entry' => true,
        ]);

        $this->expenseAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 5010,
            'account_name' => 'Rent Expense',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'balance' => 0.00,
            'opening_balance' => 0.00,
            'is_active' => true,
            'allow_manual_entry' => true,
        ]);
    }

    public function test_can_create_a_balanced_journal_entry(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Test entry',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0.00,
            'description' => 'Debit cash',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 500.00,
            'description' => 'Credit revenue',
        ]);

        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals(500.00, $journalEntry->total_debits);
        $this->assertEquals(500.00, $journalEntry->total_credits);
    }

    public function test_detects_unbalanced_journal_entry(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Unbalanced entry',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 300.00,
        ]);

        $this->assertFalse($journalEntry->isBalanced());
    }

    public function test_posts_journal_entry_and_updates_account_balances(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Post test',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 500.00,
        ]);

        $initialCashBalance = $this->cashAccount->balance;
        $initialRevenueBalance = $this->revenueAccount->balance;

        $journalEntry->post();

        $this->cashAccount->refresh();
        $this->revenueAccount->refresh();

        // Cash (asset) has debit normal balance, so debit increases it
        $this->assertEquals($initialCashBalance + 500.00, $this->cashAccount->balance);

        // Revenue has credit normal balance, so credit increases it
        $this->assertEquals($initialRevenueBalance + 500.00, $this->revenueAccount->balance);

        $this->assertTrue($journalEntry->is_posted);
        $this->assertNotNull($journalEntry->posted_at);
    }

    public function test_reverses_journal_entry_and_restores_account_balances(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Reverse test',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 200.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 200.00,
        ]);

        $journalEntry->post();

        $balanceAfterPost = $this->cashAccount->fresh()->balance;

        $journalEntry->reverse();

        $this->cashAccount->refresh();
        $this->revenueAccount->refresh();

        // Balances should be restored
        $this->assertEquals($balanceAfterPost - 200.00, $this->cashAccount->balance);
        $this->assertEquals(0.00, $this->revenueAccount->balance);

        $this->assertFalse($journalEntry->is_posted);
        $this->assertNull($journalEntry->posted_at);
    }

    public function test_prevents_posting_unbalanced_journal_entry(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Journal entry must be balanced before posting');

        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 300.00,
        ]);

        $journalEntry->post();
    }

    public function test_prevents_posting_already_posted_entry(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Journal entry is already posted');

        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 100.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 100.00,
        ]);

        $journalEntry->post();
        $journalEntry->post(); // Should throw exception
    }

    public function test_generates_unique_entry_numbers(): void
    {
        $entry1 = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $entry2 = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $this->assertNotNull($entry1->entry_number);
        $this->assertNotNull($entry2->entry_number);
        $this->assertNotEquals($entry1->entry_number, $entry2->entry_number);
    }

    public function test_account_can_check_if_it_accepts_entries(): void
    {
        // Active account with no children should accept entries
        $this->assertTrue($this->cashAccount->canAcceptEntries());

        // Create a parent account
        $parentAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 1000,
            'account_name' => 'Current Assets',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'balance' => 0.00,
            'is_active' => true,
            'allow_manual_entry' => true,
        ]);

        // Add child to parent
        $this->cashAccount->parent_id = $parentAccount->id;
        $this->cashAccount->save();

        // Parent account with children should not accept entries
        $this->assertFalse($parentAccount->canAcceptEntries());

        // Inactive account should not accept entries
        $this->cashAccount->is_active = false;
        $this->cashAccount->save();
        $this->assertFalse($this->cashAccount->canAcceptEntries());
    }

    public function test_account_normal_balance_is_set_automatically(): void
    {
        $assetAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 1020,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $this->assertEquals('debit', $assetAccount->normal_balance);

        $liabilityAccount = Account::create([
            'user_id' => $this->user->id,
            'account_number' => 2010,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $this->assertEquals('credit', $liabilityAccount->normal_balance);
    }

    public function test_reverse_throws_on_unposted_entry(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot reverse an unposted journal entry');

        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 100.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 100.00,
        ]);

        $journalEntry->reverse();
    }

    public function test_post_decreases_balances_on_reverse_direction_entries(): void
    {
        // Credit a debit-normal account and debit a credit-normal account:
        // both should DECREASE (exercises the subtraction arms of post()).
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        // Credit cash (debit-normal): balance -= 200
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 200.00,
        ]);

        // Debit revenue (credit-normal): balance -= 200
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 200.00,
            'credit_amount' => 0.00,
        ]);

        $journalEntry->post();

        $this->cashAccount->refresh();
        $this->revenueAccount->refresh();

        $this->assertEquals(800.00, $this->cashAccount->balance);   // 1000 - 200
        $this->assertEquals(-200.00, $this->revenueAccount->balance); // 0 - 200
    }

    public function test_post_line_with_both_debit_and_credit_uses_net_effect(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        // Cash line carries BOTH debit and credit: nets +300 (500 - 200)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 200.00,
        ]);

        // Revenue credit 300 keeps it balanced (debits 500 == credits 200 + 300)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 300.00,
        ]);

        $this->assertTrue($journalEntry->isBalanced());

        $journalEntry->post();

        $this->cashAccount->refresh();
        $this->revenueAccount->refresh();

        $this->assertEquals(1300.00, $this->cashAccount->balance);  // 1000 + (500 - 200)
        $this->assertEquals(300.00, $this->revenueAccount->balance); // 0 + 300
    }

    public function test_is_balanced_treats_sub_cent_difference_as_balanced(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => 100.001,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 100.00,
        ]);

        // isBalanced() compares with bccomp(..., 2), so the sub-cent tail is ignored.
        $this->assertTrue($journalEntry->isBalanced());
    }

    public function test_generate_entry_number_produces_sequential_formatted_numbers(): void
    {
        $year = date('Y');

        $entry1 = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $entry2 = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $this->assertEquals("JE-{$year}-000001", $entry1->entry_number);
        $this->assertEquals("JE-{$year}-000002", $entry2->entry_number);
    }

    public function test_entry_number_is_not_overwritten_when_supplied(): void
    {
        $entry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'CUSTOM-0001',
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $this->assertEquals('CUSTOM-0001', $entry->entry_number);
        $this->assertEquals('CUSTOM-0001', $entry->fresh()->entry_number);
    }

    public function test_user_id_is_auto_set_from_authenticated_user(): void
    {
        $this->actingAs($this->user);

        // No user_id supplied: boot creating should fill it from auth()->id()
        $entry = JournalEntry::create([
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $this->assertEquals($this->user->id, $entry->user_id);
        $this->assertEquals($this->user->id, $entry->fresh()->user_id);
    }

    public function test_is_balanced_with_negative_amounts(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => -50.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => -50.00,
        ]);

        $this->assertTrue($journalEntry->isBalanced());
        $this->assertEquals(-50.00, $journalEntry->total_debits);
        $this->assertEquals(-50.00, $journalEntry->total_credits);
    }

    public function test_post_with_negative_amounts_adjusts_balances(): void
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => -50.00,
            'credit_amount' => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => -50.00,
        ]);

        $journalEntry->post();

        $this->cashAccount->refresh();
        $this->revenueAccount->refresh();

        // Cash (debit-normal): 1000 + (-50 - 0) = 950
        $this->assertEquals(950.00, $this->cashAccount->balance);
        // Revenue (credit-normal): 0 + (-50 - 0) = -50
        $this->assertEquals(-50.00, $this->revenueAccount->balance);
    }
}
