<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * account_number is unique per team (P0-T 2b moved it from per-user).
 */
class AccountNumberUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function team(string $name): Team
    {
        return Team::forceCreate(['user_id' => User::factory()->create()->id, 'name' => $name, 'personal_team' => true]);
    }

    public function test_two_teams_can_hold_the_same_account_number(): void
    {
        Account::factory()->create(['team_id' => $this->team('A')->id, 'account_number' => 1000]);
        Account::factory()->create(['team_id' => $this->team('B')->id, 'account_number' => 1000]);

        $this->assertSame(2, Account::where('account_number', 1000)->count());
    }

    public function test_same_team_cannot_reuse_an_account_number(): void
    {
        $team = $this->team('A');

        Account::factory()->create(['team_id' => $team->id, 'account_number' => 1000]);

        $this->expectException(QueryException::class);
        Account::factory()->create(['team_id' => $team->id, 'account_number' => 1000]);
    }
}
