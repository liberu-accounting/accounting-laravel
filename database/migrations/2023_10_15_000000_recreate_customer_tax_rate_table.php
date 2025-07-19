<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateCustomerTaxRateTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('customer_tax_rate');

        Schema::create('customer_tax_rate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('customers')) {
            Schema::table('customer_tax_rate', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            });
        }
        
        if (Schema::hasTable('tax_rates')) {
            Schema::table('customer_tax_rate', function (Blueprint $table) {
                $table->foreign('tax_rate_id')
                    ->references('tax_rate_id')
                    ->on('tax_rates');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('customer_tax_rate');
    }
}