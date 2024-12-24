

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('depreciation_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets', 'asset_id');
            $table->integer('year');
            $table->decimal('depreciation_amount', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2);
            $table->decimal('book_value', 15, 2);
            $table->date('calculation_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('depreciation_calculations');
    }
};