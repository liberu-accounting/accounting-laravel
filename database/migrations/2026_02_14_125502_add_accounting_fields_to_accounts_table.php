<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('normal_balance', ['debit', 'credit'])->after('account_type')->default('debit');
            $table->decimal('opening_balance', 15, 2)->after('balance')->default(0);
            $table->text('description')->nullable()->after('account_name');
            $table->boolean('is_active')->default(true)->after('industry_type');
            $table->boolean('allow_manual_entry')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['normal_balance', 'opening_balance', 'description', 'is_active', 'allow_manual_entry']);
        });
    }
};
