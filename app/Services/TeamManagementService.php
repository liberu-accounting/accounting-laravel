<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Team provisioning. Guarantees every user owns exactly one personal team,
 * which is their tenant (Filament resolves the tenant from owned teams).
 *
 * Restores the class the registration flow (CreateNewUserWithTeams) and the
 * CreatePersonalTeam listener already depend on — it was referenced but never
 * existed, so registration fataled and no personal teams were created.
 */
class TeamManagementService
{
    /**
     * Ensure the user owns a personal team and has it selected. Idempotent:
     * safe to call from both the Fortify create action and the Registered
     * listener, and safe to call repeatedly — it never creates a second
     * personal team.
     */
    public function createPersonalTeamForUser(User $user): Team
    {
        return DB::transaction(function () use ($user): Team {
            $team = $user->ownedTeams()->where('personal_team', true)->first();

            if (! $team instanceof Team) {
                $team = Team::forceCreate([
                    'user_id' => $user->getKey(),
                    'name' => explode(' ', (string) $user->name)[0]."'s Team",
                    'personal_team' => true,
                ]);
            }

            // The user owns the team, so belongsToTeam() is true and switchTeam succeeds.
            if ($user->current_team_id === null) {
                $user->switchTeam($team);
            }

            return $team;
        });
    }

    /**
     * ponytail: a user's default tenant IS their own personal team — isolation
     * over a shared "team 1" bucket. Both entry points converge on one team.
     */
    public function assignUserToDefaultTeam(User $user): void
    {
        $this->createPersonalTeamForUser($user);
    }
}
