

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->date('acquisition_date')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'depreciation_method',
                'salvage_value', 
                'acquisition_date',
                'is_active'
            ]);
        });
    }
};