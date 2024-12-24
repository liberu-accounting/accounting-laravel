

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('late_fee_percentage', 5, 2)->default(0);
            $table->integer('grace_period_days')->default(0);
            $table->decimal('late_fee_amount', 10, 2)->default(0);
            $table->date('due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['late_fee_percentage', 'grace_period_days', 'late_fee_amount', 'due_date']);
        });
    }
};