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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id('tax_rate_id');
            $table->string('name');
            $table->decimal('rate', 8, 2);
            $table->text('description')->nullable();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create the pivot table with matching column types
        Schema::create('customer_tax_rate', function (Blueprint $table) {
            // Use the same column type as in the customers table
            
                  $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained()->onDelete('cascade');
            $table->primary(['customer_id', 'tax_rate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_tax_rate');
        Schema::dropIfExists('tax_rates');
    }
};
