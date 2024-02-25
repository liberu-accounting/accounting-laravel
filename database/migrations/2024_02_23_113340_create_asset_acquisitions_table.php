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
        Schema::create('asset_acquisitions', function (Blueprint $table) {
            $table->integer('asset_acquisition_id', true);
            $table->integer('asset_id');
            $table->date('acquisition_date');
            $table->decimal('acquisition_price', 10, 2);
            $table->timestamps();

            $table->foreign('asset_id')->references('asset_id')->on('assets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_acquisitions');
    }
};
