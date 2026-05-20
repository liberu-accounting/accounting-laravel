

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->decimal('forecast_amount', 15, 2)->nullable();
            $table->string('forecast_method')->nullable();
            $table->boolean('is_approved')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn(['forecast_amount', 'forecast_method', 'is_approved']);
        });
    }
};