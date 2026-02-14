<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add fields for bank feed integration
            $table->string('external_id')->nullable()->unique()->after('transaction_id');
            $table->foreignId('bank_connection_id')->nullable()->after('external_id')->constrained()->onDelete('set null');
            $table->string('description')->nullable()->after('transaction_description');
            $table->string('category')->nullable()->after('description');
            $table->string('type')->nullable()->after('transaction_type'); // credit/debit
            $table->string('status')->default('posted')->after('type'); // pending/posted
            
            // Indexes for performance
            $table->index('bank_connection_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_connection_id']);
            $table->dropIndex(['bank_connection_id']);
            $table->dropIndex(['status']);
            $table->dropUnique(['external_id']);
            
            $table->dropColumn([
                'external_id',
                'bank_connection_id',
                'description',
                'category',
                'type',
                'status',
            ]);
        });
    }
};
