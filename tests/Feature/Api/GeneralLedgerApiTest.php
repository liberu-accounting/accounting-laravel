<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralLedgerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($this->user);
        $this->user->refresh();
    }

    public function test_trial_balance_requires_authentication(): void
    {
        $this->getJson('/api/general-ledger/trial-balance')->assertUnauthorized();
    }

    public function test_trial_balance_returns_rows(): void
    {
        Account::factory()->create(['team_id' => $this->user->current_team_id, 'account_type' => 'asset', 'normal_balance' => 'debit', 'balance' => 500]);

        $response = $this->actingAs($this->user)->getJson('/api/general-ledger/trial-balance');

        $response->assertOk()->assertJsonStructure([['account_id', 'account_name', 'debit', 'credit']]);
    }
}
