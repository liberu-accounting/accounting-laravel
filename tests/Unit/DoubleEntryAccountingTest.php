<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    /** @test */
    public function it_can_create_a_balanced_journal_entry()
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

    /** @test */
    public function it_detects_unbalanced_journal_entry()
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

    /** @test */
    public function it_posts_journal_entry_and_updates_account_balances()
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

    /** @test */
    public function it_reverses_journal_entry_and_restores_account_balances()
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

    /** @test */
    public function it_prevents_posting_unbalanced_journal_entry()
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

    /** @test */
    public function it_prevents_posting_already_posted_entry()
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

    /** @test */
    public function it_generates_unique_entry_numbers()
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

    /** @test */
    public function account_can_check_if_it_accepts_entries()
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

    /** @test */
    public function account_normal_balance_is_set_automatically()
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
}
