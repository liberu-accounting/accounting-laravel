<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountNumberUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_users_can_hold_the_same_account_number(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Account::factory()->create(['user_id' => $userA->id, 'account_number' => 1000]);
        Account::factory()->create(['user_id' => $userB->id, 'account_number' => 1000]);

        $this->assertSame(2, Account::where('account_number', 1000)->count());
    }

    public function test_same_user_cannot_reuse_an_account_number(): void
    {
        $user = User::factory()->create();

        Account::factory()->create(['user_id' => $user->id, 'account_number' => 1000]);

        $this->expectException(QueryException::class);
        Account::factory()->create(['user_id' => $user->id, 'account_number' => 1000]);
    }
}
