<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IDOR guard: reference-validation (exists rules) must be scoped to the acting
 * tenant so user B cannot reference user A's records.
 *  - accounts are tenant-scoped via accounts.team_id
 *  - customers / vendors have no user_id column; team_id is the tenant boundary
 */
class TenantScopedValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private int $teamA;

    private int $teamB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->teamA = $this->makeTeam($this->userA);

        $this->userB = User::factory()->create();
        $this->teamB = $this->makeTeam($this->userB);
    }

    private function makeTeam(User $user): int
    {
        $team = Team::forceCreate(['user_id' => $user->id, 'name' => 'T'.$user->id, 'personal_team' => true]);
        $user->forceFill(['current_team_id' => $team->id])->save();

        return $team->id;
    }

    // --- JournalEntry: lines.*.account_id scoped to team_id ---

    public function test_journal_entry_rejects_another_teams_account(): void
    {
        $foreign = Account::factory()->create(['team_id' => $this->teamA, 'normal_balance' => 'debit']);
        $mine = Account::factory()->create(['team_id' => $this->teamB, 'normal_balance' => 'credit']);

        $this->actingAs($this->userB)->postJson('/api/journal-entries', [
            'entry_date' => '2026-06-01',
            'lines' => [
                ['account_id' => $foreign->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $mine->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('lines.0.account_id');
    }

    public function test_journal_entry_accepts_own_accounts(): void
    {
        $debit = Account::factory()->create(['team_id' => $this->teamB, 'normal_balance' => 'debit']);
        $credit = Account::factory()->create(['team_id' => $this->teamB, 'normal_balance' => 'credit']);

        $this->actingAs($this->userB)->postJson('/api/journal-entries', [
            'entry_date' => '2026-06-01',
            'lines' => [
                ['account_id' => $debit->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $credit->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ])->assertCreated();
    }

    // --- Bill: vendor_id scoped to team_id ---

    public function test_bill_rejects_another_teams_vendor(): void
    {
        $foreign = Vendor::factory()->create(['team_id' => $this->teamA]);

        $this->actingAs($this->userB)->postJson('/api/bills', [
            'vendor_id' => $foreign->vendor_id,
            'bill_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total_amount' => 100.00,
        ])->assertStatus(422)->assertJsonValidationErrors('vendor_id');
    }

    public function test_bill_accepts_own_vendor(): void
    {
        $mine = Vendor::factory()->create(['team_id' => $this->teamB]);

        $this->actingAs($this->userB)->postJson('/api/bills', [
            'vendor_id' => $mine->vendor_id,
            'bill_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total_amount' => 100.00,
        ])->assertCreated();
    }

    // --- Invoice: customer_id scoped to team_id ---

    public function test_invoice_rejects_another_teams_customer(): void
    {
        $foreign = Customer::factory()->create(['team_id' => $this->teamA]);

        $this->actingAs($this->userB)->postJson('/api/invoices', [
            'customer_id' => $foreign->id,
            'invoice_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total_amount' => 100.00,
            'payment_status' => 'pending',
        ])->assertStatus(422)->assertJsonValidationErrors('customer_id');
    }
}
