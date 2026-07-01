<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P0-T 2b: move account_number uniqueness from per-user to per-team, matching
     * the ChartOfAccountController tenant scoping (Rule::unique(...)->where('team_id', ...)).
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'account_number']);
            $table->unique(['team_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropUnique(['team_id', 'account_number']);
            $table->unique(['user_id', 'account_number']);
        });
    }
};
