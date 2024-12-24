

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->boolean('credit_hold')->default(false);
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'current_balance', 'credit_hold']);
        });
    }
};