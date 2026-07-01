<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryLineTest extends TestCase
{
    use RefreshDatabase;

    private function makeLine(float $debit, float $credit): JournalEntryLine
    {
        return JournalEntryLine::create([
            'journal_entry_id' => JournalEntry::factory()->create()->id,
            'account_id' => Account::factory()->create()->id,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
        ]);
    }

    public function test_amount_returns_debit_when_debit_is_positive(): void
    {
        $this->assertEquals(150.00, $this->makeLine(150.00, 0.00)->amount);
    }

    public function test_amount_returns_credit_when_debit_is_zero(): void
    {
        $this->assertEquals(75.00, $this->makeLine(0.00, 75.00)->amount);
    }

    public function test_type_is_debit_when_debit_is_positive(): void
    {
        $this->assertEquals('debit', $this->makeLine(150.00, 0.00)->type);
    }

    public function test_type_is_credit_when_debit_is_zero(): void
    {
        $this->assertEquals('credit', $this->makeLine(0.00, 75.00)->type);
    }
}
