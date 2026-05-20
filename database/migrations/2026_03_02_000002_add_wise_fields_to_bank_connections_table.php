<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->string('wise_access_token')->nullable()->after('revolut_token_expires_at');
            $table->string('wise_refresh_token')->nullable()->after('wise_access_token');
            $table->timestamp('wise_token_expires_at')->nullable()->after('wise_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropColumn([
                'wise_access_token',
                'wise_refresh_token',
                'wise_token_expires_at',
            ]);
        });
    }
};
