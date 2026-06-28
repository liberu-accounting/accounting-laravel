<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_entry_is_not_posted_by_default(): void
    {
        $entry = JournalEntry::factory()->create();

        $this->assertFalse($entry->is_posted);
    }

    public function test_journal_entry_is_not_approved_by_default(): void
    {
        $entry = JournalEntry::factory()->create();

        $this->assertFalse($entry->is_approved);
    }

    public function test_journal_entry_has_lines_relationship(): void
    {
        $entry = JournalEntry::factory()->create();

        $this->assertNotNull($entry->lines());
    }

    public function test_journal_entry_entry_date_cast_to_carbon(): void
    {
        $entry = JournalEntry::factory()->create(['entry_date' => '2024-06-15']);

        $this->assertInstanceOf(Carbon::class, $entry->entry_date);
        $this->assertEquals('2024-06-15', $entry->entry_date->toDateString());
    }
}
