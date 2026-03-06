<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add fields for bank feed integration (skip if already present from create migration)
            if (!Schema::hasColumn('transactions', 'external_id')) {
                $table->string('external_id')->nullable()->unique()->after('transaction_id');
            }
            if (!Schema::hasColumn('transactions', 'bank_connection_id')) {
                $table->foreignId('bank_connection_id')->nullable()->after('external_id')->constrained()->onDelete('set null');
                $table->index('bank_connection_id');
            }
            if (!Schema::hasColumn('transactions', 'description')) {
                $table->string('description')->nullable()->after('transaction_description');
            }
            if (!Schema::hasColumn('transactions', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            if (!Schema::hasColumn('transactions', 'status')) {
                $table->string('status')->default('posted')->after('type'); // pending/posted
                $table->index('status');
            }
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
