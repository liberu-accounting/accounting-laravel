<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'qbo_id')) {
                $table->string('qbo_id')->nullable()->index();
            }
            if (! Schema::hasColumn('payments', 'qbo_sync_token')) {
                $table->string('qbo_sync_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['qbo_id', 'qbo_sync_token']);
        });
    }
};
