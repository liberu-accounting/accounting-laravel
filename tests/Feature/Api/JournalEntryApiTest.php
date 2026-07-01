<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->teamId = app(\App\Services\TeamManagementService::class)
            ->createPersonalTeamForUser($this->user)->id;
        $this->user = $this->user->fresh();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/journal-entries')->assertUnauthorized();
    }

    public function test_store_creates_balanced_entry_with_lines(): void
    {
        $cash = Account::factory()->create(['team_id' => $this->teamId, 'normal_balance' => 'debit']);
        $revenue = Account::factory()->create(['team_id' => $this->teamId, 'normal_balance' => 'credit']);

        $response = $this->actingAs($this->user)->postJson('/api/journal-entries', [
            'entry_date' => '2026-06-01',
            'entry_type' => 'general',
            'memo' => 'API entry',
            'lines' => [
                ['account_id' => $cash->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $revenue->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ]);

        $response->assertCreated();
        $entry = JournalEntry::where('team_id', $this->teamId)->firstOrFail();
        $this->assertSame(2, $entry->lines()->count());
        $this->assertTrue($entry->isBalanced());
    }

    public function test_store_rejects_unbalanced_entry(): void
    {
        $cash = Account::factory()->create(['team_id' => $this->teamId]);

        $this->actingAs($this->user)->postJson('/api/journal-entries', [
            'entry_date' => '2026-06-01',
            'lines' => [
                ['account_id' => $cash->id, 'debit_amount' => 100, 'credit_amount' => 0],
            ],
        ])->assertStatus(422);
    }

    public function test_show_lists_own_entry(): void
    {
        $entry = JournalEntry::create(['entry_date' => now(), 'entry_type' => 'general', 'user_id' => $this->user->id, 'team_id' => $this->teamId]);

        $this->actingAs($this->user)->getJson("/api/journal-entries/{$entry->id}")->assertOk();
    }
}
