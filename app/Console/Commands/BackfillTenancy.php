<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0-T Phase 1: one-off, idempotent tenancy backfill for existing deployments.
 *
 * 1. Ensures every user owns a personal team (their tenant) with it selected.
 * 2. Fills NULL team_id on the user-owned tables from each row's user's team.
 *
 * Conservative by design: only touches rows where team_id IS NULL, so it never
 * reassigns an already-scoped row and is safe to re-run.
 */
class BackfillTenancy extends Command
{
    #[\Override]
    protected $signature = 'teams:backfill-tenancy {--dry-run : Report what would change without writing}';

    #[\Override]
    protected $description = 'Provision personal teams for existing users and backfill NULL team_id from user_id';

    /**
     * Tables carrying user_id that predate team_id being stamped on create.
     */
    private const USER_OWNED_TABLES = [
        'accounts', 'transactions', 'journal_entries', 'bank_connections',
        'expenses', 'audit_logs', 'connected_accounts',
        'qbo_connections', 'xero_connections', 'sage_connections',
    ];

    public function handle(TeamManagementService $teams): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1. Provision — every user needs exactly one owned personal team, selected.
        // The service is idempotent (creates only if missing, sets current_team_id
        // only if null), so call it for everyone and just count the new teams.
        $provisioned = 0;
        User::query()->each(function (User $user) use ($teams, $dryRun, &$provisioned): void {
            if (! $user->ownedTeams()->where('personal_team', true)->exists()) {
                $provisioned++;
            }
            if (! $dryRun) {
                $teams->createPersonalTeamForUser($user);
            }
        });
        $this->info(($dryRun ? '[dry-run] ' : '')."Provisioned personal teams for {$provisioned} user(s).");

        // 2. Backfill NULL team_id from each row's user's personal team.
        $filled = 0;
        Team::query()->where('personal_team', true)->each(function (Team $team) use ($dryRun, &$filled): void {
            foreach (self::USER_OWNED_TABLES as $table) {
                if (! Schema::hasColumn($table, 'team_id') || ! Schema::hasColumn($table, 'user_id')) {
                    continue;
                }
                $q = DB::table($table)->whereNull('team_id')->where('user_id', $team->user_id);
                $count = (clone $q)->count();
                if ($count === 0) {
                    continue;
                }
                $filled += $count;
                if (! $dryRun) {
                    $q->update(['team_id' => $team->getKey()]);
                }
            }
        });
        $this->info(($dryRun ? '[dry-run] ' : '')."Backfilled team_id on {$filled} row(s).");

        return self::SUCCESS;
    }
}
