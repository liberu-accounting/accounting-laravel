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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->integer('supplier_id', true);
            $table->integer('payment_term_id');
            $table->string('supplier_first_name');
            $table->string('supplier_last_name');
            $table->string('supplier_email');
            $table->string('supplier_address');
            $table->string('supplier_phone_number');
            $table->decimal('supplier_limit_credit', 10, 2);
            $table->integer('supplier_tin');
            $table->timestamps();

            $table->foreign('payment_term_id')->references('payment_term_id')->on('payment_terms');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
