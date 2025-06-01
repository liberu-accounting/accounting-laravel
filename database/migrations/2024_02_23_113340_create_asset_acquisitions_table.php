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
            $table->date('acquisition_date');
            $table->decimal('acquisition_price', 10, 2);
            $table->timestamps();
$table->foreignId('asset_id')->constrained()->onDelete('cascade');
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
