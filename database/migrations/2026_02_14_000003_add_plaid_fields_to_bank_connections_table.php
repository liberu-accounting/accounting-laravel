<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // Add user_id for multi-tenancy support (required field)
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            
            // Plaid-specific fields
            $table->string('plaid_access_token')->nullable()->after('credentials');
            $table->string('plaid_item_id')->nullable()->after('plaid_access_token');
            $table->string('plaid_institution_id')->nullable()->after('plaid_item_id');
            $table->string('plaid_cursor')->nullable()->after('plaid_institution_id');
            
            // Additional metadata
            $table->string('institution_name')->nullable()->after('bank_id');
            $table->timestamp('last_synced_at')->nullable()->after('status');
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('plaid_item_id');
            $table->index('status');
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
