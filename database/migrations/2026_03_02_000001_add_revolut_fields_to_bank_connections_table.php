<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->string('revolut_access_token')->nullable()->after('plaid_cursor');
            $table->string('revolut_refresh_token')->nullable()->after('revolut_access_token');
            $table->timestamp('revolut_token_expires_at')->nullable()->after('revolut_refresh_token');

            $table->index('bank_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropIndex(['bank_id']);
            $table->dropColumn([
                'revolut_access_token',
                'revolut_refresh_token',
                'revolut_token_expires_at',
            ]);
        });
    }
};
