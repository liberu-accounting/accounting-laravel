

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'reconciled')) {
                $table->boolean('reconciled')->default(false);
            }
            if (!Schema::hasColumn('transactions', 'discrepancy_notes')) {
                $table->text('discrepancy_notes')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'reconciled_by_user_id')) {
                $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by_user_id']);
            $table->dropColumn(['reconciled', 'discrepancy_notes', 'reconciled_at', 'reconciled_by_user_id']);
        });
    }
};