<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\JournalEntry;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create test accounts
        Account::create([
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

        Account::create([
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
    }

    /** @test */
    public function journal_entry_can_be_created_with_balanced_lines()
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Test entry',
        ]);

        $cashAccount = Account::where('account_number', 1010)->first();
        $revenueAccount = Account::where('account_number', 4010)->first();

        $journalEntry->lines()->create([
            'account_id' => $cashAccount->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0.00,
            'description' => 'Cash received',
        ]);

        $journalEntry->lines()->create([
            'account_id' => $revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 500.00,
            'description' => 'Sales revenue',
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id,
            'entry_type' => 'general',
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $cashAccount->id,
            'debit_amount' => 500.00,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'credit_amount' => 500.00,
        ]);
    }

    /** @test */
    public function posting_journal_entry_updates_account_balances()
    {
        $cashAccount = Account::where('account_number', 1010)->first();
        $revenueAccount = Account::where('account_number', 4010)->first();

        $initialCashBalance = $cashAccount->balance;
        $initialRevenueBalance = $revenueAccount->balance;

        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'Sales transaction',
        ]);

        $journalEntry->lines()->create([
            'account_id' => $cashAccount->id,
            'debit_amount' => 250.00,
            'credit_amount' => 0.00,
        ]);

        $journalEntry->lines()->create([
            'account_id' => $revenueAccount->id,
            'debit_amount' => 0.00,
            'credit_amount' => 250.00,
        ]);

        $journalEntry->post();

        $cashAccount->refresh();
        $revenueAccount->refresh();

        $this->assertEquals($initialCashBalance + 250.00, $cashAccount->balance);
        $this->assertEquals($initialRevenueBalance + 250.00, $revenueAccount->balance);
    }

    /** @test */
    public function journal_entry_has_auto_generated_entry_number()
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $this->assertNotNull($journalEntry->entry_number);
        $this->assertStringStartsWith('JE-', $journalEntry->entry_number);
    }

    /** @test */
    public function journal_entry_supports_multiple_entry_types()
    {
        $types = ['general', 'adjusting', 'closing', 'reversing'];

        foreach ($types as $type) {
            $entry = JournalEntry::create([
                'user_id' => $this->user->id,
                'entry_date' => now(),
                'entry_type' => $type,
            ]);

            $this->assertEquals($type, $entry->entry_type);
        }
    }

    /** @test */
    public function journal_entry_lines_can_have_descriptions()
    {
        $journalEntry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_date' => now(),
            'entry_type' => 'general',
        ]);

        $cashAccount = Account::where('account_number', 1010)->first();

        $line = $journalEntry->lines()->create([
            'account_id' => $cashAccount->id,
            'debit_amount' => 100.00,
            'credit_amount' => 0.00,
            'description' => 'Payment received from customer',
        ]);

        $this->assertEquals('Payment received from customer', $line->description);
    }
}
