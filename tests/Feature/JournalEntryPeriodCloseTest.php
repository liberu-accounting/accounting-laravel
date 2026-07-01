<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryPeriodCloseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeTeam(?string $lockedBefore): Team
    {
        return Team::create([
            'user_id' => $this->user->id,
            'name' => 'Books Team',
            'personal_team' => false,
            'books_locked_before' => $lockedBefore,
        ]);
    }

    public function test_backdated_entry_posts_when_no_lock_set(): void
    {
        $team = $this->makeTeam(null);

        $entry = JournalEntry::create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'entry_date' => '2020-01-01',
            'entry_type' => 'general',
        ]);

        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);
    }

    public function test_creating_entry_before_lock_throws(): void
    {
        $team = $this->makeTeam(today()->toDateString());

        $this->expectException(\DomainException::class);

        JournalEntry::create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'entry_date' => today()->subDay()->toDateString(),
            'entry_type' => 'general',
        ]);
    }

    public function test_entry_on_or_after_lock_succeeds(): void
    {
        $team = $this->makeTeam(today()->toDateString());

        $onLock = JournalEntry::create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'entry_date' => today()->toDateString(),
            'entry_type' => 'general',
        ]);

        $afterLock = JournalEntry::create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'entry_date' => today()->addDay()->toDateString(),
            'entry_type' => 'general',
        ]);

        $this->assertDatabaseHas('journal_entries', ['id' => $onLock->id]);
        $this->assertDatabaseHas('journal_entries', ['id' => $afterLock->id]);
    }
}
