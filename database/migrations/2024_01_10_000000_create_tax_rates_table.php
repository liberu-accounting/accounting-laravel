<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id('tax_rate_id');
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->timestamps();
        });

        Schema::dropIfExists('customer_tax_rate');
        
        Schema::create('customer_tax_rate', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('tax_rate_id');
            $table->primary(['customer_id', 'tax_rate_id']);
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
                
            $table->foreign('tax_rate_id')
                ->references('tax_rate_id')
                ->on('tax_rates')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_tax_rate');
        Schema::dropIfExists('tax_rates');
    }
};
