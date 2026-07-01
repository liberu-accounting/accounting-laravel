<?php
declare(strict_types=1);
namespace Tests\Unit\Models;

use App\Models\ApprovalRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_for_picks_the_most_specific_active_tier(): void
    {
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['x'], 'is_active' => false]);

        $this->assertSame(['manager'], ApprovalRule::matchFor('Bill', 100, 1)?->steps);
        $this->assertSame(['manager', 'finance_director'], ApprovalRule::matchFor('Bill', 9000, 1)?->steps);
        $this->assertNull(ApprovalRule::matchFor('Bill', 9000, 2), 'other team has no rule');
    }
}
