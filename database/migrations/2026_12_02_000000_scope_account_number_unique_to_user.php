<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the global unique on account_number with a per-user composite,
     * matching the per-tenant validation (Rule::unique(...)->where('user_id', ...)).
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            // drops the inline index `accounts_account_number_unique`
            $table->dropUnique(['account_number']);
            $table->unique(['user_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'account_number']);
            $table->unique(['account_number']);
        });
    }
};
