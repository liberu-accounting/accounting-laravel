<?php

declare(strict_types=1);

namespace Tests\Feature\Approval;

use App\Filament\App\Resources\ApprovalRules\Pages\CreateApprovalRule;
use App\Models\ApprovalRule;
use App\Models\User;
use App\Services\TeamManagementService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_persists_a_rule_with_team_id_and_a_flat_steps_array(): void
    {
        $user = User::factory()->create();
        $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $this->actingAs($user->fresh());
        Filament::setTenant($team);

        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('finance_director', 'web');

        Livewire::test(CreateApprovalRule::class)
            ->fillForm([
                'approvable_type' => 'Bill',
                'min_amount' => 5000,
                'steps' => ['manager', 'finance_director'],
                'deadline_days' => 3,
                'fallback_role' => 'finance_director',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $rule = ApprovalRule::first();

        $this->assertNotNull($rule);
        $this->assertSame($team->id, $rule->team_id);
        $this->assertSame('Bill', $rule->approvable_type);
        $this->assertSame(['manager', 'finance_director'], $rule->steps);
        $this->assertTrue($rule->is_active);
    }
}
