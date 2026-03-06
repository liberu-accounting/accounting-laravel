<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // Add user_id for multi-tenancy support (only if not already present)
            if (!Schema::hasColumn('bank_connections', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
                $table->index('user_id');
            }
            
            // Plaid-specific fields
            if (!Schema::hasColumn('bank_connections', 'plaid_access_token')) {
                $table->string('plaid_access_token')->nullable()->after('credentials');
            }
            if (!Schema::hasColumn('bank_connections', 'plaid_item_id')) {
                $table->string('plaid_item_id')->nullable()->after('plaid_access_token');
                $table->index('plaid_item_id');
            }
            if (!Schema::hasColumn('bank_connections', 'plaid_institution_id')) {
                $table->string('plaid_institution_id')->nullable()->after('plaid_item_id');
            }
            if (!Schema::hasColumn('bank_connections', 'plaid_cursor')) {
                $table->string('plaid_cursor')->nullable()->after('plaid_institution_id');
            }
            
            // Additional metadata
            if (!Schema::hasColumn('bank_connections', 'institution_name')) {
                $table->string('institution_name')->nullable()->after('bank_id');
            }
            if (!Schema::hasColumn('bank_connections', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('status');
            }
            
            // Index on status (only if not already indexed)
            try {
                $table->index('status');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    public function down()
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['plaid_item_id']);
            $table->dropIndex(['status']);
            
            $table->dropColumn([
                'user_id',
                'plaid_access_token',
                'plaid_item_id',
                'plaid_institution_id',
                'plaid_cursor',
                'institution_name',
                'last_synced_at',
            ]);
        });
    }
};
